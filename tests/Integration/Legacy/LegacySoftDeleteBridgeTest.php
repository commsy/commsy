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

use App\Legacy\LegacySoftDeleteBridge;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration coverage for {@see LegacySoftDeleteBridge} — the narrow set of
 * soft-delete primitives that legacy save/copy call sites still reach for
 * while #5082 finishes.
 *
 * Each public method is pinned against the DB with the three columns the
 * legacy `parent::delete()` would have written: `deletion_date`,
 * `deleter_id`, plus the primary-table row itself. The tag2tag sibling
 * renumber is verified separately — it replaces `_cleanSortingPlaces()`.
 *
 * We swap in a real {@see Connection} and a mocked {@see LegacyEnvironment}
 * so the `currentDeleterId()` fallback is reproducible (legacy returned
 * `?: 0` when no user was set; we pin that sentinel explicitly).
 */
final class LegacySoftDeleteBridgeTest extends KernelTestCase
{
    private Connection $connection;
    private LegacySoftDeleteBridge $bridge;
    /** @var LegacyEnvironment&MockObject */
    private LegacyEnvironment $legacyEnvironment;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $entityManager->getConnection();

        $noUser = $this->createMock(cs_user_item::class);
        $noUser->method('getItemID')->willReturn(0);

        $innerEnv = $this->createMock(cs_environment::class);
        $innerEnv->method('getCurrentUserItem')->willReturn($noUser);

        $this->legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $this->legacyEnvironment->method('getEnvironment')->willReturn($innerEnv);

        $this->bridge = new LegacySoftDeleteBridge($this->legacyEnvironment, $entityManager);
    }

    public function testSoftDeleteLinkItemMarksBothLinkItemsAndItemsRow(): void
    {
        $itemId = $this->insertLinkItem();

        $this->bridge->softDeleteLinkItem($itemId);

        $this->assertSoftDeleted('link_items', $itemId);
        $this->assertSoftDeleted('items', $itemId);
    }

    public function testSoftDeleteDiscussionArticleSweepsArticleIncomingLinksAndItemsTwin(): void
    {
        $articleId = $this->insertDiscussionArticle();
        $incomingLink = $this->insertLinkItemBetween($articleId, 42);
        $outgoingLink = $this->insertLinkItemBetween(99, $articleId);

        $this->bridge->softDeleteDiscussionArticle($articleId);

        $this->assertSoftDeleted('discussionarticles', $articleId);
        $this->assertSoftDeleted('items', $articleId);
        $this->assertSoftDeleted('link_items', $incomingLink);
        $this->assertSoftDeleted('link_items', $outgoingLink);
    }

    public function testSoftDeleteStepMarksStepAndItemsTwin(): void
    {
        $stepId = $this->insertStep();

        $this->bridge->softDeleteStep($stepId);

        $this->assertSoftDeleted('step', $stepId);
        $this->assertSoftDeleted('items', $stepId);
    }

    /**
     * `softDeleteTag2TagPivot` soft-deletes one parent/child row and then
     * renumbers the remaining siblings so `sorting_place` stays a dense
     * 1..N range — parity with the legacy `_cleanSortingPlaces()` that sat
     * inside `cs_tag2tag_manager::delete()`.
     */
    public function testSoftDeleteTag2TagPivotSoftDeletesAndRenumbersSiblings(): void
    {
        $parentId = 4711;

        $this->insertTag2TagSibling($parentId, 100, 1);
        $this->insertTag2TagSibling($parentId, 200, 2); // target
        $this->insertTag2TagSibling($parentId, 300, 3);
        $this->insertTag2TagSibling($parentId, 400, 4);

        $this->bridge->softDeleteTag2TagPivot($parentId, 200);

        self::assertTrue($this->tag2TagPivotSoftDeleted($parentId, 200));

        // Remaining siblings renumbered 1..3 in original order:
        self::assertSame(1, $this->tag2TagSortingPlace($parentId, 100));
        self::assertSame(2, $this->tag2TagSortingPlace($parentId, 300));
        self::assertSame(3, $this->tag2TagSortingPlace($parentId, 400));
    }

    // ------------------------------------------------------------------

    private function insertLinkItem(): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
            ['type' => 'link_item']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO link_items (item_id, creation_date, first_item_id, second_item_id)
                VALUES (:itemId, NOW(), 1, 2)',
            ['itemId' => $itemId]
        );

        return $itemId;
    }

    private function insertLinkItemBetween(int $fromId, int $toId): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
            ['type' => 'link_item']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO link_items (item_id, creation_date, first_item_id, second_item_id)
                VALUES (:itemId, NOW(), :fromId, :toId)',
            ['itemId' => $itemId, 'fromId' => $fromId, 'toId' => $toId]
        );

        return $itemId;
    }

    private function insertDiscussionArticle(): int
    {
        // Parent discussion (FK target).
        $this->connection->executeStatement(
            'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
            ['type' => 'discussion']
        );
        $discussionId = (int) $this->connection->lastInsertId();
        $this->connection->executeStatement(
            'INSERT INTO discussions (item_id, creation_date, title) VALUES (:itemId, NOW(), :title)',
            ['itemId' => $discussionId, 'title' => 'disc-' . $discussionId]
        );

        $this->connection->executeStatement(
            'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
            ['type' => 'discarticle']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO discussionarticles (item_id, creation_date, modification_date, discussion_id, description)
                VALUES (:itemId, NOW(), NOW(), :discussionId, :description)',
            ['itemId' => $itemId, 'discussionId' => $discussionId, 'description' => 'article-' . $itemId]
        );

        return $itemId;
    }

    private function insertStep(): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
            ['type' => 'step']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO step (item_id, creation_date, modification_date, todo_item_id, title)
                VALUES (:itemId, NOW(), NOW(), 0, :title)',
            ['itemId' => $itemId, 'title' => 'step-' . $itemId]
        );

        return $itemId;
    }

    private function insertTag2TagSibling(int $parentId, int $childId, int $sortingPlace): void
    {
        $this->connection->executeStatement(
            'INSERT INTO tag2tag (from_item_id, to_item_id, creation_date, modification_date, sorting_place)
                VALUES (:parentId, :childId, NOW(), NOW(), :place)',
            ['parentId' => $parentId, 'childId' => $childId, 'place' => $sortingPlace]
        );
    }

    private function tag2TagPivotSoftDeleted(int $parentId, int $childId): bool
    {
        $value = $this->connection->fetchOne(
            'SELECT deletion_date FROM tag2tag WHERE from_item_id = :parentId AND to_item_id = :childId',
            ['parentId' => $parentId, 'childId' => $childId]
        );

        return $value !== null && $value !== false;
    }

    private function tag2TagSortingPlace(int $parentId, int $childId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT sorting_place FROM tag2tag
                WHERE from_item_id = :parentId
                  AND to_item_id = :childId
                  AND deletion_date IS NULL',
            ['parentId' => $parentId, 'childId' => $childId]
        );
    }

    private function assertSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNotNull($row['deletion_date'], sprintf('%s row %d must be soft-deleted', $table, $itemId));
        self::assertSame(
            0,
            (int) $row['deleter_id'],
            sprintf('%s row %d must fall back to deleter_id=0 when no user is set', $table, $itemId)
        );
    }
}
