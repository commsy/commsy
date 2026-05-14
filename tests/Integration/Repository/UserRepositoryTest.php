<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Coverage for {@see UserRepository}'s lookup methods. Companion to
 * {@see UserRepositoryFindInContextTest} (which targets findInContext
 * specifically). Together they pin every method that filters on
 * `u.deleter` / `u.deletionDate` — that's the seam that broke during
 * the EntityUsersTrait rollout when User.deleterId was renamed.
 *
 * Tested methods (10 of 11; findInContext lives in the sibling test):
 *   - getConfirmableUserByContextId
 *   - getModeratorsByRoomId
 *   - getContactsByRoomId
 *   - findActiveUsers
 *   - findActiveUsersAsQuery
 *   - getNumActiveUsersByContext
 *   - findPortalUser
 *   - findByAccountIdAndContext
 *   - findAllByRoomStatus
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $repository;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(UserRepository::class);
        $this->account = AccountStory::get('account');
    }

    // ---- getConfirmableUserByContextId

    /**
     * Method returns a {@see QueryBuilder} (forgot ->getQuery()->getResult())
     * — caller is responsible for executing it. We force execution to
     * exercise the DQL.
     */
    public function testGetConfirmableUserByContextIdReturnsRequestedUsers(): void
    {
        $room = $this->createRoom();
        $requested = $this->createMember($this->account, $room, status: 1);
        $regular = RoomUserFactory::createOne([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 2,
        ]);

        /** @var QueryBuilder $qb */
        $qb = $this->repository->getConfirmableUserByContextId($room->getItemId());
        self::assertInstanceOf(QueryBuilder::class, $qb);

        $ids = $this->idsFromResults($qb->getQuery()->getResult());

        self::assertContains($requested->getItemId(), $ids);
        self::assertNotContains($regular->getItemId(), $ids);
    }

    public function testGetConfirmableUserByContextIdSkipsSoftDeleted(): void
    {
        $room = $this->createRoom();
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->account,
            'room' => $room,
            'status' => 1,
        ]);

        $qb = $this->repository->getConfirmableUserByContextId($room->getItemId());
        $results = $qb->getQuery()->getResult();

        self::assertSame([], $results);
    }

    // ---- getModeratorsByRoomId

    public function testGetModeratorsByRoomIdReturnsModerators(): void
    {
        $room = $this->createRoom();
        $mod = $this->createMember($this->account, $room, status: 3);
        $regular = RoomUserFactory::createOne([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 2,
        ]);

        $ids = $this->idsFromResults($this->repository->getModeratorsByRoomId($room->getItemId()));

        self::assertContains($mod->getItemId(), $ids);
        self::assertNotContains($regular->getItemId(), $ids, 'status=2 must not appear');
    }

    public function testGetModeratorsByRoomIdSkipsSoftDeleted(): void
    {
        // Note: this method only filters on deletionDate, NOT on deleter.
        // The test pins that asymmetry.
        $room = $this->createRoom();
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->account,
            'room' => $room,
            'status' => 3,
        ]);

        self::assertSame([], $this->repository->getModeratorsByRoomId($room->getItemId()));
    }

    // ---- getContactsByRoomId

    public function testGetContactsByRoomIdReturnsContactFlaggedMembers(): void
    {
        $room = $this->createRoom();
        $contact = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
            'isContact' => true,
        ]);
        $nonContact = RoomUserFactory::createOne([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 2,
            'isContact' => false,
        ]);

        $ids = $this->idsFromResults($this->repository->getContactsByRoomId($room->getItemId()));

        self::assertContains($contact->getItemId(), $ids);
        self::assertNotContains($nonContact->getItemId(), $ids);
    }

    // ---- findActiveUsers / findActiveUsersAsQuery / getNumActiveUsersByContext

    public function testFindActiveUsersReturnsMembersAndExcludesSoftDeleted(): void
    {
        $room = $this->createRoom();
        $alive = $this->createMember($this->account, $room);
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 2,
        ]);

        $ids = $this->idsFromResults($this->repository->findActiveUsers($room->getItemId()));

        self::assertContains($alive->getItemId(), $ids);
        self::assertCount(1, $ids, 'soft-deleted user must not appear');
    }

    public function testFindActiveUsersAsQueryReturnsExecutableQuery(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room);

        $query = $this->repository->findActiveUsersAsQuery($room->getItemId());

        self::assertInstanceOf(Query::class, $query);
        self::assertCount(1, $query->getResult());
    }

    public function testGetNumActiveUsersByContextReturnsScalarCount(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room);
        $this->createMember($this->createSecondAccount(), $room);
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 2,
        ]);

        self::assertSame(2, (int) $this->repository->getNumActiveUsersByContext($room->getItemId()));
    }

    // ---- findPortalUser

    public function testFindPortalUserReturnsThePortalLevelUserForTheAccount(): void
    {
        // AccountStory pre-creates a portal-level User row keyed on
        // (portal_id as context, userId, authSource).
        $portalUser = $this->repository->findPortalUser($this->account);

        self::assertInstanceOf(User::class, $portalUser);
        self::assertSame($this->account->getUsername(), $portalUser->getUserId());
    }

    public function testFindPortalUserDistinguishesBetweenAccounts(): void
    {
        $other = $this->createSecondAccount();

        $first = $this->repository->findPortalUser($this->account);
        $second = $this->repository->findPortalUser($other);

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame(
            $first->getItemId(),
            $second->getItemId(),
            'Each Account must resolve to its own distinct portal-level User',
        );
        self::assertSame($this->account->getUsername(), $first->getUserId());
        self::assertSame($other->getUsername(), $second->getUserId());
    }

    // ---- findByAccountIdAndContext

    public function testFindByAccountIdAndContextFindsRoomMembership(): void
    {
        $room = $this->createRoom();
        $member = $this->createMember($this->account, $room);

        $found = $this->repository->findByAccountIdAndContext(
            $this->account->getId(),
            $room->getItemId(),
        );

        self::assertNotNull($found);
        self::assertSame($member->getItemId(), $found->getItemId());
    }

    public function testFindByAccountIdAndContextFiltersSoftDeleted(): void
    {
        $room = $this->createRoom();
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
        ]);

        self::assertNull(
            $this->repository->findByAccountIdAndContext(
                $this->account->getId(),
                $room->getItemId(),
            ),
        );
    }

    // ---- findAllByRoomStatus

    public function testFindAllByRoomStatusReturnsActiveMemberships(): void
    {
        // Account creation auto-provisions a private room + membership,
        // so the baseline is non-empty. Test the delta we add.
        $baseline = $this->repository->findAllByRoomStatus($this->account);
        $baselineCount = count($baseline);

        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        $community = RoomFactory::new()->community()->create($this->roomDefaults());

        $this->createMember($this->account, $project);
        $this->createMember($this->account, $community);

        $results = $this->repository->findAllByRoomStatus($this->account);

        self::assertCount($baselineCount + 2, $results);
    }

    public function testFindAllByRoomStatusFiltersByType(): void
    {
        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        $community = RoomFactory::new()->community()->create($this->roomDefaults());

        $this->createMember($this->account, $project);
        $this->createMember($this->account, $community);

        $results = $this->repository->findAllByRoomStatus(
            $this->account,
            filterType: 'project',
        );

        self::assertCount(1, $results);
    }

    public function testFindAllByRoomStatusFiltersOutSoftDeletedMembership(): void
    {
        $baseline = $this->repository->findAllByRoomStatus($this->account);
        $baselineCount = count($baseline);

        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->account,
            'room' => $project,
            'status' => 2,
        ]);

        // Soft-deleted membership must NOT increase the result count.
        $results = $this->repository->findAllByRoomStatus($this->account);
        self::assertCount($baselineCount, $results, 'soft-deleted memberships are filtered');
    }

    // ---- helpers

    private function createRoom()
    {
        return RoomFactory::new()->project()->create($this->roomDefaults());
    }

    private function roomDefaults(): array
    {
        return [
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ];
    }

    private function createMember(Account $account, $room, int $status = 2): User
    {
        return RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => $status,
        ]);
    }

    private function createSecondAccount(): Account
    {
        $portal = $this->account->getPortal();
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }

    /**
     * @param User[] $results
     * @return int[]
     */
    private function idsFromResults(array $results): array
    {
        return array_map(static fn (User $u): int => $u->getItemId(), $results);
    }
}
