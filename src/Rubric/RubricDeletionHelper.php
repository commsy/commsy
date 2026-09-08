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
 * Shared low-level soft-delete primitives for a single rubric item and its
 * auxiliary rows (`link_items`, `links`, `annotations`, `item_link_file`,
 * `items` twin).
 *
 * Composed by every {@see RubricDeleter} implementation — each deleter picks
 * the primitives it needs for its rubric's cascade shape.
 */
class RubricDeletionHelper
{
    public function __construct(
        private readonly Connection $connection,
        private readonly FileDeleter $fileDeleter,
    ) {}

    /**
     * Soft-deletes all `link_items` rows referencing the given item (as
     * first/second linked item or as subject) plus their `items` twin rows.
     *
     * Parity fix: the legacy batch cascade `deleteLinksBecauseItemIsDeleted()`
     * skipped the twin, leaving orphaned `items` rows.
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
     * Soft-deletes all rows in `links` that reference the given item,
     * regardless of link_type and direction. Applied uniformly across all
     * rubrics (legacy cleanup was inconsistent).
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
     * Soft-deletes all annotations attached to the given item, including
     * their `items` twin rows and their `link_items` references.
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
     * Soft-deletes `item_link_file` rows attached to the given item, then
     * ends the life of every file that lost its last carrying entry.
     *
     * The two steps belong together: stamping only the link would leave
     * the file stranded — see
     * {@see \App\Files\FileDeleter::softDeleteFilesWithoutLiveLinks()}.
     */
    public function softDeleteFileLinks(int $itemId, int $deleterId, ?int $versionId = null): void
    {
        $fileIds = $this->findLinkedFileIds([$itemId]);

        // versionId 0 = the unversioned rubrics' only version.
        $this->fileDeleter->softDeleteFileLink($itemId, $versionId ?? 0, $deleterId);

        $this->fileDeleter->softDeleteFilesWithoutLiveLinks($fileIds, $deleterId);
    }

    /**
     * Soft-deletes every version of `item_link_file` rows for the given
     * item, then ends the life of the files that lost their last carrying
     * entry. For versioned rubrics (Material, Section) — the FileDeleter
     * variant filters on exact version_id.
     */
    public function softDeleteAllFileLinkVersions(int $itemId, int $deleterId): void
    {
        $fileIds = $this->findLinkedFileIds([$itemId]);

        $this->connection->executeStatement(
            'UPDATE item_link_file
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_iid = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->fileDeleter->softDeleteFilesWithoutLiveLinks($fileIds, $deleterId);
    }

    /**
     * Every file id linked to any of the given items, regardless of the
     * link's own deletion state. Read BEFORE the links are stamped so the
     * orphan sweep still knows which files to look at.
     *
     * @param int[] $itemIds
     *
     * @return int[]
     */
    public function findLinkedFileIds(array $itemIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $itemIds)));
        if ($ids === []) {
            return [];
        }

        return array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT file_id FROM item_link_file WHERE item_iid IN (:ids)',
            ['ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER]
        ));
    }

    /**
     * Soft-deletes the shared `items` table row for the given item.
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
     * ids. Annotations are not included — rubrics that carry them call
     * {@see softDeleteAnnotations()} per parent.
     */
    public function softDeleteAuxiliaryRowsForItems(array $itemIds, int $deleterId, bool $allFileLinkVersions = false): void
    {
        if (empty($itemIds)) {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $itemIds)));

        // Collect link_items ids before soft-deleting them so we can also
        // soft-delete their `items` twin rows in the same pass.
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

        $fileIds = $this->findLinkedFileIds($ids);

        if ($allFileLinkVersions) {
            // Versioned rubrics: match regardless of version_id.
            $this->connection->executeStatement(
                'UPDATE item_link_file
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_iid IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            );
        } else {
            foreach ($ids as $id) {
                $this->fileDeleter->softDeleteFileLink($id, 0, $deleterId);
            }
        }

        $this->fileDeleter->softDeleteFilesWithoutLiveLinks($fileIds, $deleterId);

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
