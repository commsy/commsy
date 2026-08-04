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

use App\Entity\Room;
use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for ItemVoter::EDIT. Pins behavior of the EDIT path
 * before we extract it into App\Security\Permission\Checker\ItemEditChecker
 * (Phase 2.3). The checker will absorb cs_item::mayEdit() and break the
 * circular Security::isGranted(EDIT_LOCK) call out of cs_item.php.
 *
 * The voter's EDIT path mixes:
 *   - Account=root short-circuit (top-level)
 *   - Archived-room block (with self-edit bypass for own user_item)
 *   - canEditLock() via LockManager
 *   - isLockedByModerator() for project/community
 *   - isReadOnlyUser() self-edit bypass
 *   - $item->mayEdit($currentUser) (the legacy delegate)
 *
 * Per-test the user is logged in IN THE ROOM CONTEXT — the legacy
 * `currentUserItem` is set to the room-level cs_user_item, mirroring what
 * LegacySubscriber does in production when a request carries a roomId. The
 * archived self-edit shortcut depends on this (it compares
 * `$item->getItemID() === $currentUser->getItemID()`).
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterEditTest extends KernelTestCase
{
    use BootsVoter;

    // ---------- creator-can-edit-own / others-cannot (private editing default)

    public function testCreatorCanEditOwnMaterial(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'A regular member who created the material can edit it (private editing default)',
        );
    }

    public function testRegularMemberCannotEditOthersMaterialWhenPrivateEditing(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $otherAccount = $this->createPortalAccount();
        $this->createMember($otherAccount, $room, 2);

        $this->loginAs($otherAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'A non-creator regular member cannot edit a private-editing material',
        );
    }

    // ---------- moderator can edit anything in their room

    public function testModeratorCanEditOthersMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $modAccount = $this->createPortalAccount();
        $this->createMember($modAccount, $room, 3);

        $this->loginAs($modAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'Moderator can edit any material in the room',
        );
    }

    // ---------- root short-circuit at voter top-level

    public function testRootAccountCanEditAnyMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'Account.username="root" short-circuits the voter to true',
        );
    }

    // ---------- read-only and requested members blocked

    public function testReadOnlyMemberCannotEditOthersMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $readOnlyAccount = $this->createPortalAccount();
        $this->createMember($readOnlyAccount, $room, 4);

        $this->loginAs($readOnlyAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'Read-only members cannot edit anything except their own user_item',
        );
    }

    public function testRequestedMemberCannotEditMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $requestedAccount = $this->createPortalAccount();
        $this->createMember($requestedAccount, $room, 1);

        $this->loginAs($requestedAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'Members with status=requested cannot edit',
        );
    }

    // ---------- archived room blocks edit, except own user_item

    public function testCreatorCannotEditOwnMaterialInArchivedRoom(): void
    {
        $room = $this->createProjectRoom(archived: true);
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'Edit of room content is blocked when the room is archived',
        );
    }

    public function testMemberCanEditOwnUserItemInArchivedRoom(): void
    {
        $room = $this->createProjectRoom(archived: true);
        $member = $this->createMember($this->portalAccount, $room, 2);

        $this->loginAs($this->portalAccount, $room);

        // Voter's archived branch has an explicit bypass:
        // if ($item instanceof cs_user_item && $item->getItemID() === $currentUser->getItemID()) return true;
        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $member->getItemId()),
            'Member can edit own user_item even in archived room (so they can leave)',
        );
    }

    // ---- isLockedByModerator (status=4 LOCKED_PORTAL_MOD) blocks room
    //      moderators from editing the room itself; only portal moderators
    //      retain edit access. Voter::canEdit applies this only when the
    //      item is a project/community room.

    public function testRoomModeratorCannotEditLockedByModeratorProject(): void
    {
        $room = $this->createProjectRoom(lockedByModerator: true);
        $this->createMember($this->portalAccount, $room, 3);

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $room->getItemId()),
            'Room moderators cannot edit a project room locked by a portal moderator',
        );
    }

    public function testPortalModeratorCanEditLockedByModeratorProject(): void
    {
        $room = $this->createProjectRoom(lockedByModerator: true);
        $this->createMember($this->portalAccount, $room, 3);
        $this->promoteToPortalModerator($this->portalAccount);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $room->getItemId()),
            'Portal moderators bypass the LOCKED_PORTAL_MOD restriction',
        );
    }

    // ---- canEditLock blocks edit when another user holds the lock ----
    //      Voter::canEdit -> if (!canEditLock(...)) return false;

    public function testHeldLockBlocksEditForOtherUser(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 3);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        // Lock holder: another moderator account that grabbed the lock first.
        $lockHolder = $this->createPortalAccount();
        $this->createMember($lockHolder, $room, 3);
        $this->placeLock($material->getItemId(), $lockHolder);

        // The actor (also a moderator) tries to edit — Voter::canEditLock
        // delegates to LockManager::userCanLock which compares the lock's
        // owner to the *current security user*. Mismatch → false → EDIT is
        // blocked even though mayEdit() would have allowed it.
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $material->getItemId()),
            'A live lock held by another user blocks EDIT via canEditLock()',
        );
    }

    // ---- ReadOnlyUser self-edit bypass: status 4 cannot edit anything,
    //      EXCEPT their own user_item (which Voter::canEdit short-circuits
    //      BEFORE calling mayEdit, so status 4 still passes for own profile).

    public function testReadOnlyMemberCanEditOwnUserItem(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 4);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $member->getItemId()),
            'RO users can still edit their own user_item (Voter has explicit shortcut)',
        );
    }

    // ---- EDIT on PORTAL-level user items. This is the path the portal
    //      settings account index acts on: the actor is logged in at portal
    //      level (no room), and the subject is another account's portal user
    //      item. The rule comes from UserEditChecker::canEdit — same context
    //      plus moderator, or self. "Same context" is the portal here, which
    //      is what confines a portal moderator to their own portal.

    public function testPortalModeratorCanEditAnotherPortalUser(): void
    {
        $targetAccount = $this->createPortalAccount();
        $targetUserId = $this->portalUserItemId($targetAccount);

        $modAccount = $this->createPortalAccount();
        $this->promoteToPortalModerator($modAccount);
        $this->loginAs($modAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $targetUserId),
            'A portal moderator can edit another user of the same portal',
        );
    }

    public function testPortalModeratorCannotEditAUserOfAnotherPortal(): void
    {
        $foreignPortal = PortalFactory::createOne();
        $foreignAccount = $this->createPortalAccount(portal: $foreignPortal);
        $foreignUserId = $this->portalUserItemId($foreignAccount);

        $modAccount = $this->createPortalAccount();
        $this->promoteToPortalModerator($modAccount);
        $this->loginAs($modAccount);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $foreignUserId),
            'Portal moderation stops at the portal boundary — a user of another portal is off limits',
        );
    }

    public function testRegularPortalUserCannotEditAnotherPortalUser(): void
    {
        $targetAccount = $this->createPortalAccount();
        $targetUserId = $this->portalUserItemId($targetAccount);

        $plainAccount = $this->createPortalAccount();
        $this->loginAs($plainAccount);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT, $targetUserId),
            'Without moderator status only your own portal user item is editable',
        );
    }

    public function testRegularPortalUserCanEditOwnPortalUser(): void
    {
        $plainAccount = $this->createPortalAccount();
        $ownUserId = $this->portalUserItemId($plainAccount);

        $this->loginAs($plainAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $ownUserId),
            'A user can always edit their own portal user item',
        );
    }

    public function testRootCanEditAUserOfAnyPortal(): void
    {
        $foreignPortal = PortalFactory::createOne();
        $foreignAccount = $this->createPortalAccount(portal: $foreignPortal);
        $foreignUserId = $this->portalUserItemId($foreignAccount);

        $this->actAsRoot($this->createPortalAccount('root'));

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT, $foreignUserId),
            'root bypasses the portal boundary (ItemVoter short-circuits on username=root)',
        );
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    private function createProjectRoom(bool $archived = false, bool $lockedByModerator = false): Room
    {
        $factory = RoomFactory::new()->project();
        if ($archived) {
            $factory = $factory->archived();
        }
        if ($lockedByModerator) {
            $factory = $factory->lockedByModerator();
        }

        return $factory->create($this->roomDefaults());
    }
}
