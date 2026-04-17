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
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Material items without delegating to the legacy
 * `cs_material_item::delete()` / `cs_section_item::delete()` cascade.
 *
 * Materials (and their sections + file attachments) are **versioned**: each
 * edit creates a new row with an incremented `version_id`. This deleter
 * exposes three distinct entry points to match the three user flows:
 *
 *  - {@see deleteItem()}           — CS_ALL semantic (wipe every version).
 *                                     Used by the `RubricDeleter` contract
 *                                     (Account-Delete) and the generic UI
 *                                     delete ("Material wegwerfen").
 *  - {@see deleteCurrentVersion()} — drop only the latest version; the
 *                                     previous version becomes the new
 *                                     current one. `items` row stays alive.
 *  - {@see deleteSection()}        — drop a section within one material
 *                                     version; reindexes the parent material.
 *
 * Unlike the legacy cascade this implementation cleans up *all* versioned
 * `item_link_file` rows when dropping the whole item (the legacy
 * `deleteByItem()` call passed the current version id even in CS_ALL mode —
 * a bug that left attachments of older versions behind).
 */
class MaterialDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
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
     * `SELECT DISTINCT` because materials are versioned (multiple rows per
     * `item_id`, one per version). Sections are NOT returned — they are
     * cleaned up transitively by {@see deleteItem()} on the parent material.
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
     * Soft-deletes the material **and every version + every section (all
     * versions) it contains**. Annotations (not versioned) die completely;
     * all versioned `item_link_file` rows are dropped.
     *
     * Task cascade is **not yet** performed here. Todo-tasks ("Aufgabe an
     * Material") are modelled as a separate rubric with their own deleter,
     * but whether that rubric should really exist as an independent
     * `RubricDeleter` or be folded into Material is still open. Until that
     * is clarified, tasks linked to the deleted material remain orphaned
     * (no worse than the legacy state, where they disappeared from the UI
     * but nothing else referenced them).
     */
    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Event — ElasticaSubscriber removes the material document from
        //    `commsy_material`. Section data embedded in that document dies
        //    with it; no per-section events needed.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Collect distinct section ids across all versions of this material.
        $sectionIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT item_id FROM section
                WHERE material_item_id = :materialId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['materialId' => $itemId]
        ));

        // 3. Soft-delete *every* section row (all versions) in a single UPDATE,
        //    then batch-clean their auxiliary rows incl. versioned file_links.
        if (!empty($sectionIds)) {
            $this->connection->executeStatement(
                'UPDATE section
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE material_item_id = :materialId',
                ['deleterId' => $deleterId, 'materialId' => $itemId]
            );

            $this->itemDeletionHelper->softDeleteAuxiliaryRowsForItems(
                $sectionIds,
                $deleterId,
                allFileLinkVersions: true,
            );
        }

        // 4. Soft-delete *every* version of the `materials` row.
        $this->connection->executeStatement(
            'UPDATE materials
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 5. Auxiliary cleanup for the material itself.
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteAnnotations($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteAllFileLinkVersions($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Drops only the **current (latest)** version of the material. The
     * previous version remains intact and becomes the new current one; the
     * `items` row stays alive.
     *
     * Section rows created for the dropped version are soft-deleted on a
     * per-version basis, along with the versioned `item_link_file` rows.
     * Links / link_items / annotations are *not* touched — they are
     * version-agnostic and remain valid for the prior material version.
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

        // Resolve the typed item *before* the version is soft-deleted — once
        // the only alive version is gone the legacy manager returns null and
        // we would lose the handle needed to dispatch the reindex event.
        $typedItem = $this->itemService->getTypedItem($itemId);

        $this->connection->executeStatement(
            'UPDATE materials
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId AND version_id = :versionId',
            ['deleterId' => $deleterId, 'itemId' => $itemId, 'versionId' => $currentVersionId]
        );

        // Sections for exactly this material version.
        $this->connection->executeStatement(
            'UPDATE section
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE material_item_id = :materialId AND version_id = :versionId',
            ['deleterId' => $deleterId, 'materialId' => $itemId, 'versionId' => $currentVersionId]
        );

        // Versioned file_links belonging to that material version.
        $this->connection->executeStatement(
            'UPDATE item_link_file
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_iid = :itemId AND item_vid = :versionId',
            ['deleterId' => $deleterId, 'itemId' => $itemId, 'versionId' => $currentVersionId]
        );

        // Reindex so the new "current" version surfaces in ES. No items-row
        // touch here — the material as an entity still exists.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemReindexEvent($typedItem), ItemReindexEvent::class);
        }
    }

    /**
     * Drops a single section row (per material version) and reindexes the
     * parent material so the removed section disappears from its embedded
     * `sections` field. Sections have no own ES index.
     *
     * If `$materialVersionId` is null, every version of the section is
     * soft-deleted. The legacy UI path always passes the material's current
     * version — matching that behaviour keeps older material versions'
     * section lists intact.
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

            $this->itemDeletionHelper->softDeleteLinks($sectionId, $deleterId);
            $this->itemDeletionHelper->softDeleteLinkItems($sectionId, $deleterId);
            $this->itemDeletionHelper->softDeleteAllFileLinkVersions($sectionId, $deleterId);
            $this->itemDeletionHelper->softDeleteItemsRow($sectionId, $deleterId);
        }

        // Reindex the parent material so the (now deleted) section disappears
        // from its embedded `sections` field.
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
}
