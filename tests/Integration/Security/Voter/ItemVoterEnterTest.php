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

namespace Tests\Integration\Security\Voter;

use App\Entity\Account;
use App\Entity\Room;
use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for ItemVoter::ENTER. These pin the *current* behavior
 * of the voter so the upcoming extraction into App\Room\RoomAccessChecker
 * (Phase 2.2 of the permission refactor) cannot regress room-entry semantics.
 *
 * Tests are intentionally black-box: we set up rooms + memberships via
 * factories, log in via the security token storage, then call
 * isGranted(ItemVoter::ENTER, $roomItemId) and assert the result.
 *
 * The voter delegates to:
 *   - cs_context_item::mayEnter() → mayEnterByUserID()
 *   - cs_user_manager::isUserInContext() (which filters status >= 2)
 *   - the Account=root short-circuit at voter top-level
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterEnterTest extends KernelTestCase
{
    use BootsVoter;

    /**
     * @return iterable<string, array{
     *     roomBuilder: callable(static): Room,
     *     memberStatus: ?int,
     *     accountUsername: ?string,
     *     expected: bool,
     * }>
     */
    public static function provideEnterScenarios(): iterable
    {
        // ---- Account=root short-circuit: any subject, always true ----
        yield 'root_account_can_enter_normal_room' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => null,
            'accountUsername' => 'root',
            'expected' => true,
        ];

        yield 'root_account_can_enter_locked_room' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['locked' => true]),
            'memberStatus' => null,
            'accountUsername' => 'root',
            'expected' => true,
        ];

        yield 'root_account_can_enter_deleted_room' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['deleted' => true]),
            'memberStatus' => null,
            'accountUsername' => 'root',
            'expected' => true,
        ];

        // ---- Regular project room with various member statuses ----
        yield 'regular_user_member_can_enter_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'moderator_member_can_enter_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => 3,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'read_only_member_can_enter_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => 4,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'requested_member_cannot_enter_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => 1,
            'accountUsername' => null,
            'expected' => false,
        ];

        yield 'rejected_member_cannot_enter_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => 0,
            'accountUsername' => null,
            'expected' => false,
        ];

        yield 'non_member_cannot_enter_normal_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(),
            'memberStatus' => null,
            'accountUsername' => null,
            'expected' => false,
        ];

        // ---- openForGuests bypass: only community rooms honor the flag ----
        // cs_project_item::isOpenForGuests() / cs_grouproom_item / cs_userroom_item
        // are HARDCODED to return false regardless of the is_open_for_guests
        // column. Only cs_community_item falls through to cs_context_item's
        // DB-driven implementation. Pinning this quirk so the refactor doesn't
        // accidentally "fix" it (which would be a behavior change).
        yield 'non_member_can_enter_openforguests_community' => [
            'roomBuilder' => fn(self $t) => $t->createCommunityRoom(['openForGuests' => true]),
            'memberStatus' => null,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'non_member_cannot_enter_openforguests_project_legacy_quirk' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['openForGuests' => true]),
            'memberStatus' => null,
            'accountUsername' => null,
            'expected' => false,
        ];

        // ---- Locked rooms block everyone (except root short-circuit) ----
        yield 'member_cannot_enter_locked_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['locked' => true]),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => false,
        ];

        yield 'moderator_cannot_enter_locked_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['locked' => true]),
            'memberStatus' => 3,
            'accountUsername' => null,
            'expected' => false,
        ];

        // ---- Deleted rooms: voter checks isDeleted() before mayEnter ----
        yield 'member_cannot_enter_deleted_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['deleted' => true]),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => false,
        ];

        // ---- Same logic applies to community / grouproom / userroom ----
        yield 'member_can_enter_community_room' => [
            'roomBuilder' => fn(self $t) => $t->createCommunityRoom(),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'non_member_cannot_enter_community_room' => [
            'roomBuilder' => fn(self $t) => $t->createCommunityRoom(),
            'memberStatus' => null,
            'accountUsername' => null,
            'expected' => false,
        ];

        yield 'member_can_enter_grouproom' => [
            'roomBuilder' => fn(self $t) => $t->createGroupRoom(),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => true,
        ];

        yield 'member_can_enter_userroom' => [
            'roomBuilder' => fn(self $t) => $t->createUserRoom(),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => true,
        ];

        // ---- Archived rooms still allow ENTER (only EDIT is blocked) ----
        yield 'member_can_enter_archived_project' => [
            'roomBuilder' => fn(self $t) => $t->createProjectRoom(['archived' => true]),
            'memberStatus' => 2,
            'accountUsername' => null,
            'expected' => true,
        ];

        // ---- Private rooms: only the owner (or root) may enter ----
        // A private room is a single user's personal dashboard. The owner is
        // its sole moderator (status=3), so a logged-in account that is the
        // moderator of the room may enter it. Non-owner / foreign / guest
        // scenarios live in dedicated methods below because they need a
        // *second* account as the owner.
        yield 'owner_can_enter_own_private_room' => [
            'roomBuilder' => fn(self $t) => $t->createPrivateRoom(),
            'memberStatus' => 3,
            'accountUsername' => null,
            'expected' => true,
        ];
    }

    #[DataProvider('provideEnterScenarios')]
    public function testEnterScenario(
        callable $roomBuilder,
        ?int $memberStatus,
        ?string $accountUsername,
        bool $expected,
    ): void {
        $account = $accountUsername === null
            ? $this->portalAccount
            : $this->createPortalAccount($accountUsername);

        $room = $roomBuilder($this);

        if ($memberStatus !== null) {
            RoomUserFactory::createOne([
                'account' => $account,
                'room' => $room,
                'status' => $memberStatus,
            ]);
        }

        $this->loginAs($account);

        $actual = $this->authChecker->isGranted(ItemVoter::ENTER, $room->getItemId());

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'isGranted(ItemVoter::ENTER, %d) returned %s, expected %s',
                $room->getItemId(),
                $actual ? 'true' : 'false',
                $expected ? 'true' : 'false',
            ),
        );
    }

    // ---------- Private rooms: a private room is a single user's personal
    //            dashboard, so entry is owner-only. RoomController guards its
    //            whole class with ITEM_ENTER, which makes this one rule cover
    //            every route of that controller.

    public function testNonOwnerCannotEnterForeignPrivateRoom(): void
    {
        $room = $this->createPrivateRoom();

        // The private room's owner is its sole moderator — a *different*
        // account than the one attempting to enter below.
        $owner = $this->createPortalAccount('privateroom-owner');
        RoomUserFactory::createOne([
            'account' => $owner,
            'room' => $room,
            'status' => 3,
        ]);

        // An unrelated authenticated account tries to open the foreign
        // dashboard (the /room/{foreignPrivateRoomId}/all attack).
        $intruder = $this->createPortalAccount('privateroom-intruder');
        $this->loginAs($intruder);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::ENTER, $room->getItemId()),
            'A user must not enter another user\'s private room (dashboard); '
            . 'this is the /room/{id}/all room-list leak.',
        );
    }

    public function testRootCanEnterForeignPrivateRoom(): void
    {
        $room = $this->createPrivateRoom();

        $owner = $this->createPortalAccount('privateroom-owner-for-root');
        RoomUserFactory::createOne([
            'account' => $owner,
            'room' => $room,
            'status' => 3,
        ]);

        // Account.username='root' short-circuit still wins for private rooms.
        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::ENTER, $room->getItemId()),
            'root may enter any private room via the top-level short-circuit.',
        );
    }

    // ---------- Portal subjects: when the voter receives a Portal entity
    //            (or a portal-id), it goes into the `isPortal()` branch with
    //            its own root/locked/openForGuests/authenticated rules.

    public function testAuthenticatedUserCanEnterDefaultPortal(): void
    {
        $portal = $this->portalAccount->getPortal();
        $this->loginAs($this->portalAccount);

        // Default portal: status=1 (open), no AuthSourceGuest. Falls through
        // to `return $user instanceof UserInterface;` — true for any
        // authenticated Account.
        self::assertTrue($this->authChecker->isGranted(ItemVoter::ENTER, $portal));
    }

    public function testLockedPortalBlocksNonRootEnter(): void
    {
        $lockedPortal = PortalFactory::new()->locked()->create();
        $account = $this->createPortalAccount(portal: $lockedPortal);
        $this->loginAs($account);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::ENTER, $lockedPortal),
            'A portal with status=3 (locked) blocks ENTER for non-root users — '
            . 'PortalProxy::isLocked() casts the string column to int before '
            . 'comparison.',
        );
    }

    public function testRootCanEnterLockedPortal(): void
    {
        $lockedPortal = PortalFactory::new()->locked()->create();
        $rootAccount = $this->createPortalAccount('root', portal: $lockedPortal);
        $this->actAsRoot($rootAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::ENTER, $lockedPortal),
            'Account.username="root" short-circuit always returns true',
        );
    }

    public function testNonMemberCanEnterPortalOpenForGuests(): void
    {
        $portal = PortalFactory::new()->withGuestAuth()->create();
        $account = $this->createPortalAccount(portal: $portal);
        $this->loginAs($account);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::ENTER, $portal),
            'Portals with an enabled AuthSourceGuest count as open for guests',
        );
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    /**
     * @internal Exposed as `public` only because DataProvider closures need a
     * static call site (`fn(self $t) => $t->createProjectRoom()`).
     */
    public function createProjectRoom(array $overrides = []): Room
    {
        return $this->buildRoom('project', $overrides);
    }

    public function createCommunityRoom(array $overrides = []): Room
    {
        return $this->buildRoom('community', $overrides);
    }

    public function createGroupRoom(array $overrides = []): Room
    {
        return $this->buildRoom('grouproom', $overrides);
    }

    public function createUserRoom(array $overrides = []): Room
    {
        return $this->buildRoom('userroom', $overrides);
    }

    public function createPrivateRoom(array $overrides = []): Room
    {
        return $this->buildRoom('privateroom', $overrides);
    }

    private function buildRoom(string $type, array $overrides): Room
    {
        $factory = RoomFactory::new();

        if (!empty($overrides['locked'])) {
            $factory = $factory->locked();
        }

        if (!empty($overrides['openForGuests'])) {
            $factory = $factory->openForGuests();
        }

        if (!empty($overrides['deleted'])) {
            $factory = $factory->deleted();
        }

        if (!empty($overrides['archived'])) {
            $factory = $factory->archived();
        }

        return $factory->create($this->roomDefaults() + ['type' => $type]);
    }

}
