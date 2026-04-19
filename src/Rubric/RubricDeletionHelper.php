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

namespace App\Rubric;

use App\Files\FileDeleter;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Shared low-level soft-delete primitives for a **single rubric item** and
 * its auxiliary rows (`link_items`, `links`, `annotations`, `item_link_file`,
 * `items` twin).
 *
 * Composed by every {@see RubricDeleter} implementation — each deleter picks
 * only the primitives it needs for its rubric's cascade shape. Mirrors the
 * soft-delete behaviour of the legacy `cs_item::_delete()` cascade, but as
 * explicit DBAL operations instead of a monolithic method.
 *
 * Scope-boundary: this class contains **only rubric-item-scoped primitives**.
 * User-scoped cleanup (task ownership, membership artefacts) lives in
 * {@see \App\User\UserDeletionHelper} — a separate bounded context, intentionally
 * not mixed in here, so the class stays small and its name keeps telling the
 * truth as the delete landscape grows.
 *
 * Naming: mirrors {@see \App\Room\RoomDeletionHelper} — same `…DeletionHelper`
 * suffix signals "primitives consumed by the leaf deleters of this aggregate",
 * never the leaf-delete interface itself.
 *
 * (Was previously `App\Rubric\ItemDeletionHelper`; split in the post-#5082
 * consolidation pass to disentangle rubric- and user-scoped operations.)
 */
class RubricDeletionHelper
{
    public function __construct(
        private readonly Connection $connection,
        private readonly FileDeleter $fileDeleter,
    ) {}

