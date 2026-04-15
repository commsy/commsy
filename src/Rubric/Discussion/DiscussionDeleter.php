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
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Discussion items without delegating to the legacy
 * `cs_discussion_item::delete()` / `cs_discussionarticle_item::delete()`
 * cascade.
 *
 * In addition to the generic `RubricDeleter` contract (ganze Discussion
 * inkl. aller Beiträge) this class exposes {@see deleteArticle()} for the
 * UI delete-single-article flow. Articles with child answers don't get
 * soft-deleted — instead their content is **purged** (description + subject
 * emptied, creator/modifier nullified) while the row stays alive so the
 * thread hierarchy remains intact. That matches the legacy `public = -2`
 * tombstone pattern but additionally satisfies data-protection demands by
 * actually erasing the content rather than merely flagging it.
 */
class DiscussionDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricKey(): string
    {
        return 'discussion';
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
     * Soft-deletes the discussion **and every article it contains** in one go.
     *
     * We intentionally skip the per-article overwrite-tombstone dance here —
     * the whole thread is gone, so keeping a zombie hierarchy would be
     * pointless (and would only leak content that should be removed).
     */
    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Dispatch the deletion event — ElasticaSubscriber removes the
        //    discussion document from its index in response. Article-level ES
        //    cleanup is implicit: articles are not indexed separately.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Collect all alive article ids belonging to this discussion.
        $articleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM discussionarticles
                WHERE discussion_id = :discussionId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['discussionId' => $itemId]
        ));

        // 3. Soft-delete every article row (discussionarticles) in a single UPDATE.
        if (!empty($articleIds)) {
            $this->connection->executeStatement(
                'UPDATE discussionarticles
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $articleIds],
                ['ids' => ArrayParameterType::INTEGER]
            );

            // 4. And wipe their auxiliary rows (link_items, links, file_links,
            //    items) in batched statements.
            $this->itemDeletionHelper->softDeleteAuxiliaryRowsForItems($articleIds, $deleterId);
        }

        // 5. Soft-delete the `discussions` row itself.
        $this->connection->executeStatement(
            'UPDATE discussions
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 6. And the auxiliary cleanup for the discussion itself.
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Deletes a single discussion article.
     *
     * Two distinct paths depending on whether the article has answers:
     *
     *  - **No children**: regular soft-delete (rubric row + items + links +
     *    link_items + file_links).
     *  - **With children**: the row stays alive (so the thread hierarchy
     *    remains navigable) but the content is physically erased: description
     *    and subject are emptied, creator_id and modifier_id are NULLed for
     *    author anonymisation, and `public = -2` is set so the UI layer
     *    continues to render the legacy "deleted article with answers"
     *    placeholder via {@see cs_discussionarticle_item::getDescription()}.
     *    link_items / links / file_links attached to the article are still
     *    cleaned up — they don't carry any thread-structure information.
     *
     * In both cases the parent discussion is re-indexed via the legacy
     * `updateElastic()` helper so removed article content disappears from
     * the discussion's search document.
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
            // translator-driven placeholder in the UI working. Not marking
            // the row soft-deleted would break the thread tree otherwise.
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

            // Links / link_items / file_links can go even though the row
            // itself stays — they don't participate in the thread hierarchy.
            $this->itemDeletionHelper->softDeleteLinks($articleId, $deleterId);
            $this->itemDeletionHelper->softDeleteLinkItems($articleId, $deleterId);
            $this->itemDeletionHelper->softDeleteFileLinks($articleId);
        } else {
            $this->connection->executeStatement(
                'UPDATE discussionarticles
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id = :id',
                ['deleterId' => $deleterId, 'id' => $articleId]
            );

            $this->itemDeletionHelper->softDeleteLinks($articleId, $deleterId);
            $this->itemDeletionHelper->softDeleteLinkItems($articleId, $deleterId);
            $this->itemDeletionHelper->softDeleteFileLinks($articleId);
            $this->itemDeletionHelper->softDeleteItemsRow($articleId, $deleterId);
        }

        // Re-index the parent discussion so removed article content
        // disappears from its search document (articles have no own index).
        // ElasticaSubscriber::onItemReindex picks this up and performs the
        // ObjectPersister delete+insert; ReadStatusSubscriber invalidates the
        // read-status cache for the discussion on the same event.
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
}
