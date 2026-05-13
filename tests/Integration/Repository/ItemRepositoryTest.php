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

namespace Tests\Integration\Repository;

use App\Entity\Items;
use App\Repository\ItemRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ItemRepositoryTest extends KernelTestCase
{
    private ItemRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ItemRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testGetNumItemsReturnsOneForExistingItem(): void
    {
        $itemId = $this->insertItem(70_001, type: 'material', pinned: false);

        self::assertSame(1, $this->repository->getNumItems($itemId));
    }

    public function testGetPinnedItemsByRoomIdReturnsPinnedAliveItems(): void
    {
        $roomId = 70_010;

        $pinned = $this->insertItem($roomId, 'material', pinned: true);
        $unpinned = $this->insertItem($roomId, 'material', pinned: false);
        $deleted = $this->insertItem($roomId, 'material', pinned: true, softDeleted: true);

        $items = $this->repository->getPinnedItemsByRoomId($roomId);
        $ids = array_map(static fn ($i): int => $i->getItemId(), $items);

        self::assertContains($pinned, $ids);
        self::assertNotContains($unpinned, $ids, 'unpinned items must be excluded');
        self::assertNotContains($deleted, $ids, 'soft-deleted items must be excluded');
    }

    public function testGetPinnedItemsByRoomIdAndTypeFiltersByType(): void
    {
        $roomId = 70_020;

        $material = $this->insertItem($roomId, 'material', pinned: true);
        $announcement = $this->insertItem($roomId, 'announcement', pinned: true);

        $materials = $this->repository->getPinnedItemsByRoomIdAndType($roomId, ['material']);
        $ids = array_map(static fn ($i): int => $i->getItemId(), $materials);

        self::assertContains($material, $ids);
        self::assertNotContains($announcement, $ids);
    }

    public function testGetPinnedItemsByRoomIdAndTypeWithEmptyTypesReturnsAll(): void
    {
        $roomId = 70_030;

        $a = $this->insertItem($roomId, 'material', pinned: true);
        $b = $this->insertItem($roomId, 'announcement', pinned: true);

        $items = $this->repository->getPinnedItemsByRoomIdAndType($roomId, []);
        $ids = array_map(static fn ($i): int => $i->getItemId(), $items);

        self::assertContains($a, $ids);
        self::assertContains($b, $ids);
    }

    private function insertItem(
        int $contextId,
        string $type,
        bool $pinned = false,
        bool $softDeleted = false,
    ): int {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->insert('items', [
            'context_id' => $contextId,
            'type' => $type,
            'modification_date' => $now,
            'pinned' => $pinned ? 1 : 0,
            'draft' => 0,
            'deleter_id' => $softDeleted ? 1 : null,
            'deletion_date' => $softDeleted ? $now : null,
        ]);
        return (int) $this->connection->lastInsertId();
    }
}
