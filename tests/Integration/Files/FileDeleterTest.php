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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\FilesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Integration coverage for {@see FileDeleter::softDeleteFile()} — the
 * replacement for the legacy `cs_file_item::delete()` cascade used by the
 * FileList live component.
 *
 * Pins down the contract of the soft-delete path:
 *  - `files` row marked as soft-deleted (deletion_date + deleter_id).
 *  - `item_link_file` rows referencing this file soft-deleted (in both
 *    `file_id` directions — every attachment of any item version).
 *  - Other files and their attachments stay untouched.
 *
 * Physical removal (`files` row + disk file) is out of scope: that will
 * land with the FileHardDeleter, tracked as a separate ticket.
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

    private function createFile(): int
    {
        $file = FilesFactory::createOne([
            'contextId' => $this->room->getItemId(),
        ]);

        return $file->getFilesId();
    }

    /**
     * `item_link_file` has a composite PK (item_iid, item_vid, file_id) —
     * no surrogate id column. Rows are addressed by that triple in the
     * assertions below.
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
