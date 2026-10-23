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

namespace Tests\Integration\Files;

use App\Entity\Room;
use App\Entity\User;
use App\Files\FileDeleter;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\FilesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see FileDeleter::detachFromItem()} — letting go of an entry's
 * files per version and ending the ones without a live link left — and
 * {@see FileDeleter::softDeleteUnlinkedFiles()}, the nightly sweep for
 * uploads that never reached an entry.
 */
final class FileDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private FileDeleter $fileDeleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testDetachEndsAFileWhoseLastCarrierIsGone(): void
    {
        $file = $this->createFile();
        $this->createItemLinkFile($file, itemId: 1001, versionId: 0);

        $this->fileDeleter->detachFromItem(1001, $this->deleterId);

        self::assertNotNull($this->fileRow($file)['deletion_date']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDetachSparesAFileAnotherEntryStillCarries(): void
    {
        $file = $this->createFile();
        $this->createItemLinkFile($file, itemId: 1001, versionId: 0);
        $this->createItemLinkFile($file, itemId: 1002, versionId: 0);

        $this->fileDeleter->detachFromItem(1001, $this->deleterId);

        $this->assertItemLinkFileSoftDeleted($file, itemId: 1001, versionId: 0);
        $this->assertItemLinkFileAlive($file, itemId: 1002, versionId: 0);
        self::assertNull(
            $this->fileRow($file)['deletion_date'],
            'The file is still reachable through the other entry',
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDetachingOneFileFromOneVersionKeepsItWhereverElseItIsLinked(): void
    {
        $shared = $this->createFile();
        $sibling = $this->createFile();
        $this->createItemLinkFile($shared, itemId: 1001, versionId: 0);
        $this->createItemLinkFile($shared, itemId: 1001, versionId: 1);
        $this->createItemLinkFile($sibling, itemId: 1001, versionId: 1);

        $this->fileDeleter->detachFromItem(1001, $this->deleterId, 1, $shared);

        $this->assertItemLinkFileSoftDeleted($shared, itemId: 1001, versionId: 1);
        $this->assertItemLinkFileAlive($shared, itemId: 1001, versionId: 0);
        $this->assertItemLinkFileAlive($sibling, itemId: 1001, versionId: 1);
        self::assertNull($this->fileRow($shared)['deletion_date'], 'The older version still carries the file');
        self::assertNull($this->fileRow($sibling)['deletion_date']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDetachingTheLastLinkOfOneFileEndsIt(): void
    {
        $target = $this->createFile();
        $sibling = $this->createFile();
        $this->createItemLinkFile($target, itemId: 1001, versionId: 1);
        $this->createItemLinkFile($sibling, itemId: 1001, versionId: 1);

        $this->fileDeleter->detachFromItem(1001, $this->deleterId, 1, $target);

        self::assertNotNull($this->fileRow($target)['deletion_date']);
        $this->assertItemLinkFileAlive($sibling, itemId: 1001, versionId: 1);
        self::assertNull($this->fileRow($sibling)['deletion_date']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDetachingAFileTheItemDoesNotCarryChangesNothing(): void
    {
        $foreign = $this->createFile();
        $this->createItemLinkFile($foreign, itemId: 1002, versionId: 0);
        $orphan = $this->createFile();

        $this->fileDeleter->detachFromItem(1001, $this->deleterId, 0, $foreign);
        $this->fileDeleter->detachFromItem(1001, $this->deleterId, 0, $orphan);

        $this->assertItemLinkFileAlive($foreign, itemId: 1002, versionId: 0);
        self::assertNull($this->fileRow($foreign)['deletion_date']);
        self::assertNull($this->fileRow($orphan)['deletion_date'], 'Only files of the item are ended here');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepStampsAnUploadThatNeverGotLinked(): void
    {
        $orphan = $this->createFile(new DateTime('-2 days'));

        $this->fileDeleter->softDeleteUnlinkedFiles();

        $row = $this->fileRow($orphan);
        self::assertNotNull($row['deletion_date']);
        self::assertNull($row['deleter_id'], 'The sweep has no person behind it');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepSparesAnUploadStillInFlight(): void
    {
        $fresh = $this->createFile(new DateTime());

        $this->fileDeleter->softDeleteUnlinkedFiles();

        self::assertNull(
            $this->fileRow($fresh)['deletion_date'],
            'A file younger than the guard may still be waiting for its link',
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepSparesAFileThatIsStillAttached(): void
    {
        $attached = $this->createFile(new DateTime('-2 days'));
        $this->createItemLinkFile($attached, itemId: 1001, versionId: 1);

        $this->fileDeleter->softDeleteUnlinkedFiles();

        self::assertNull($this->fileRow($attached)['deletion_date']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepSparesAFileWhoseLinksAreStamped(): void
    {
        $detached = $this->createFile(new DateTime('-2 days'));
        $this->createItemLinkFile($detached, itemId: 1001, versionId: 1);
        $this->connection->executeStatement(
            'UPDATE item_link_file SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE file_id = :fileId',
            ['deleterId' => $this->deleterId, 'fileId' => $detached]
        );

        $this->fileDeleter->softDeleteUnlinkedFiles();

        // linkFileByID() revives a stamped link when the same file is
        // attached again — the entry paths end such files, not this sweep.
        self::assertNull($this->fileRow($detached)['deletion_date']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepDoesNotRestartTheRetentionOfAnAlreadyDeletedFile(): void
    {
        $gone = $this->createFile(new DateTime('-2 days'));
        $this->connection->executeStatement(
            'UPDATE files SET deletion_date = :date WHERE files_id = :id',
            ['date' => '2020-01-01 00:00:00', 'id' => $gone]
        );

        $this->fileDeleter->softDeleteUnlinkedFiles();

        self::assertSame(
            '2020-01-01 00:00:00',
            $this->fileRow($gone)['deletion_date'],
            'Re-stamping would push the hard delete out by another retention period',
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSweepReportsHowManyFilesItStamped(): void
    {
        // Clear whatever the fixtures left behind, so the count is ours.
        $this->fileDeleter->softDeleteUnlinkedFiles();

        $this->createFile(new DateTime('-2 days'));
        $this->createFile(new DateTime('-2 days'));

        self::assertSame(2, $this->fileDeleter->softDeleteUnlinkedFiles());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testLinksOfGoneEntriesAreLetGoOf(): void
    {
        $alive = (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $deleted = (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $this->connection->executeStatement('UPDATE items SET deletion_date = NOW() - INTERVAL 200 DAY WHERE item_id = ?', [$deleted]);
        $restorable = (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $this->connection->executeStatement('UPDATE items SET deletion_date = NOW() - INTERVAL 10 DAY WHERE item_id = ?', [$restorable]);
        $vanished = 999_999_001;
        $portal = (int) $this->room->getPortal()?->getId();

        $onAlive = $this->createFile();
        $onDeleted = $this->createFile();
        $onVanished = $this->createFile();
        $onPortal = $this->createFile();
        $onRestorable = $this->createFile();
        $this->createItemLinkFile($onRestorable, itemId: $restorable, versionId: 0);
        $this->createItemLinkFile($onAlive, itemId: $alive, versionId: 0);
        $this->createItemLinkFile($onDeleted, itemId: $deleted, versionId: 0);
        $this->createItemLinkFile($onVanished, itemId: $vanished, versionId: 0);
        $this->createItemLinkFile($onPortal, itemId: $portal, versionId: 0);

        $fileIds = $this->fileDeleter->detachFromGoneEntries();

        sort($fileIds);
        self::assertSame([$onDeleted, $onVanished], $fileIds);
        $this->assertItemLinkFileAlive($onAlive, itemId: $alive, versionId: 0);
        $this->assertItemLinkFileAlive($onPortal, itemId: $portal, versionId: 0);
        $this->assertItemLinkFileAlive($onRestorable, itemId: $restorable, versionId: 0);
        self::assertNotNull($this->connection->fetchOne(
            'SELECT deletion_date FROM item_link_file WHERE file_id = ? AND item_iid = ?', [$onDeleted, $deleted]
        ));
        self::assertNotNull($this->connection->fetchOne(
            'SELECT deletion_date FROM item_link_file WHERE file_id = ? AND item_iid = ?', [$onVanished, $vanished]
        ));
        self::assertNull($this->fileRow($onDeleted)['deletion_date'], 'only the links are let go of here');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAFileMissingOnDiskEndsWithAllItsLinks(): void
    {
        $missing = $this->createFile();
        $bystander = $this->createFile();
        $this->createItemLinkFile($missing, itemId: 1001, versionId: 0);
        $this->createItemLinkFile($missing, itemId: 1001, versionId: 1);
        $this->createItemLinkFile($bystander, itemId: 1001, versionId: 1);

        $this->fileDeleter->endFilesMissingOnDisk([$missing]);

        self::assertNotNull($this->fileRow($missing)['deletion_date']);
        foreach ([0, 1] as $versionId) {
            self::assertNotNull($this->connection->fetchOne(
                'SELECT deletion_date FROM item_link_file WHERE file_id = ? AND item_vid = ?', [$missing, $versionId]
            ));
        }
        self::assertNull($this->fileRow($bystander)['deletion_date']);
        $this->assertItemLinkFileAlive($bystander, itemId: 1001, versionId: 1);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testEndingFilesWithoutLiveLinksTellsHowManyEnded(): void
    {
        $ended = $this->createFile();
        $kept = $this->createFile();
        $this->createItemLinkFile($kept, itemId: 1001, versionId: 0);

        self::assertSame(1, $this->fileDeleter->softDeleteFilesWithoutLiveLinks([$ended, $kept], null));
        self::assertNull($this->fileRow($ended)['deleter_id'], 'no person is behind it');
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->fileDeleter = self::getContainer()->get(FileDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createFile(?DateTime $creationDate = null): int
    {
        $file = FilesFactory::createOne(array_filter([
            'contextId' => $this->room->getItemId(),
            'creationDate' => $creationDate,
        ]));

        return $file->getFilesId();
    }

    /**
     * @return array<string, mixed>
     */
    private function fileRow(int $fileId): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM files WHERE files_id = :id',
            ['id' => $fileId]
        );
        self::assertIsArray($row);

        return $row;
    }

    /**
     * Composite PK (item_iid, item_vid, file_id); no surrogate id column.
     */
    private function createItemLinkFile(int $fileId, int $itemId, int $versionId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO item_link_file (file_id, item_iid, item_vid)
                VALUES (:fileId, :itemId, :versionId)',
            ['fileId' => $fileId, 'itemId' => $itemId, 'versionId' => $versionId]
        );
    }

    private function assertItemLinkFileSoftDeleted(int $fileId, int $itemId, int $versionId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM item_link_file
                WHERE file_id = :fileId AND item_iid = :itemId AND item_vid = :versionId',
            ['fileId' => $fileId, 'itemId' => $itemId, 'versionId' => $versionId]
        );
        self::assertIsArray($row);
        self::assertNotNull($row['deletion_date']);
        self::assertSame($this->deleterId, (int) $row['deleter_id']);
    }

    private function assertItemLinkFileAlive(int $fileId, int $itemId, int $versionId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM item_link_file
                WHERE file_id = :fileId AND item_iid = :itemId AND item_vid = :versionId',
            ['fileId' => $fileId, 'itemId' => $itemId, 'versionId' => $versionId]
        );
        self::assertIsArray($row);
        self::assertNull($row['deletion_date']);
        self::assertNull($row['deleter_id']);
    }
}
