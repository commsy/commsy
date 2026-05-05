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

use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for ItemVoter::DELETE. The DELETE attribute is
 * only sensible for rooms — DeleteAction.php uses ITEM_EDIT for rubric
 * items. The voter:
 *   - returns false for userrooms (those go through a separate
 *     "remove member" flow, not DELETE)
 *   - returns false for already-deleted rooms (idempotency guard)
 *   - returns true for parent moderators (portal moderator OR community
 *     moderator owning a project, OR project moderator owning a grouproom)
 *   - returns true for room moderators of the room itself
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterDeleteTest extends KernelTestCase
{
    use BootsVoter;

    public function testRoomModeratorCanDeleteOwnProjectRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::DELETE, $room->getItemId()),
            'Room moderators can delete their own project room',
        );
    }

    public function testRegularMemberCannotDeleteProjectRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::DELETE, $room->getItemId()),
            'Regular members cannot delete the room they are in',
        );
    }

    public function testPortalModeratorCanDeleteProjectRoom(): void
    {
        $room = $this->createProjectRoom();
        // Portal moderator only — NOT a member of the project room.
        $this->promoteToPortalModerator($this->portalAccount);

        // Login at portal level, since the actor is not a room member.
        $this->loginAs($this->portalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::DELETE, $room->getItemId()),
            'Portal moderators are "parent moderators" of every project room and can delete them',
        );
    }

    public function testUserroomCannotBeDeletedEvenByModerator(): void
    {
        $userRoom = $this->createUserRoom();
        $this->createMember($this->portalAccount, $userRoom, 3);

        $this->loginAs($this->portalAccount, $userRoom);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::DELETE, $userRoom->getItemId()),
            'Userrooms have a separate lifecycle and cannot be deleted via ITEM_DELETE',
        );
    }

    public function testRootAccountCanDeleteAnything(): void
    {
        $userRoom = $this->createUserRoom();

        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        // The userroom guard normally blocks DELETE; the Account.username='root'
        // short-circuit at voter top-level overrides it (returns true before
        // canDelete is even called).
        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::DELETE, $userRoom->getItemId()),
            'Account.username="root" short-circuits ITEM_DELETE to true even for userrooms',
        );
    }

    public function testCannotDeleteAlreadyDeletedRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);

        // Login BEFORE the soft-delete: the membership lookup needs the
        // room to be alive. After login the room can be soft-deleted and
        // the voter still works with the cached currentUserItem.
        $this->loginAs($this->portalAccount, $room);

        // Soft-delete via raw DBAL, then evict the legacy room manager
        // caches so the voter actually re-reads the now-deleted row.
        $this->softDeleteRoom($room->getItemId());
        $this->evictLegacyCache($room->getItemId());

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::DELETE, $room->getItemId()),
            'A room that is already soft-deleted cannot be deleted again',
        );
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

}
