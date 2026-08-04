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

use App\Rubric\Material\MaterialDeleter;
use App\Security\Authorization\Voter\ItemVoter;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Factory\PortalFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for ItemVoter::SEE. Pins behavior of cs_item::maySee
 * before extraction into App\Security\Permission\Checker\ItemViewChecker
 * (Phase 2.4).
 *
 * The voter's SEE path:
 *   - Account=root short-circuit (top-level)
 *   - canView() guards on $item->isDeleted() FIRST, then delegates to maySee()
 *
 * cs_item::maySee() denies if the context is deleted, allows root, allows
 * room users (status >= 2) on activated items, allows moderators / creators
 * on not-yet-activated items, falls back to external-viewer DB lookup, and
 * finally allows guests/requested users when the current context is open
 * for guests AND the item is activated.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterSeeTest extends KernelTestCase
{
    use BootsVoter;

    public function testRootAccountCanSeeAnyMaterial(): void
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
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'Account.username="root" short-circuits SEE to true',
        );
    }

    public function testRegularMemberCanSeeMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'A regular member of the room can see materials in it',
        );
    }

    public function testReadOnlyMemberCanSeeMaterial(): void
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

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'Read-only members can see content (cs_user_item.isUser() is status >= 2 which includes 4)',
        );
    }

    public function testNonMemberCannotSeeMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $nonMember = $this->createPortalAccount();
        $this->loginAs($nonMember);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'A non-member of the room cannot see its content',
        );
    }

    public function testRequestedMemberCannotSeeMaterial(): void
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

        // cs_item::maySee uses isUser() which is status >= 2; status 1
        // (requested) does not pass.
        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'Requested members cannot see room content',
        );
    }

    public function testNobodyCanSeeSoftDeletedMaterial(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        self::getContainer()->get(MaterialDeleter::class)
            ->softDeleteItem($material->getItemId(), $this->portalAccount->getId());

        $this->evictLegacyCache($material->getItemId());

        // Sanity: confirm the soft-delete actually landed on items.deletion_date.
        $row = $this->dbConnection()->fetchAssociative(
            'SELECT deletion_date FROM items WHERE item_id = ?',
            [$material->getItemId()]
        );
        self::assertNotNull(
            $row['deletion_date'] ?? null,
            'MaterialDeleter must set items.deletion_date'
        );

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            sprintf(
                'Voter::canView() guards on $item->isDeleted() before maySee(). DB deletion_date=%s',
                $row['deletion_date']
            ),
        );
    }

    // ---- External viewer feature (whitespace-separated user_ids on items
    //      in private rooms) — see cs_item::mayExternalSee + external_viewer
    //      table. Even non-members can SEE the item if their username is
    //      registered as external viewer.

    public function testExternalViewerCanSeeItemInPrivateRoom(): void
    {
        $privateRoom = $this->createPrivateRoom();
        $owner = $this->createMember($this->portalAccount, $privateRoom, 3);

        // Create the external viewer first so we can pass their username
        // straight into the factory; MaterialFactory persists the
        // external_viewer rows via the legacy save() pipeline.
        $externalAccount = $this->createPortalAccount();
        $material = MaterialFactory::createOne([
            'room' => $privateRoom,
            'creator' => $owner,
            'externalViewers' => [$externalAccount->getUsername()],
        ]);

        // External viewer is not a member of the private room — log them in
        // at portal level (no room context).
        $this->loginAs($externalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'External viewer (registered username) can see the item even without room membership',
        );
    }

    public function testNonExternalViewerCannotSeeItemInPrivateRoom(): void
    {
        $privateRoom = $this->createPrivateRoom();
        $owner = $this->createMember($this->portalAccount, $privateRoom, 3);
        $material = MaterialFactory::createOne([
            'room' => $privateRoom,
            'creator' => $owner,
        ]);

        $strangerAccount = $this->createPortalAccount();
        // Note: NO external viewer entry for this account.

        $this->loginAs($strangerAccount);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'A non-member who is not in the external_viewer table cannot see private-room items',
        );
    }

    // ---- isNotActivated: deactivated entries (activation_date in future)
    //      are visible only to moderators or to their creator.

    public function testModeratorCanSeeNotYetActivatedMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
            'activationDate' => new \DateTimeImmutable('+1 day'),
        ]);

        $modAccount = $this->createPortalAccount();
        $this->createMember($modAccount, $room, 3);
        $this->loginAs($modAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'Moderators can see not-yet-activated entries (mayDeactivate-and-still-see)',
        );
    }

    public function testCreatorCanSeeOwnNotYetActivatedMaterial(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
            'activationDate' => new \DateTimeImmutable('+1 day'),
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'The creator of a not-yet-activated entry can still see it',
        );
    }

    public function testRegularMemberCannotSeeOthersNotYetActivatedMaterial(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
            'activationDate' => new \DateTimeImmutable('+1 day'),
        ]);

        $otherAccount = $this->createPortalAccount();
        $this->createMember($otherAccount, $room, 2);
        $this->loginAs($otherAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'Regular members do NOT see not-yet-activated entries authored by others',
        );
    }

    public function testCannotSeeMaterialWhoseRoomIsDeleted(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        // Soft-delete the room (context). cs_item::maySee starts with:
        //   if (null === $contextItem || $contextItem->isDeleted()) return false;
        $this->softDeleteRoom($room->getItemId());

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::SEE, $material->getItemId()),
            'maySee() short-circuits to false when the context room is deleted',
        );
    }

    // ---- SEE on PORTAL-level user items is currently UNUSABLE.
    //
    //      This is a defect, pinned here rather than endorsed. ItemVoter::EDIT
    //      handles the same subjects fine (see ItemVoterEditTest), so nothing
    //      but SEE is affected — and no production caller asks SEE for a
    //      portal-level user today, which is why it has gone unnoticed.
    //
    //      When the mapping is fixed, replace this with the real assertions:
    //      moderator sees a user of their own portal, and — since the read
    //      path never compares portals — a user of a foreign portal too.

    public function testSeeOnAPortalLevelUserItemThrowsBecauseItsContextIsNotARoom(): void
    {
        $targetAccount = $this->createPortalAccount();
        $targetUserId = $this->portalUserItemId($targetAccount);

        $modAccount = $this->createPortalAccount();
        $this->promoteToPortalModerator($modAccount);
        $this->loginAs($modAccount);

        // App\Entity\User::$room is mapped onto `context_id`. For a portal-level
        // user that column holds a PORTAL id, so the association points at a
        // Room row that does not exist. UserViewChecker hands the target to
        // ItemViewSubjectFactory, whose isContextDeleted() resolves that id
        // through RoomRepository; the uninitialised proxy throws on first
        // access. The portal comparison is never reached.
        $this->expectException(EntityNotFoundException::class);

        $this->authChecker->isGranted(ItemVoter::SEE, $targetUserId);
    }

    public function testSeeOnAForeignPortalUserItemFailsTheSameWay(): void
    {
        $foreignPortal = PortalFactory::createOne();
        $foreignAccount = $this->createPortalAccount(portal: $foreignPortal);
        $foreignUserId = $this->portalUserItemId($foreignAccount);

        $modAccount = $this->createPortalAccount();
        $this->promoteToPortalModerator($modAccount);
        $this->loginAs($modAccount);

        // Same crash, different portal — proof the failure is about the
        // context mapping, not about the portal boundary.
        $this->expectException(EntityNotFoundException::class);

        $this->authChecker->isGranted(ItemVoter::SEE, $foreignUserId);
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
