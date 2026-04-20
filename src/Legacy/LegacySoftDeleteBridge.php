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

namespace App\Legacy;

use App\Services\LegacyEnvironment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Thin soft-delete primitives for the few legacy save/copy call sites that
 * still need to remove a single child row (link_item, discussion article,
 * step, tag2tag pivot) after #5082.
 *
 * The legacy code reaches these primitives via
 * `$this->_environment->getSymfonyContainer()->get(LegacySoftDeleteBridge::class)`,
 * mirroring the existing pattern in `cs_item::_saveFileLinks()`. The goal is
 * to move the delete logic out of the legacy item and manager classes without
 * re-introducing delete behaviour into the legacy managers themselves.
 *
 * ## Aufrufer-Matrix (Stand: 2026-04)
 *
 *  - `softDeleteLinkItem()`         — called by `cs_item::_setObjectLinkItems()`,
 *                                     `cs_item::_setIDLinkItems()`,
 *                                     `cs_todo_item::removeProcessor()`,
 *                                     `cs_dates_item::removeParticipant()`,
 *                                     `cs_group_item::removeMember()`.
 *  - `softDeleteDiscussionArticle()` — called by `cs_discussion_item::copy()`.
 *  - `softDeleteStep()`              — called by `cs_todo_item::copy()`.
 *  - `softDeleteTag2TagPivot()`      — called by `cs_tag_item::savePositions()`.
 *
 * ## Parity targets (legacy code being replaced)
 *
 *  - `cs_link_manager::delete()`               — `softDeleteLinkItem()`
 *  - `cs_discussionarticles_manager::delete()` — `softDeleteDiscussionArticle()`
 *  - `cs_step_manager::delete()`               — `softDeleteStep()`
 *  - `cs_tag2tag_manager::delete()` +
 *    private `_cleanSortingPlaces()`           — `softDeleteTag2TagPivot()`
 *    (Note: the original `cs_tag2tag_manager` class itself is already gone;
 *    parity is documented against its historical behaviour. The hard-delete
 *    pendant for the tag2tag table now lives in
 *    {@see LegacyAuxHardDeleter::hardDeleteTag2TagPivotRows()}.)
 *
 * ## Intentional design points
 *
 * These methods are deliberately **not** registered as `RubricDeleter`: the
 * call sites are internal (inside save/copy flows) and delete a single child
 * row, not a top-level rubric item. They are also **not** expected to dispatch
 * `ItemDeletedEvent` / touch ES — the surrounding save/copy flow is
 * responsible for the consistency of the aggregate.
 *
 * ## Interim status — dissolved by separate ticket
 *
 * This class is a temporary Legacy→App bridge. It disappears once the legacy
 * save/copy flow in `cs_item` / `cs_todo_item` / `cs_discussion_item` /
 * `cs_tag_item` is extracted out of the legacy item classes (Ticket E in the
 * post-#5082 roadmap). Until then: **no new methods here** — any new soft-
 * delete need either belongs in a proper `*Deleter` service or blocks on
 * Ticket E.
 */
class LegacySoftDeleteBridge
{
    private readonly Connection $connection;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
    ) {
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Soft-deletes a single `link_items` row plus its `items` twin.
     *
     * Replaces `cs_link_manager::delete($itemId)` for the legacy-internal
     * callers `cs_item::_setObjectLinkItems()`, `cs_item::_setIDLinkItems()`
     * and `cs_todo_item::removeProcessor()`.
     */
    public function softDeleteLinkItem(int $itemId): void
    {
        $deleterId = $this->currentDeleterId();

        $this->connection->executeStatement(
            'UPDATE link_items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->connection->executeStatement(
            'UPDATE items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes a single `discussionarticles` row, its incoming `link_items`
     * references, and the `items` twin.
     *
     * Replaces `cs_discussionarticles_manager::delete($itemId)` for the legacy
     * caller `cs_discussion_item::copy()`. The `link_items` cleanup mirrors
     * the legacy call to `cs_link_manager::deleteLinksBecauseItemIsDeleted()`
     * that sits inside the manager's `delete()` method.
     */
    public function softDeleteDiscussionArticle(int $itemId): void
    {
        $deleterId = $this->currentDeleterId();

        $this->connection->executeStatement(
            'UPDATE discussionarticles SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // Parity with cs_link_manager::deleteLinksBecauseItemIsDeleted():
        // soft-delete every link_items row pointing at this article.
        $this->connection->executeStatement(
            'UPDATE link_items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE first_item_id = :itemId OR second_item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->connection->executeStatement(
            'UPDATE items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes a single `step` row plus its `items` twin.
     *
     * Replaces `cs_step_manager::delete($itemId)` for the legacy caller
     * `cs_todo_item::copy()`.
     */
    public function softDeleteStep(int $itemId): void
    {
        $deleterId = $this->currentDeleterId();

        $this->connection->executeStatement(
            'UPDATE step SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->connection->executeStatement(
            'UPDATE items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes a single `tag2tag` pivot row (one parent/child relationship
     * between two tags) and re-numbers the remaining siblings' `sorting_place`
     * under that parent.
     *
     * Replaces `cs_tag2tag_manager::delete($parentTagId, $childTagId)` plus the
     * private `_cleanSortingPlaces()` it calls, for the legacy caller
     * `cs_tag_item::savePositions()`.
     */
    public function softDeleteTag2TagPivot(int $parentTagId, int $childTagId): void
    {
        $deleterId = $this->currentDeleterId();

        $this->connection->executeStatement(
            'UPDATE tag2tag
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE from_item_id = :parentId AND to_item_id = :childId',
            ['deleterId' => $deleterId, 'parentId' => $parentTagId, 'childId' => $childTagId]
        );

        $this->renumberTagSiblings($parentTagId);
    }

    /**
     * Equivalent of legacy `cs_tag2tag_manager::_cleanSortingPlaces()`: renumber
     * the remaining (non-soft-deleted) children of `$parentTagId` to a dense
     * 1..N `sorting_place` range, preserving current order.
     */
    private function renumberTagSiblings(int $parentTagId): void
    {
        $linkIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT link_id FROM tag2tag
                WHERE from_item_id = :parentId
                  AND deletion_date IS NULL
                  AND deleter_id IS NULL
                ORDER BY sorting_place ASC',
            ['parentId' => $parentTagId]
        ));

        $position = 1;
        foreach ($linkIds as $linkId) {
            $this->connection->executeStatement(
                'UPDATE tag2tag SET sorting_place = :position WHERE link_id = :linkId',
                ['position' => $position, 'linkId' => $linkId]
            );
            ++$position;
        }
    }

    /**
     * Legacy parity: when no user context is available, the legacy managers
     * fall back to `getItemID() ?: 0`. We preserve that sentinel so cron /
     * background jobs don't hit a non-null constraint mismatch.
     */
    private function currentDeleterId(): int
    {
        return (int) ($this->legacyEnvironment->getEnvironment()->getCurrentUserItem()?->getItemID() ?: 0);
    }
}
