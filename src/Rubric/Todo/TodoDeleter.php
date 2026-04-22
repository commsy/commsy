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

namespace App\Rubric\Todo;

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
 * Deletes Todo items. Replaces the legacy `cs_todo_item::delete()` /
 * `cs_step_item::delete()` cascade. Beyond the generic `RubricDeleter`
 * contract, exposes {@see deleteStep()} for the UI delete-single-step flow.
 */
class TodoDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Todo;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM todos
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Only top-level todos; steps are soft-deleted transitively by
     * {@see softDeleteItem()}.
     */
    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM todos
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Soft-deletes the todo and every step it contains in one go.
     */
    public function softDeleteItem(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        $stepIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM step
                WHERE todo_item_id = :todoId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['todoId' => $itemId]
        ));

        if (!empty($stepIds)) {
            $this->connection->executeStatement(
                'UPDATE step
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $stepIds],
                ['ids' => ArrayParameterType::INTEGER]
            );

            $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($stepIds, $deleterId);
        }

        $this->connection->executeStatement(
            'UPDATE todos
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // Todos don't carry annotations (same as discussions).
        $this->rubricDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteFileLinks($itemId);
        $this->rubricDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Deletes a single step. Steps are flat, so always a regular
     * soft-delete. After the step is gone, the parent todo gets a re-index
     * event so the removed step disappears from its embedded `steps` field.
     */
    public function deleteStep(int $stepId, int $deleterId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT todo_item_id FROM step
                WHERE item_id = :id AND deleter_id IS NULL AND deletion_date IS NULL',
            ['id' => $stepId]
        );
        if ($row === false) {
            return; // already gone
        }

        $todoId = (int) $row['todo_item_id'];

        $this->connection->executeStatement(
            'UPDATE step
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :id',
            ['deleterId' => $deleterId, 'id' => $stepId]
        );

        $this->rubricDeletionHelper->softDeleteLinks($stepId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($stepId, $deleterId);
        $this->rubricDeletionHelper->softDeleteFileLinks($stepId);
        $this->rubricDeletionHelper->softDeleteItemsRow($stepId, $deleterId);

        // Re-index parent todo so the removed step disappears from its
        // embedded `steps` field.
        $todo = $this->itemService->getTypedItem($todoId);
        if ($todo !== null) {
            $this->eventDispatcher->dispatch(new ItemReindexEvent($todo), ItemReindexEvent::class);
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE todos SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE todos SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    /**
     * Sweeps both `todos` and `step`. Legacy only covered `todos`, leaving
     * expired steps orphaned — gap closed here.
     */
    public function hardDeleteOlderThan(int $days): int
    {
        $count = 0;

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM step
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        $count += (int) $this->connection->executeStatement(
            'DELETE FROM todos
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        return $count;
    }
}
