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

namespace App\Rubric\Material;

use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Material items. Replaces the legacy `cs_material_item::delete()`
 * / `cs_section_item::delete()` cascade.
 *
 * Materials (and their sections + file attachments) are versioned. Three
 * entry points: {@see softDeleteItem()} wipes every version,
 * {@see deleteCurrentVersion()} drops only the latest, {@see deleteSection()}
 * drops a single section within one version.
 */
class MaterialDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Material;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT item_id FROM materials
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * `SELECT DISTINCT` because materials are versioned. Sections are not
     * returned — they are cleaned up transitively by {@see softDeleteItem()}.
     */
    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT item_id FROM materials
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Soft-deletes the material and every version + every section (all
     * versions) it contains. Annotations (not versioned) die completely;
     * all versioned `item_link_file` rows are dropped.
     */
    public function softDeleteItem(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // Collect distinct section ids across all versions.
        $sectionIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT item_id FROM section
                WHERE material_item_id = :materialId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['materialId' => $itemId]
        ));

        if (!empty($sectionIds)) {
            $this->connection->executeStatement(
                'UPDATE section
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE material_item_id = :materialId',
                ['deleterId' => $deleterId, 'materialId' => $itemId]
            );

            $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems(
                $sectionIds,
                $deleterId,
                allFileLinkVersions: true,
            );
        }

        // Soft-delete every version of the `materials` row.
        $this->connection->executeStatement(
            'UPDATE materials
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->rubricDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteAnnotations($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteAllFileLinkVersions($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Drops only the current (latest) version of the material. The previous
     * version becomes the new current one; the `items` row stays alive.
     * Links / link_items / annotations are version-agnostic and not touched.
     */
    public function deleteCurrentVersion(int $itemId, int $deleterId): void
    {
        $row = $this->connection->fetchOne(
            'SELECT MAX(version_id) FROM materials
                WHERE item_id = :itemId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['itemId' => $itemId]
        );
        if ($row === false || $row === null) {
            return; // nothing alive to drop
        }
        $currentVersionId = (int) $row;

        // Resolve typed item before soft-delete: once the only alive version
        // is gone the legacy manager returns null.
        $typedItem = $this->itemService->getTypedItem($itemId);

        $this->connection->executeStatement(
            'UPDATE materials
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId AND version_id = :versionId',
            ['deleterId' => $deleterId, 'itemId' => $itemId, 'versionId' => $currentVersionId]
        );

        $this->connection->executeStatement(
            'UPDATE section
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE material_item_id = :materialId AND version_id = :versionId',
            ['deleterId' => $deleterId, 'materialId' => $itemId, 'versionId' => $currentVersionId]
        );

        $this->connection->executeStatement(
            'UPDATE item_link_file
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_iid = :itemId AND item_vid = :versionId',
            ['deleterId' => $deleterId, 'itemId' => $itemId, 'versionId' => $currentVersionId]
        );

        // Reindex so the new current version surfaces in ES.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemReindexEvent($typedItem), ItemReindexEvent::class);
        }
    }

    /**
     * Drops a single section row (per material version) and reindexes the
     * parent material. If `$materialVersionId` is null, every version of
     * the section is soft-deleted.
     */
    public function deleteSection(int $sectionId, int $deleterId, ?int $materialVersionId = null): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT material_item_id FROM section
                WHERE item_id = :id
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL
                LIMIT 1',
            ['id' => $sectionId]
        );
        if ($row === false) {
            return; // already gone
        }
        $materialId = (int) $row['material_item_id'];

        if ($materialVersionId !== null) {
            $this->connection->executeStatement(
                'UPDATE section
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id = :id AND version_id = :versionId',
                ['deleterId' => $deleterId, 'id' => $sectionId, 'versionId' => $materialVersionId]
            );

            $this->connection->executeStatement(
                'UPDATE item_link_file
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_iid = :id AND item_vid = :versionId',
                ['deleterId' => $deleterId, 'id' => $sectionId, 'versionId' => $materialVersionId]
            );
        } else {
            $this->connection->executeStatement(
                'UPDATE section
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id = :id',
                ['deleterId' => $deleterId, 'id' => $sectionId]
            );

            $this->rubricDeletionHelper->softDeleteLinks($sectionId, $deleterId);
            $this->rubricDeletionHelper->softDeleteLinkItems($sectionId, $deleterId);
            $this->rubricDeletionHelper->softDeleteAllFileLinkVersions($sectionId, $deleterId);
            $this->rubricDeletionHelper->softDeleteItemsRow($sectionId, $deleterId);
        }

        // Reindex parent material so the deleted section disappears from
        // its embedded `sections` field.
        $typedMaterial = $this->itemService->getTypedItem($materialId);
        if ($typedMaterial !== null) {
            $this->eventDispatcher->dispatch(new ItemReindexEvent($typedMaterial), ItemReindexEvent::class);
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE materials SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE materials SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    /**
     * Sweeps `materials` (all versions) and `section` (all versions).
     */
    public function hardDeleteOlderThan(int $days): int
    {
        $count = 0;

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM section
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM materials
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        return $count;
    }
}
