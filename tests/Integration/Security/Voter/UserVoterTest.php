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

use App\Security\Authorization\Voter\UserVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for UserVoter (4 attributes):
 *   - MODERATOR              → currentUserItem.isModerator() (status === 3 in current context)
 *   - PORTAL_MODERATOR       → portal-level user_item is moderator
 *   - ROOM_MODERATOR         → user has a room user_item with status=3 in {subject = roomId}
 *   - PARENT_ROOM_MODERATOR  → portal mod, root, or community-mod owning the project (etc.)
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class UserVoterTest extends KernelTestCase
{
    use BootsVoter;

    // ---------- MODERATOR (current context) ----------

    public function testCurrentRoomModeratorIsGrantedModerator(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(UserVoter::MODERATOR));
    }

    public function testRegularMemberIsNotGrantedModerator(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(UserVoter::MODERATOR));
    }

    // ---------- PORTAL_MODERATOR ----------

    public function testPortalModeratorIsGrantedPortalModerator(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        self::assertTrue($this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR));
    }

    public function testRegularUserIsNotGrantedPortalModerator(): void
    {
        $this->loginAs($this->portalAccount);

        self::assertFalse($this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR));
    }

    // ---------- ROOM_MODERATOR ----------

    public function testRoomModeratorIsGrantedForOwnRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 3);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(UserVoter::ROOM_MODERATOR, $room->getItemId()),
        );
    }

    public function testRoomModeratorDeniedForNonModeratorMember(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::ROOM_MODERATOR, $room->getItemId()),
        );
    }

    public function testRoomModeratorDeniedForNonExistentRoom(): void
    {
        $this->loginAs($this->portalAccount);

        // Non-existent room id → roomService returns null → voter returns false.
        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::ROOM_MODERATOR, 999_999),
        );
    }

    // ---------- PARENT_ROOM_MODERATOR ----------

    public function testPortalModeratorIsParentModeratorForAnyProjectRoom(): void
    {
        $room = $this->createProjectRoom();
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(UserVoter::PARENT_ROOM_MODERATOR, $room->getItemId()),
            'Portal moderators are considered parent moderators of every project room',
        );
    }

    public function testRegularUserIsNotParentModerator(): void
    {
        $room = $this->createProjectRoom();
        $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::PARENT_ROOM_MODERATOR, $room->getItemId()),
        );
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
