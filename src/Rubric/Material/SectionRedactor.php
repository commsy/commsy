<?php

namespace App\Rubric\Material;

use App\Event\ItemReindexEvent;
use App\Rubric\RedactionText;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\SubEntryRedactor;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SectionRedactor implements SubEntryRedactor
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RedactionText $redactionText,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly ItemService $itemService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Sections are versioned: every version of an affected section is
     * replaced, so no older version keeps the original wording.
     */
    public function redactContentOfUser(int $userId, int $contextId): void
    {
        $sectionIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT item_id FROM section
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        ));
        if ($sectionIds === []) {
            return;
        }

        $materialIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT material_item_id FROM section WHERE item_id IN (:ids)',
            ['ids' => $sectionIds],
            ['ids' => ArrayParameterType::INTEGER]
        ));

        $this->connection->executeStatement(
            'UPDATE section
                SET title = :title, description = :description, modification_date = NOW()
                WHERE item_id IN (:ids)',
            [
                'title' => $this->redactionText->title($contextId),
                'description' => $this->redactionText->description($contextId),
                'ids' => $sectionIds,
            ],
            ['ids' => ArrayParameterType::INTEGER]
        );

        // The attachments go with the content — a surviving link would keep
        // the file downloadable through the section that no longer shows it.
        foreach ($sectionIds as $sectionId) {
            $this->rubricDeletionHelper->softDeleteAllFileLinkVersions($sectionId, $userId);
        }

        // The material's search document embeds its sections' text, so the
        // replacement only reaches the index when the material is reindexed.
        $this->reindexMaterials($materialIds);
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE section SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE section SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    /** @param int[] $materialIds */
    private function reindexMaterials(array $materialIds): void
    {
        foreach ($materialIds as $materialId) {
            $material = $this->itemService->getTypedItem($materialId);
            if ($material !== null) {
                $this->eventDispatcher->dispatch(new ItemReindexEvent($material), ItemReindexEvent::class);
            }
        }
    }
}
