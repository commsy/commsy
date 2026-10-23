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
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\FilesFactory;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Saving a shorter file list through {@see \cs_item::_saveFileLinks()}
 * detaches the dropped files from that version and ends the ones nothing
 * else carries, so no file is left behind without a live link.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ItemFileLinkSaveTest extends KernelTestCase
{
    private Connection $connection;
    private int $materialId;
    private int $roomId;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');
        $this->roomId = $room->getItemId();
        $this->materialId = (int) MaterialFactory::createOne(['room' => $room, 'creator' => $creator])->getItemId();
    }

    public function testADroppedFileEndsWhenNothingElseCarriesIt(): void
    {
        $kept = $this->createFile();
        $dropped = $this->createFile();
        $this->linkFile($kept, 0);
        $this->linkFile($dropped, 0);

        $this->saveFileList(0, [$kept]);

        self::assertNull($this->linkDeletionDate($kept, 0));
        self::assertNotNull($this->linkDeletionDate($dropped, 0));
        self::assertNull($this->fileDeletionDate($kept));
        self::assertNotNull($this->fileDeletionDate($dropped));
    }

    public function testADroppedFileAnotherVersionCarriesStays(): void
    {
        $shared = $this->createFile();
        $this->linkFile($shared, 0);
        $this->linkFile($shared, 1);

        $this->saveFileList(1, []);

        self::assertNotNull($this->linkDeletionDate($shared, 1));
        self::assertNull($this->linkDeletionDate($shared, 0));
        self::assertNull($this->fileDeletionDate($shared));
    }

    /**
     * @param int[] $fileIds
     */
    private function saveFileList(int $versionId, array $fileIds): void
    {
        /** @var ItemService $itemService */
        $itemService = self::getContainer()->get(ItemService::class);
        $item = $itemService->getTypedItem($this->materialId);
        self::assertNotNull($item);
        $item->setVersionID($versionId);
        $item->setFileIDArray($fileIds);

        $item->_saveFileLinks();
    }

    private function createFile(): int
    {
        return FilesFactory::createOne(['contextId' => $this->roomId])->getFilesId();
    }

    private function linkFile(int $fileId, int $versionId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO item_link_file (file_id, item_iid, item_vid) VALUES (?, ?, ?)',
            [$fileId, $this->materialId, $versionId]
        );
    }

    private function linkDeletionDate(int $fileId, int $versionId): ?string
    {
        $value = $this->connection->fetchOne(
            'SELECT deletion_date FROM item_link_file WHERE file_id = ? AND item_iid = ? AND item_vid = ?',
            [$fileId, $this->materialId, $versionId]
        );

        return false === $value ? null : $value;
    }

    private function fileDeletionDate(int $fileId): ?string
    {
        $value = $this->connection->fetchOne('SELECT deletion_date FROM files WHERE files_id = ?', [$fileId]);

        return false === $value ? null : $value;
    }
}
