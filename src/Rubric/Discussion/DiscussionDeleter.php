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

namespace App\Rubric\Discussion;

use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Discussion items. Replaces the legacy `cs_discussion_item::delete()`
 * / `cs_discussionarticle_item::delete()` cascade. Exposes
 * {@see deleteArticle()} for the UI delete-single-article flow; articles
 * with child answers have their content purged while the row stays alive
 * (legacy `public = -2` tombstone pattern, DSGVO-hardened).
 */
class DiscussionDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Discussion;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM discussions
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Only top-level discussions; articles are soft-deleted transitively
     * by {@see softDeleteItem()}.
     */
    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM discussions
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Soft-deletes the discussion and every article it contains. The
     * per-article tombstone purge is skipped here since the whole thread
     * is gone.
     */
    public function softDeleteItem(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        $articleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM discussionarticles
                WHERE discussion_id = :discussionId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['discussionId' => $itemId]
        ));

        if (!empty($articleIds)) {
            $this->connection->executeStatement(
                'UPDATE discussionarticles
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $articleIds],
                ['ids' => ArrayParameterType::INTEGER]
            );

            $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($articleIds, $deleterId);
        }

        $this->connection->executeStatement(
            'UPDATE discussions
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->rubricDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteFileLinks($itemId);
        $this->rubricDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Deletes a single discussion article.
     *
     * - No children: regular soft-delete.
     * - With children: row stays alive (thread hierarchy preserved) but
     *   description/subject are emptied, creator/modifier nullified, and
     *   `public = -2` is set so the UI renders the "deleted article with
     *   answers" placeholder. Attached links / link_items / file_links are
     *   still cleaned up.
     *
     * The parent discussion is re-indexed in both cases.
     */
    public function deleteArticle(int $articleId, int $deleterId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT discussion_id, position FROM discussionarticles
                WHERE item_id = :id AND deleter_id IS NULL AND deletion_date IS NULL',
            ['id' => $articleId]
        );
        if ($row === false) {
            return; // already gone
        }

        $discussionId = (int) $row['discussion_id'];
        $position = (string) $row['position'];

        $hasChildren = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM discussionarticles
                WHERE discussion_id = :discussionId
                  AND position LIKE :childPattern
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            [
                'discussionId' => $discussionId,
                'childPattern' => $position . '.%',
            ]
        ) > 0;

        if ($hasChildren) {
            // Purge content + anonymise author. `public = -2` keeps the
            // translator-driven placeholder in the UI working; not
            // soft-deleting the row preserves the thread tree.
            $this->connection->executeStatement(
                "UPDATE discussionarticles
                    SET description = '',
                        creator_id = NULL,
                        modifier_id = NULL,
                        public = -2,
                        modification_date = NOW()
                    WHERE item_id = :id",
                ['id' => $articleId]
            );

            // Links / link_items / file_links don't participate in the
            // thread hierarchy, so they can go even though the row stays.
            $this->rubricDeletionHelper->softDeleteLinks($articleId, $deleterId);
            $this->rubricDeletionHelper->softDeleteLinkItems($articleId, $deleterId);
            $this->rubricDeletionHelper->softDeleteFileLinks($articleId);
        } else {
            $this->connection->executeStatement(
                'UPDATE discussionarticles
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id = :id',
                ['deleterId' => $deleterId, 'id' => $articleId]
            );

            $this->rubricDeletionHelper->softDeleteLinks($articleId, $deleterId);
            $this->rubricDeletionHelper->softDeleteLinkItems($articleId, $deleterId);
            $this->rubricDeletionHelper->softDeleteFileLinks($articleId);
            $this->rubricDeletionHelper->softDeleteItemsRow($articleId, $deleterId);
        }

        // Re-index parent discussion — articles have no own index.
        $discussion = $this->itemService->getTypedItem($discussionId);
        if ($discussion !== null) {
            $this->eventDispatcher->dispatch(new ItemReindexEvent($discussion), ItemReindexEvent::class);
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE discussions SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE discussions SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    /**
     * Sweeps both `discussions` and `discussionarticles`. Legacy only
     * covered `discussions`, leaving expired articles orphaned — gap
     * closed here.
     */
    public function hardDeleteOlderThan(int $days): int
    {
        $count = 0;

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM discussionarticles
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM discussions
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        return $count;
    }
}
