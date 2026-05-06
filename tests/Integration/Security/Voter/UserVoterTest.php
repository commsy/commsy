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
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for UserVoter (4 attributes):
 *   - MODERATOR              → currentUserItem.isModerator() (status === 3 in current context)
 *   - PORTAL_MODERATOR       → portal-level user_item is moderator AND portal is alive;
 *                              also accepts a Portal entity / portal-id as subject for a
 *                              cross-portal check (the user must act in their own portal)
 *   - ROOM_MODERATOR         → user has a room user_item with status=3 in {subject = roomId}
 *   - PARENT_ROOM_MODERATOR  → portal mod, root, or community-mod owning the project (etc.)
 *
 * UserVoter also short-circuits to true for `Account.username === 'root'` — the
 * same shortcut ItemVoter has at the top level. Replaces the deleted
 * PortalModeratorVoter, whose logic the deletion-date check now lives here.
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

    public function testPortalModeratorWithMatchingPortalSubjectIsGranted(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(
                UserVoter::PORTAL_MODERATOR,
                $this->portalAccount->getPortal(),
            ),
            'Passing the user\'s own portal as subject must still grant',
        );
    }

    public function testPortalModeratorWithMatchingPortalIdSubjectIsGranted(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(
                UserVoter::PORTAL_MODERATOR,
                $this->portalAccount->getPortal()?->getId(),
            ),
            'Passing the user\'s own portal id as subject must still grant',
        );
    }

    /**
     * Bug 2 fix: prior to consolidating PORTAL_MODERATOR into UserVoter,
     * the deletion-date guard lived in PortalModeratorVoter and was
     * effectively dead code (UserVoter granted unconditionally for any
     * portal-mod user). After consolidation, the deletion check applies
     * uniformly — a portal moderator does NOT keep their grant when the
     * portal itself is soft-deleted.
     */
    public function testDeletedPortalRevokesPortalModerator(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $portal = $this->portalAccount->getPortal();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $portal->setDeletionDate(new \DateTime());
        $em->flush();

        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR, $portal),
            'A portal moderator must lose PORTAL_MODERATOR rights when the portal is soft-deleted',
        );
        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR),
            'Same outcome when no subject is passed — the user\'s own portal is checked',
        );
    }

    /**
     * Cross-portal guard: a portal moderator on portal A must NOT be granted
     * PORTAL_MODERATOR when the subject is portal B. Pre-consolidation this
     * relied on an upstream PortalSettingsController guard rather than the
     * voter itself; consolidating into UserVoter makes the check explicit so
     * any future caller is automatically protected.
     */
    public function testPortalModeratorWithForeignPortalSubjectIsDenied(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $foreignPortal = PortalFactory::createOne();

        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR, $foreignPortal),
            'Portal moderator on portal A must not be granted PORTAL_MODERATOR for portal B',
        );
        self::assertFalse(
            $this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR, $foreignPortal->getId()),
            'Same outcome when the foreign portal is passed by id',
        );
    }

    public function testRootAccountShortCircuitsPortalModeratorEvenWhenPortalIsDeleted(): void
    {
        $this->setSecurityToken($this->createPortalAccount('root'));

        $portal = $this->portalAccount->getPortal();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $portal->setDeletionDate(new \DateTime());
        $em->flush();

        self::assertTrue(
            $this->authChecker->isGranted(UserVoter::PORTAL_MODERATOR, $portal),
            'Account.username="root" short-circuits all UserVoter attributes',
        );
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
