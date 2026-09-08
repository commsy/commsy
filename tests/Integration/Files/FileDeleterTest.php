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
use Tests\Factory\FilesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see FileDeleter::softDeleteFile()} — the replacement for the legacy
 * `cs_file_item::delete()` cascade used by the FileList live component —
 * and {@see FileDeleter::softDeleteUnlinkedFiles()}, the nightly sweep for
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
    public function testSoftDeleteFileMarksFilesRowAsDeleted(): void
    {
        $fileId = $this->createFile();

        $this->fileDeleter->softDeleteFile($fileId, $this->deleterId);

        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM files WHERE files_id = :id',
            ['id' => $fileId]
        );
        self::assertIsArray($row);
        self::assertNotNull($row['deletion_date']);
        self::assertSame($this->deleterId, (int) $row['deleter_id']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteFileSoftDeletesEveryItemLinkFileRow(): void
    {
        $fileId = $this->createFile();
        $otherFileId = $this->createFile();

        $this->createItemLinkFile($fileId, itemId: 1001, versionId: 1);
        $this->createItemLinkFile($fileId, itemId: 1001, versionId: 2);
        $this->createItemLinkFile($otherFileId, itemId: 1001, versionId: 1);

        $this->fileDeleter->softDeleteFile($fileId, $this->deleterId);

        $this->assertItemLinkFileSoftDeleted($fileId, itemId: 1001, versionId: 1);
        $this->assertItemLinkFileSoftDeleted($fileId, itemId: 1001, versionId: 2);
        $this->assertItemLinkFileAlive($otherFileId, itemId: 1001, versionId: 1);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteFileDoesNotAffectOtherFiles(): void
    {
        $target = $this->createFile();
        $bystander = $this->createFile();

        $this->fileDeleter->softDeleteFile($target, $this->deleterId);

        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM files WHERE files_id = :id',
            ['id' => $bystander]
        );
        self::assertIsArray($row);
        self::assertNull($row['deletion_date']);
        self::assertNull($row['deleter_id']);
    }

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
    public function testSoftDeleteFileLinkCanStampASingleAttachment(): void
    {
        $target = $this->createFile();
        $sibling = $this->createFile();
        $this->createItemLinkFile($target, itemId: 1001, versionId: 1);
        $this->createItemLinkFile($sibling, itemId: 1001, versionId: 1);

        $this->fileDeleter->softDeleteFileLink(1001, 1, $this->deleterId, $target);

        $this->assertItemLinkFileSoftDeleted($target, itemId: 1001, versionId: 1);
        $this->assertItemLinkFileAlive($sibling, itemId: 1001, versionId: 1);
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
