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
use App\Entity\Room;
use App\Entity\User;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see PrivateRoomDeleter}.
 *
 * Structurally private rooms are a twin of user rooms (no sub-rooms, no
 * portal / community links, no moderation mails, not indexed in ES), so
 * the assertion suite mirrors {@see UserRoomDeleterTest}. Keeping the
 * tests separate rather than parameterised makes the type-specific
 * behaviour (`supports()`, `roomType()`) self-documenting.
 */
final class PrivateRoomDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private PrivateRoomDeleter $deleter;
    private Account $account;
    private int $deleterId;

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomAndItemsRows(): void
    {
        $room = $this->createPrivateRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $room->getItemId());
        $this->assertSoftDeleted('items', $room->getItemId());
    }

    /**
     * Regular call sites (AccountMerger) drain the private room first,
     * but DB-fix scripts and tests can hand over a non-empty room — the
     * orchestrator must still cascade into rubric content in that case.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoRubricContent(): void
    {
        $room = $this->createPrivateRoom();
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
     * The owner's `cs_user_item` row inside the private room must be
     * soft-deleted. The portal-level account on the `accounts` table
     * must stay untouched — account lifecycle is the caller's concern.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomMemberships(): void
    {
        $room = $this->createPrivateRoom();
        $member = $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('user', $member->getItemId());
        $this->assertSoftDeleted('items', $member->getItemId());
    }

    /**
     * Idempotency matters most here: AccountMerger and AccountDeleter
     * can both reach the same private room in quick succession when an
     * account is merged and then cleaned up.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteIsIdempotent(): void
    {
        $room = $this->createPrivateRoom();
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

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDoesNotAffectOtherRooms(): void
    {
        $target = $this->createPrivateRoom();
        $bystander = $this->createPrivateRoom();
        $this->createMembership($target);
        $bystanderMember = $this->createMembership($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertNotSoftDeleted('room', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
        $this->assertNotSoftDeleted('user', $bystanderMember->getItemId());
    }

    public function testRoomTypeIsPrivateRoom(): void
    {
        self::assertSame(RoomType::PrivateRoom, $this->deleter->roomType());
    }

    /**
     * Happy-path hard-delete: soft-delete → hard-delete physically
     * removes the `room` row, rubric content and membership row, while
     * leaving a bystander room untouched. See the same test in
     * {@see UserRoomDeleterTest} for the rationale around FS coverage
     * and the orchestrator scope boundary.
     */
    #[WithStory(AccountStory::class)]
    public function testHardDeleteRemovesRoomAndContentPhysically(): void
    {
        $target = $this->createPrivateRoom();
        $member = $this->createMembership($target);
        $announcement = AnnouncementFactory::createOne([
            'room' => $target,
            'creator' => $member,
        ]);

        $bystander = $this->createPrivateRoom();
        $bystanderMember = $this->createMembership($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());
        $this->deleter->hardDeleteRoom($target->getItemId());

        $this->assertPhysicallyDeleted('room', $target->getItemId());
        $this->assertPhysicallyDeleted('announcement', $announcement->getItemId());
        $this->assertPhysicallyDeleted('items', $announcement->getItemId());
        $this->assertPhysicallyDeleted('user', $member->getItemId());

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
        $this->deleter = self::getContainer()->get(PrivateRoomDeleter::class);

        $this->account = AccountStory::get('account');
        $this->deleterId = $this->account->getId();
    }

    private function createPrivateRoom(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::PrivateRoom->value,
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
