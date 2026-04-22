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

namespace Tests\Integration\Legacy;

use App\Legacy\LegacyAuxHardDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Pins the bulk-SQL sweep on {@see LegacyAuxHardDeleter} across `items`,
 * `link_items`, `tag`, `tag2tag`, `tasks`: expired rows go, grace-window and
 * alive rows stay. Also pins the `type != 'user'` carve-out — soft-deleted
 * user items must survive until membership-leave nullifies authorship refs.
 */
final class LegacyAuxHardDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private LegacyAuxHardDeleter $deleter;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(LegacyAuxHardDeleter::class);
    }

    public function testHardDeleteItemsRowsRemovesExpiredButPreservesUserType(): void
    {
        $expired = $this->insertItem('announcement', -40);
        $recent = $this->insertItem('announcement', -10);
        $alive = $this->insertItem('announcement', null);
        $expiredUser = $this->insertItem('user', -40);

        $this->deleter->hardDeleteItemsRows(30);

        self::assertFalse($this->itemExists($expired), 'expired announcement items row must be physically removed');
        self::assertTrue($this->itemExists($recent), 'recent soft-delete must survive the cutoff');
        self::assertTrue($this->itemExists($alive), 'alive row must survive');
        self::assertTrue(
            $this->itemExists($expiredUser),
            'user items must stay pinned — membership-leave does not yet nullify authorship references'
        );
    }

    public function testHardDeleteLinkItemRowsRemovesExpired(): void
    {
        $expiredItemId = $this->insertItem('link_item', -40);
        $recentItemId = $this->insertItem('link_item', -10);

        $this->insertLinkItem($expiredItemId, -40);
        $this->insertLinkItem($recentItemId, -10);

        $this->deleter->hardDeleteLinkItemRows(30);

        self::assertFalse($this->linkItemExists($expiredItemId));
        self::assertTrue($this->linkItemExists($recentItemId));
    }

    public function testHardDeleteTagRowsRemovesExpired(): void
    {
        $expiredId = $this->insertItem('tag', -40);
        $recentId = $this->insertItem('tag', -10);

        $this->insertTag($expiredId, -40);
        $this->insertTag($recentId, -10);

        $this->deleter->hardDeleteTagRows(30);

        self::assertFalse($this->tagExists($expiredId));
        self::assertTrue($this->tagExists($recentId));
    }

    public function testHardDeleteTag2TagPivotRowsRemovesExpired(): void
    {
        $expiredLinkId = $this->insertTag2Tag(1, 2, -40);
        $recentLinkId = $this->insertTag2Tag(3, 4, -10);
        $aliveLinkId = $this->insertTag2Tag(5, 6, null);

        $this->deleter->hardDeleteTag2TagPivotRows(30);

        self::assertFalse($this->tag2TagLinkExists($expiredLinkId));
        self::assertTrue($this->tag2TagLinkExists($recentLinkId));
        self::assertTrue($this->tag2TagLinkExists($aliveLinkId));
    }

    public function testHardDeleteTaskRowsRemovesExpired(): void
    {
        $expiredId = $this->insertItem('task', -40);
        $recentId = $this->insertItem('task', -10);

        $this->insertTask($expiredId, -40);
        $this->insertTask($recentId, -10);

        $this->deleter->hardDeleteTaskRows(30);

        self::assertFalse($this->taskExists($expiredId));
        self::assertTrue($this->taskExists($recentId));
    }

    // ------------------------------------------------------------------

    private function insertItem(string $type, ?int $daysAgo): int
    {
        if ($daysAgo === null) {
            $this->connection->executeStatement(
                'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
                ['type' => $type]
            );
        } else {
            $this->connection->executeStatement(
                sprintf(
                    "INSERT INTO items (type, modification_date, deletion_date, deleter_id)
                        VALUES (:type, NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0)",
                    $daysAgo
                ),
                ['type' => $type]
            );
        }

        return (int) $this->connection->lastInsertId();
    }

    private function insertLinkItem(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO link_items (item_id, creation_date, deletion_date, deleter_id, first_item_id, second_item_id)
                    VALUES (:itemId, NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, 1, 2)",
                $daysAgo
            ),
            ['itemId' => $itemId]
        );
    }

    private function insertTag(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO tag (item_id, creation_date, modification_date, deletion_date, deleter_id, title)
                    VALUES (:itemId, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, :title)",
                $daysAgo
            ),
            ['itemId' => $itemId, 'title' => 'test-tag-' . $itemId]
        );
    }

    private function insertTag2Tag(int $fromId, int $toId, ?int $daysAgo): int
    {
        if ($daysAgo === null) {
            $this->connection->executeStatement(
                'INSERT INTO tag2tag (from_item_id, to_item_id, creation_date, modification_date)
                    VALUES (:fromId, :toId, NOW(), NOW())',
                ['fromId' => $fromId, 'toId' => $toId]
            );
        } else {
            $this->connection->executeStatement(
                sprintf(
                    "INSERT INTO tag2tag (from_item_id, to_item_id, creation_date, modification_date, deletion_date, deleter_id)
                        VALUES (:fromId, :toId, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0)",
                    $daysAgo
                ),
                ['fromId' => $fromId, 'toId' => $toId]
            );
        }

        return (int) $this->connection->lastInsertId();
    }

    private function insertTask(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO tasks (item_id, creation_date, modification_date, deletion_date, deleter_id, title, status, linked_item_id)
                    VALUES (:itemId, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, :title, 'OPEN', 0)",
                $daysAgo
            ),
            ['itemId' => $itemId, 'title' => 'task-' . $itemId]
        );
    }

    private function itemExists(int $itemId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM items WHERE item_id = :id',
            ['id' => $itemId]
        );
    }

    private function linkItemExists(int $itemId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM link_items WHERE item_id = :id',
            ['id' => $itemId]
        );
    }

    private function tagExists(int $itemId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM tag WHERE item_id = :id',
            ['id' => $itemId]
        );
    }

    private function tag2TagLinkExists(int $linkId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM tag2tag WHERE link_id = :id',
            ['id' => $linkId]
        );
    }

    private function taskExists(int $itemId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM tasks WHERE item_id = :id',
            ['id' => $itemId]
        );
    }
}
