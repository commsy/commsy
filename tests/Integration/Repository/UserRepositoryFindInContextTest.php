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
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Repository contract for {@see UserRepository::findInContext()}: returns
 * the User entity that represents the given Account in the given context,
 * or null when no such row exists.
 *
 * Filter rules pinned by these tests:
 *   - context_id matches the requested context exactly
 *   - userId AND authSource of the membership match the Account
 *   - soft-deleted memberships are excluded
 *
 * The tests don't characterize what *kinds* of contexts exist (portal
 * user, room user, private-room user) — that is the job of
 * AccountCreatorFacade tests. Here we just verify the lookup query.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class UserRepositoryFindInContextTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private Account $account;

    public function testReturnsTheMembershipForAccountInGivenContext(): void
    {
        $room = $this->createProjectRoomInPortal();
        $expected = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 2,
        ]);

        $found = $this->userRepository->findInContext($this->account, $room->getItemId());

        self::assertNotNull($found);
        self::assertSame($expected->getItemId(), $found->getItemId());
    }

    public function testReturnsNullWhenAccountHasNoMembershipInContext(): void
    {
        $room = $this->createProjectRoomInPortal();
        // No RoomUser created in $room for $this->account.

        self::assertNull($this->userRepository->findInContext($this->account, $room->getItemId()));
    }

    public function testReturnsNullWhenContextHoldsADifferentAccountsMembership(): void
    {
        $room = $this->createProjectRoomInPortal();
        $otherAccount = $this->createSecondAccount();
        RoomUserFactory::createOne([
            'account' => $otherAccount,
            'room' => $room,
            'status' => 3,
        ]);

        // Querying with $this->account must not return $otherAccount's row.
        self::assertNull($this->userRepository->findInContext($this->account, $room->getItemId()));
    }

    public function testIgnoresSoftDeletedMemberships(): void
    {
        $room = $this->createProjectRoomInPortal();
        RoomUserFactory::new()
            ->softDeleted()
            ->create([
                'account' => $this->account,
                'room' => $room,
                'status' => 2,
            ]);

        self::assertNull(
            $this->userRepository->findInContext($this->account, $room->getItemId()),
            'The query must filter on `deletion_date IS NULL AND deleter_id IS NULL`',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->account = AccountStory::get('account');
    }

    private function createProjectRoomInPortal()
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
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
}