    /**
     * Soft-deletes all `link_items` rows that reference the given item, either
     * as first/second linked item or as the subject of the row itself.
     *
     * Equivalent to legacy `cs_link_manager::deleteLinksBecauseItemIsDeleted()`,
     * but additionally soft-deletes the `items` twin row of each affected
     * link_item: `cs_link_manager::_create()` allocates a row in `items`
     * (type = 'link_item') to obtain the AUTO_INCREMENT id before inserting
     * into `link_items`, and `cs_link_manager::delete()` cleans up both sides
     * via `parent::delete()`. The legacy batch-cascade `deleteLinksBecause
     * ItemIsDeleted()` skipped the twin, leaving orphaned `items` rows; we fix
     * that inconsistency here so the two tables stay in sync.
     */
    public function softDeleteLinkItems(int $itemId, int $deleterId): void
    {
        $linkItemIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM link_items
                WHERE (first_item_id = :itemId
                       OR second_item_id = :itemId
                       OR item_id = :itemId)
                  AND deletion_date IS NULL',
            ['itemId' => $itemId]
        ));
        if (empty($linkItemIds)) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE link_items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $linkItemIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $this->connection->executeStatement(
            'UPDATE items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $linkItemIds],
            ['ids' => ArrayParameterType::INTEGER]
        );
    }

    /**
     * Soft-deletes all rows in the `links` table that reference the given item,
     * regardless of link_type (`buzzword_for`, `in_time`, `label_for`, …) and
     * direction (from/to).
     *
     * This fixes a legacy inconsistency: `cs_dates_manager::delete()` cleaned
     * up `links` via `deleteLinksBecauseItemIsDeleted()`, while other rubric
     * managers either skipped it (Discussion, Material, Task) or hard-deleted
     * a single type (Announcement's `relevant_for`). All rubrics migrated off
     * the legacy cascade use this helper so the behaviour is uniform.
     */
    public function softDeleteLinks(int $itemId, int $deleterId): void
    {
        $this->connection->executeStatement(
            'UPDATE links
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE from_item_id = :itemId OR to_item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes all annotations attached to the given item, including their
     * `items` twin rows and their `link_items` references.
     *
     * Equivalent to legacy `cs_item::deleteAssociatedAnnotations()` which loads
     * the annotation list and calls `delete()` on each.
     */
    public function softDeleteAnnotations(int $parentItemId, int $deleterId): void
    {
        $annotationIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM annotations
                WHERE linked_item_id = :parentItemId AND deletion_date IS NULL',
            ['parentItemId' => $parentItemId]
        );

        foreach ($annotationIds as $annotationId) {
            $annotationId = (int) $annotationId;

            $this->connection->executeStatement(
                'UPDATE annotations
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id = :itemId',
                ['deleterId' => $deleterId, 'itemId' => $annotationId]
            );

            $this->softDeleteLinkItems($annotationId, $deleterId);
            $this->softDeleteItemsRow($annotationId, $deleterId);
        }
    }

    /**
     * Soft-deletes `item_link_file` rows attached to the given item.
     *
     * Delegates to the existing FileDeleter service. Note that the legacy
     * `delete()` methods only cleaned up file links for Material items — this
     * refactoring applies the cleanup uniformly across all rubrics, since any
     * cs_item can carry file attachments.
     */
    public function softDeleteFileLinks(int $itemId, ?int $versionId = null): void
    {
        // When versionId is not given, treat it as "all versions" (0).
        $this->fileDeleter->softDeleteFileLink($itemId, $versionId ?? 0);
    }

    /**
     * Soft-deletes **every version** of `item_link_file` rows for the given
     * item. Needed for versioned rubrics (Material, Section): the FileDeleter
     * variant filters on an exact `version_id` match, so it cannot purge
     * attachments from older versions in one go.
     *
     * The legacy `cs_material_item::delete()` cascade passed the *current*
     * version id into `deleteByItem()` even when dropping all versions — that
     * was a bug; this helper fixes it by dropping the version filter entirely.
     */
    public function softDeleteAllFileLinkVersions(int $itemId, int $deleterId): void
    {
        $this->connection->executeStatement(
            'UPDATE item_link_file
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_iid = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes the shared `items` table row for the given item.
     *
     * Equivalent to legacy base `cs_manager::delete()` which is invoked from
     * every rubric manager's delete() as `parent::delete()`.
     */
    public function softDeleteItemsRow(int $itemId, int $deleterId): void
    {
        $this->connection->executeStatement(
            'UPDATE items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Batch variant: soft-delete every reference in `link_items`, `links`,
     * `item_link_file` and the shared `items` row for the given list of item
     * ids. Used by rubrics that wipe out hierarchies in one go (e.g.
     * DiscussionDeleter removing all articles of a discussion) — the single
     * SQL statements are cheaper than looping singular helpers per item.
     *
     * Annotations are intentionally *not* included here: rubrics that carry
     * annotations still call {@see softDeleteAnnotations()} on a per-parent
     * basis because the parent id is the join key, not the batched ids.
     */
    public function softDeleteAuxiliaryRowsForItems(array $itemIds, int $deleterId, bool $allFileLinkVersions = false): void
    {
        if (empty($itemIds)) {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $itemIds)));

        // Collect the item_ids of every link_items row that references any of
        // the batched ids (as subject, first or second linked item) *before*
        // soft-deleting them, so we can soft-delete their `items` twin rows in
        // the same pass. See {@see softDeleteLinkItems()} for why the twin
        // exists and why the legacy batch cascade missed it.
        $linkItemIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM link_items
                WHERE (first_item_id IN (:ids)
                       OR second_item_id IN (:ids)
                       OR item_id IN (:ids))
                  AND deletion_date IS NULL',
            ['ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER]
        ));

        if (!empty($linkItemIds)) {
            $this->connection->executeStatement(
                'UPDATE link_items
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $linkItemIds],
                ['ids' => ArrayParameterType::INTEGER]
            );
        }

        $this->connection->executeStatement(
            'UPDATE links
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE from_item_id IN (:ids) OR to_item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER]
        );

        if ($allFileLinkVersions) {
            // Versioned rubrics (Material, Section): match regardless of version_id.
            $this->connection->executeStatement(
                'UPDATE item_link_file
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_iid IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            );
        } else {
            // Non-versioned rubrics: delegate to FileDeleter item-by-item.
            foreach ($ids as $id) {
                $this->fileDeleter->softDeleteFileLink($id, 0);
            }
        }

        // Soft-delete the `items` rows for both the batched ids themselves and
        // the link_items twin rows in a single UPDATE.
        $itemsRowIds = array_values(array_unique(array_merge($ids, $linkItemIds)));
        $this->connection->executeStatement(
            'UPDATE items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $itemsRowIds],
            ['ids' => ArrayParameterType::INTEGER]
        );
    }
}
