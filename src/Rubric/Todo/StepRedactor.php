<?php

namespace App\Rubric\Todo;

use App\Event\ItemReindexEvent;
use App\Rubric\RedactionText;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\SubEntryRedactor;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StepRedactor implements SubEntryRedactor
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RedactionText $redactionText,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly ItemService $itemService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * `minutes` is the person's own time entry and is reset alongside
     * title and description.
     */
    public function redactContentOfUser(int $userId, int $contextId): void
    {
        $stepIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM step
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        ));
        if ($stepIds === []) {
            return;
        }

        $todoIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT todo_item_id FROM step WHERE item_id IN (:ids)',
            ['ids' => $stepIds],
            ['ids' => ArrayParameterType::INTEGER]
        ));

        $this->connection->executeStatement(
            'UPDATE step
                SET title = :title, description = :description, minutes = 0, modification_date = NOW()
                WHERE item_id IN (:ids)',
            [
                'title' => $this->redactionText->title($contextId),
                'description' => $this->redactionText->description($contextId),
                'ids' => $stepIds,
            ],
            ['ids' => ArrayParameterType::INTEGER]
        );

        foreach ($stepIds as $stepId) {
            $this->rubricDeletionHelper->softDeleteFileLinks($stepId, $userId);
        }

        // The todo's search document embeds its steps' text.
        foreach ($todoIds as $todoId) {
            $todo = $this->itemService->getTypedItem($todoId);
            if ($todo !== null) {
                $this->eventDispatcher->dispatch(new ItemReindexEvent($todo), ItemReindexEvent::class);
            }
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE step SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE step SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
