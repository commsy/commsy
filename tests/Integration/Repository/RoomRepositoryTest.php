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

namespace Tests\Integration\Repository;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Tests\Story\PortalStory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(PortalStory::class)]
#[WithStory(AccountStory::class)]
final class RoomRepositoryTest extends KernelTestCase
{
    private RoomRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(RoomRepository::class);
    }

    // ---- findAllIds (pre-existing test, kept verbatim)

    #[WithStory(RoomWithMemberStory::class)]
    public function testFindAllIdsReturnsOnlyExistingNonDeletedRooms(): void
    {
        $portal = PortalStory::get('portal');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $activeRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'project',
            'deleter' => null,
            'deletionDate' => null,
        ]);

        $deletedRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'project',
            'deleter' => $roomUser,
            'deletionDate' => new DateTimeImmutable(),
        ]);

        $otherTypeRoom = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'grouproom',
            'deleter' => null,
            'deletionDate' => null,
        ]);

        $ids = $this->repository->findAllIds(['grouproom', 'userroom', 'privateroom']);

        $this->assertContains($activeRoom->getItemId(), $ids);
        $this->assertNotContains($deletedRoom->getItemId(), $ids);
        $this->assertNotContains($otherTypeRoom->getItemId(), $ids);
    }

    // ---- getMainRoomQueryBuilder

    public function testGetMainRoomQueryBuilderFiltersByPortalAndTypeAndExcludesDeleted(): void
    {
        $portal = AccountStory::get('account')->getPortal();
        $portalId = $portal->getId();

        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        $community = RoomFactory::new()->community()->create($this->roomDefaults());
        $deleted = RoomFactory::createOne($this->roomDefaults() + [
            'type' => 'project',
            'deletionDate' => new DateTimeImmutable(),
        ]);

        $qb = $this->repository->getMainRoomQueryBuilder($portalId, ['project', 'community']);
        self::assertInstanceOf(QueryBuilder::class, $qb);

        /** @var Room[] $rooms */
        $rooms = $qb->getQuery()->getResult();
        $ids = array_map(static fn (Room $r): int => $r->getItemId(), $rooms);

        self::assertContains($project->getItemId(), $ids);
        self::assertContains($community->getItemId(), $ids);
        self::assertNotContains($deleted->getItemId(), $ids);
    }

    // ---- getNumActiveRoomsByPortal

    public function testGetNumActiveRoomsByPortalCountsAllExceptPrivateAndDeleted(): void
    {
        $portalId = AccountStory::get('account')->getPortal()->getId();
        $baseline = $this->repository->getNumActiveRoomsByPortal($portalId);

        RoomFactory::new()->project()->create($this->roomDefaults());
        RoomFactory::new()->community()->create($this->roomDefaults());
        // Private rooms are excluded by the query.
        RoomFactory::new()->privateRoom()->create($this->roomDefaults());
        // Soft-deleted rooms are excluded.
        RoomFactory::createOne($this->roomDefaults() + [
            'type' => 'project',
            'deletionDate' => new DateTimeImmutable(),
        ]);

        self::assertSame(
            $baseline + 2,
            $this->repository->getNumActiveRoomsByPortal($portalId),
            'private + soft-deleted rooms must NOT be counted',
        );
    }

    // ---- getActiveRoomsByAccount

    public function testGetActiveRoomsByAccountReturnsRoomsWithMembership(): void
    {
        $account = AccountStory::get('account');
        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        $unrelated = RoomFactory::new()->project()->create($this->roomDefaults());

        RoomUserFactory::createOne([
            'account' => $account,
            'room' => $project,
            'status' => 2,
        ]);

        $rooms = $this->repository->getActiveRoomsByAccount($account, ['project']);
        $ids = array_map(static fn (Room $r): int => $r->getItemId(), $rooms);

        self::assertContains($project->getItemId(), $ids);
        self::assertNotContains($unrelated->getItemId(), $ids, 'rooms without membership must be excluded');
    }

    public function testGetActiveRoomsByAccountFiltersOutSoftDeletedMembership(): void
    {
        $account = AccountStory::get('account');
        $project = RoomFactory::new()->project()->create($this->roomDefaults());

        RoomUserFactory::new()->softDeleted()->create([
            'account' => $account,
            'room' => $project,
            'status' => 2,
        ]);

        $rooms = $this->repository->getActiveRoomsByAccount($account, ['project']);
        $ids = array_map(static fn (Room $r): int => $r->getItemId(), $rooms);

        self::assertNotContains(
            $project->getItemId(),
            $ids,
            'soft-deleted membership must hide the room',
        );
    }

    // ---- findOnePrivateByPortalIdAndAccount

    public function testFindOnePrivateByPortalIdAndAccountReturnsTheAutoProvisionedPrivateRoom(): void
    {
        // AccountFactory's afterPersist provisions a private room for
        // every new Account; this test pins that the lookup finds it.
        $account = AccountStory::get('account');

        $privateRoom = $this->repository->findOnePrivateByPortalIdAndAccount(
            $account->getPortal()->getId(),
            $account,
        );

        self::assertInstanceOf(Room::class, $privateRoom);
        self::assertSame('privateroom', $privateRoom->getType());
    }

    public function testFindOnePrivateByPortalIdAndAccountReturnsNullForUnrelatedPortal(): void
    {
        $account = AccountStory::get('account');

        // Random unused portal id — no private room exists there for
        // this account.
        $foreignPortalId = $account->getPortal()->getId() + 999_999;

        self::assertNull(
            $this->repository->findOnePrivateByPortalIdAndAccount($foreignPortalId, $account),
        );
    }

    // ---- getProjectAndUserRoomIds

    public function testGetProjectAndUserRoomIdsReturnsBothTypes(): void
    {
        $project = RoomFactory::new()->project()->create($this->roomDefaults());
        $userRoom = RoomFactory::new()->userRoom()->create($this->roomDefaults());
        $community = RoomFactory::new()->community()->create($this->roomDefaults());

        $ids = $this->repository->getProjectAndUserRoomIds();

        self::assertContains($project->getItemId(), $ids);
        self::assertContains($userRoom->getItemId(), $ids);
        self::assertNotContains($community->getItemId(), $ids, 'community rooms must NOT appear');
    }

    // ---- updateActivity

    public function testUpdateActivityFlipsRoomActivityState(): void
    {
        $room = RoomFactory::createOne($this->roomDefaults() + [
            'type' => 'project',
            'activityState' => 'active',
        ]);

        $affected = $this->repository->updateActivity('active', 'idle');

        self::assertGreaterThanOrEqual(1, $affected);

        // Refresh — Doctrine doesn't auto-invalidate on bulk updates.
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $reloaded = $em->getRepository(Room::class)->find($room->getItemId());

        self::assertSame('idle', $reloaded->getActivityState());
    }

    // ---- countByPortalAndType

    public function testCountByPortalAndTypeAggregatesActiveRooms(): void
    {
        $portal = AccountStory::get('account')->getPortal();

        RoomFactory::new()->project()->create($this->roomDefaults());
        RoomFactory::new()->project()->create($this->roomDefaults());
        RoomFactory::new()->community()->create($this->roomDefaults());
        // Soft-deleted: must NOT be counted.
        RoomFactory::createOne($this->roomDefaults() + [
            'type' => 'project',
            'deletionDate' => new DateTimeImmutable(),
        ]);

        $rows = $this->repository->countByPortalAndType();

        // Find counts for our portal grouped by type.
        $countsByType = [];
        foreach ($rows as $row) {
            if ($row['portal'] === $portal->getTitle()) {
                $countsByType[$row['type']] = (int) $row['count'];
            }
        }

        self::assertGreaterThanOrEqual(2, $countsByType['project'] ?? 0);
        self::assertGreaterThanOrEqual(1, $countsByType['community'] ?? 0);
    }

    // ---- helpers

    private function roomDefaults(): array
    {
        $portal = AccountStory::get('account')->getPortal();

        return [
            'contextId' => $portal->getId(),
            'portal' => $portal,
        ];
    }
}
