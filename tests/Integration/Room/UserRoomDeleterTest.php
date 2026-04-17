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

namespace Tests\Integration\Room;

use App\Entity\Account;
use App\Entity\Announcement;
use App\Entity\Room;
use App\Entity\User;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use App\Room\UserRoomDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see UserRoomDeleter}.
 *
 * User rooms are the simplest room flavour — no sub-rooms, no portal or
 * community-link bookkeeping, no moderation mails — so the assertion suite
 * stays focused on the core contract shared by every RoomDeleter:
 *   - the room row and its items twin are soft-deleted,
 *   - rubric content inside the room is soft-deleted (orchestrated via
 *     {@see \App\Room\RoomContentDeleter}),
 *   - user memberships in the room are soft-deleted,
 *   - idempotent and isolated from rooms that happen to share a portal.
 */
final class UserRoomDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private UserRoomDeleter $deleter;
    private Account $account;
    private int $deleterId;

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomAndItemsRows(): void
    {
        $room = $this->createUserRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $room->getItemId());
        $this->assertSoftDeleted('items', $room->getItemId());
    }

    /**
     * Every rubric item inside the user room must end up soft-deleted too,
     * driven by {@see \App\Room\RoomContentDeleter}. This is the key
     * difference to the legacy cascade: items whose creator is *not* a
     * member of the room at delete-time would previously stay orphaned.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoRubricContent(): void
    {
        $room = $this->createUserRoom();
        $member = $this->createMembership($room);

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('announcement', $announcement->getItemId());
        $this->assertSoftDeleted('items', $announcement->getItemId());
    }

    /**
     * Memberships (cs_user_item rows whose `context_id` = the room) must be
     * soft-deleted. This is the room-scope user row, not the portal-level
     * account — deleting the user room must not touch the account itself.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomMemberships(): void
    {
        $room = $this->createUserRoom();
        $member = $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('user', $member->getItemId());
        $this->assertSoftDeleted('items', $member->getItemId());
    }

    /**
     * Calling the deleter twice must be a no-op on the second run — the
     * auto-abandon subscriber in particular can fire on a room mid-delete.
     * Pinning this down protects against double-counted deletion_date
     * bumps / duplicate event dispatches once that path is live.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteIsIdempotent(): void
    {
        $room = $this->createUserRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());
        $firstDate = $this->connection->fetchOne(
            'SELECT deletion_date FROM room WHERE item_id = :id',
            ['id' => $room->getItemId()]
        );

        // Intentionally call with a different deleter id — if the second run
        // were to slip through, the stored deleter_id would change.
        $this->deleter->softDeleteRoom($room->getItemId(), 999999, RoomDeletionOptions::forUserAction());

        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM room WHERE item_id = :id',
            ['id' => $room->getItemId()]
        );
        self::assertSame($firstDate, $row['deletion_date'], 'deletion_date must not change on re-delete');
        self::assertSame($this->deleterId, (int) $row['deleter_id'], 'deleter_id must stay pinned to the first caller');
    }

    /**
     * Regression safety net: deleting one user room must not affect another
     * room in the same portal. Guards against a missing WHERE clause or a
     * context-wide UPDATE that would only surface in production.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDoesNotAffectOtherRooms(): void
    {
        $target = $this->createUserRoom();
        $bystander = $this->createUserRoom();
        $this->createMembership($target);
        $bystanderMember = $this->createMembership($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertNotSoftDeleted('room', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
        $this->assertNotSoftDeleted('user', $bystanderMember->getItemId());
    }

    public function testRoomTypeIsUserRoom(): void
    {
        self::assertSame(RoomType::UserRoom, $this->deleter->roomType());
    }

    /**
     * Happy-path hard-delete: following the realistic production flow
     * (soft-delete → wait out grace period → hard-delete), the `room`
     * row and its rubric content must be physically removed, while a
     * bystander room in the same portal must remain entirely untouched.
     *
     * Filesystem removal via {@see \cs_disc_manager::removeRoomDir()} is
     * exercised by the call graph but not asserted on — `removeRoomDir`
     * no-ops on a missing directory (the test fixtures create no files),
     * so there is nothing to check on disk. Dedicated FS coverage lives
     * in {@see \Tests\Unit\Database\FixPhysicalFilesTest}.
     */
    #[WithStory(AccountStory::class)]
    public function testHardDeleteRemovesRoomAndContentPhysically(): void
    {
        $target = $this->createUserRoom();
        $member = $this->createMembership($target);
        $announcement = AnnouncementFactory::createOne([
            'room' => $target,
            'creator' => $member,
        ]);

        $bystander = $this->createUserRoom();
        $bystanderMember = $this->createMembership($bystander);

        // Soft-delete first so the room actually sits in the post-grace
        // state that a real hard-delete pass would encounter. Calling
        // hardDeleteRoom() directly here (rather than via
        // RoomHardDeleter::hardDeleteRoomsOlderThan) keeps the assertion
        // scope on the per-type cascade — the orchestrator's threshold
        // query is trivial DBAL and does not need integration coverage.
        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());
        $this->deleter->hardDeleteRoom($target->getItemId());

        // Target physically gone: `room` row + the rubric content row +
        // its items-twin + the membership row.
        $this->assertPhysicallyDeleted('room', $target->getItemId());
        $this->assertPhysicallyDeleted('announcement', $announcement->getItemId());
        $this->assertPhysicallyDeleted('items', $announcement->getItemId());
        $this->assertPhysicallyDeleted('user', $member->getItemId());

        // Bystander must still be alive — catches a missing WHERE clause
        // or a context-wide DELETE hitting rows outside the target room.
        $this->assertNotSoftDeleted('room', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
        $this->assertNotSoftDeleted('user', $bystanderMember->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(UserRoomDeleter::class);

        $this->account = AccountStory::get('account');
        $this->deleterId = $this->account->getId();
    }

    private function createUserRoom(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::UserRoom->value,
        ]);
    }

    private function createMembership(Room $room): User
    {
        return RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
        ]);
    }

    private function assertSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNotNull($row['deletion_date'], sprintf('%s row %d must have deletion_date set', $table, $itemId));
        self::assertSame(
            $this->deleterId,
            (int) $row['deleter_id'],
            sprintf('%s row %d must record the correct deleter_id', $table, $itemId)
        );
    }

    private function assertPhysicallyDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT item_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertFalse($row, sprintf('%s row %d must be physically removed', $table, $itemId));
    }

    private function assertNotSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNull($row['deletion_date'], sprintf('%s row %d must still be alive', $table, $itemId));
        self::assertNull($row['deleter_id'], sprintf('%s row %d must have no deleter_id', $table, $itemId));
    }
}
