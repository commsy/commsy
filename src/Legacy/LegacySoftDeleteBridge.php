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
 * Soft-delete primitives for the few legacy save/copy call sites that still
 * need to remove a single child row (link_item, discussion article, step,
 * tag2tag pivot). Called from legacy save/copy paths in cs_item / cs_todo_item
 * / cs_discussion_item / cs_tag_item / cs_dates_item / cs_group_item.
 *
 * Hard-delete pendant for the tag2tag table lives in
 * {@see LegacyAuxHardDeleter::hardDeleteTag2TagPivotRows()}.
 *
 * Interim Legacy->App bridge; dissolved by Ticket E in the post-#5082 roadmap.
 * No new methods here — any new soft-delete need belongs in a proper
 * `*Deleter` service.
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
     * Parity: cs_link_manager::delete()
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
     * Parity: cs_discussionarticles_manager::delete()
     */
    public function softDeleteDiscussionArticle(int $itemId): void
    {
        $deleterId = $this->currentDeleterId();

        $this->connection->executeStatement(
            'UPDATE discussionarticles SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // Parity: cs_link_manager::deleteLinksBecauseItemIsDeleted()
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
     * Parity: cs_step_manager::delete()
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
     * Soft-deletes a single `tag2tag` pivot row and re-numbers the remaining
     * siblings' `sorting_place` under that parent.
     *
     * Parity: cs_tag2tag_manager::delete() + _cleanSortingPlaces()
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
     * Renumber remaining children of `$parentTagId` to a dense 1..N
     * `sorting_place` range. Parity: cs_tag2tag_manager::_cleanSortingPlaces()
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
     * Legacy parity: falls back to 0 when no user is bound (cron context).
     */
    private function currentDeleterId(): int
    {
        return (int) ($this->legacyEnvironment->getEnvironment()->getCurrentUserItem()?->getItemID() ?: 0);
    }
}
