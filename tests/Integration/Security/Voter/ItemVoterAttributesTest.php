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
use App\Entity\User;
use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for the smaller status-based ItemVoter attributes:
 *   - ITEM_NEW       — can the user create new content in the current room?
 *   - ITEM_PARTICIPATE — can the user be assigned tasks/participate (status >= 2 except guest/requested)?
 *   - ITEM_ANNOTATE  — can the user write annotations (status 2 or 3, not RO)?
 *   - ITEM_OWN       — is the user the creator of the given item?
 *
 * These all branch only on the user's status (and, for NEW/PARTICIPATE/
 * ANNOTATE, the room's archived flag). They DON'T delegate to legacy
 * mayEdit/maySee, so they are the simplest paths to extract in Phase 2.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterAttributesTest extends KernelTestCase
{
    use BootsVoter;

    // ---- ITEM_NEW: "can the current user create new content here?" The
    //      voter has NO subject — it reads currentRoom from legacyEnv and
    //      checks: not guest, not RO, not requested, room not archived.

    public function testRegularMemberCanCreateNewContent(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(ItemVoter::NEW));
    }

    public function testReadOnlyUserCannotCreateNewContent(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 4);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::NEW));
    }

    public function testRequestedUserCannotCreateNewContent(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 1);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::NEW));
    }

    public function testArchivedRoomBlocksCreateNewContent(): void
    {
        $room = $this->createProjectRoom(archived: true);
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::NEW));
    }

    // ---- ITEM_PARTICIPATE: status in {2, 3, 4} (user, mod, RO) AND room
    //      not archived. Used for todo assignments / discussion replies.

    public function testRegularUserCanParticipate(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertTrue($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $material->getItemId()));
    }

    public function testReadOnlyUserCanParticipate(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 4);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::PARTICIPATE, $material->getItemId()),
            'Read-only users CAN participate (e.g. be added to a todo) — only writing is blocked',
        );
    }

    public function testRequestedUserCannotParticipate(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 1);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $material->getItemId()));
    }

    public function testArchivedRoomBlocksParticipate(): void
    {
        $room = $this->createProjectRoom(archived: true);
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::PARTICIPATE, $material->getItemId()));
    }

    // ---- ITEM_ANNOTATE: status in {2, 3} only AND room not archived.
    //      RO users CANNOT annotate (status 4 not in [2,3]).

    public function testRegularUserCanAnnotate(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertTrue($this->authChecker->isGranted(ItemVoter::ANNOTATE, $material->getItemId()));
    }

    public function testReadOnlyUserCannotAnnotate(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 4);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::ANNOTATE, $material->getItemId()),
            'RO users (status 4) cannot annotate — annotations require write status (2 or 3)',
        );
    }

    public function testArchivedRoomBlocksAnnotate(): void
    {
        $room = $this->createProjectRoom(archived: true);
        $member = $this->createMember($this->portalAccount, $room, 2);
        $this->loginAs($this->portalAccount, $room);

        $material = $this->createMaterial($room, $member);

        self::assertFalse($this->authChecker->isGranted(ItemVoter::ANNOTATE, $material->getItemId()));
    }

    // ---- ITEM_OWN: simple equality check on creator id vs current user
    //      itemId. Used by form types to gate moderation-only fields.

    public function testCreatorOwnsItem(): void
    {
        $room = $this->createProjectRoom();
        $author = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $author,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue($this->authChecker->isGranted(ItemVoter::OWN, $material->getItemId()));
    }

    public function testNonCreatorDoesNotOwnItem(): void
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

        self::assertFalse($this->authChecker->isGranted(ItemVoter::OWN, $material->getItemId()));
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

    /**
     * AttributesTest variants need an `archived` flag — that's the only
     * state combination a generic trait helper wouldn't cover, so we keep
     * a small wrapper here. All other room types use the trait helpers.
     */
    private function createProjectRoom(bool $archived = false): Room
    {
        $factory = RoomFactory::new()->project();
        if ($archived) {
            $factory = $factory->archived();
        }
        return $factory->create($this->roomDefaults());
    }

    /**
     * Creates a material whose creator is given as a User entity. Tests
     * that don't care about ownership just hand in the membership they
     * already created via {@see createMember()}.
     */
    private function createMaterial(Room $room, User $creator): \App\Entity\Materials
    {
        return MaterialFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);
    }
}
