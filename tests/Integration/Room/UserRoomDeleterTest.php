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
use App\Files\FilesDirectory;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use App\Room\UserRoomDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins UserRoomDeleter against the shared RoomDeleter contract. User rooms
 * have no sub-rooms, no portal/community-link bookkeeping and no moderation
 * mails, so this is the baseline shape reused by the other room deleters.
 */
final class UserRoomDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private UserRoomDeleter $deleter;
    private Account $account;
    private int $deleterId;

    /** @var list<string> directories written by the test */
    private array $roomDirectories = [];

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
     * Legacy gap: items whose creator was not a member at delete-time
     * used to stay orphaned. RoomContentDeleter now sweeps them too.
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
     * Idempotency matters: the auto-abandon subscriber can fire on a
     * room mid-delete.
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

    /**
     * A user room's context is its project room, but its files lie below
     * the portal like those of every other room.
     */
    #[WithStory(AccountStory::class)]
    public function testHardDeleteRemovesTheFilesDirectoryBelowThePortal(): void
    {
        $project = RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::Project->value,
        ]);
        $target = $this->createUserRoom((int) $project->getItemId());
        $bystander = $this->createUserRoom((int) $project->getItemId());
        $targetFile = $this->roomFile($target);
        $bystanderFile = $this->roomFile($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());
        $this->deleter->hardDeleteRoom($target->getItemId());

        self::assertDirectoryDoesNotExist(\dirname($targetFile));
        self::assertFileExists($bystanderFile);
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

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->roomDirectories);
        parent::tearDown();
    }

    private function createUserRoom(?int $projectRoomId = null): Room
    {
        return RoomFactory::createOne([
            'contextId' => $projectRoomId ?? $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::UserRoom->value,
        ]);
    }

    /**
     * Writes a file into the room's files directory.
     */
    private function roomFile(Room $room): string
    {
        $path = self::getContainer()->get(FilesDirectory::class)
            ->contextDirectory((int) $this->account->getPortal()?->getId(), (int) $room->getItemId()).'/1.pdf';
        (new Filesystem())->dumpFile($path, 'content');
        $this->roomDirectories[] = \dirname($path);

        return $path;
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
