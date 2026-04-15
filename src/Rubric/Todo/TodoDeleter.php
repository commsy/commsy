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
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Todo items without delegating to the legacy
 * `cs_todo_item::delete()` / `cs_step_item::delete()` cascade.
 *
 * In addition to the generic `RubricDeleter` contract (ganzes Todo inkl.
 * aller Steps) this class exposes {@see deleteStep()} for the UI
 * delete-single-step flow. Steps are flat (no hierarchy like
 * discussionarticles) — a step is always a leaf, so we never need the
 * DSGVO-purge path that exists for discussion articles. A single-step
 * delete re-indexes the parent todo so the removed step disappears from
 * its embedded `steps` field in the `commsy_todo` index.
 */
class TodoDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricKey(): string
    {
        return 'todo';
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
     * Soft-deletes the todo **and every step it contains** in one go.
     *
     * Cascading the whole tree means we do not dispatch per-step events —
     * steps have no own ES index and the parent todo is removed via
     * `ItemDeletedEvent` anyway.
     */
    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Dispatch the deletion event. ElasticaSubscriber removes the todo
        //    document from `commsy_todo`; the step data it embedded dies with it.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Collect all alive step ids that belong to this todo.
        $stepIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM step
                WHERE todo_item_id = :todoId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['todoId' => $itemId]
        ));

        // 3. Soft-delete every step row in a single UPDATE + batch-clean aux rows.
        //    Fixes a legacy inconsistency: cs_step_manager::delete() cleaned
        //    link_items but not `links` — softDeleteAuxiliaryRowsForItems()
        //    handles both uniformly.
        if (!empty($stepIds)) {
            $this->connection->executeStatement(
                'UPDATE step
                    SET deletion_date = NOW(), deleter_id = :deleterId
                    WHERE item_id IN (:ids)',
                ['deleterId' => $deleterId, 'ids' => $stepIds],
                ['ids' => ArrayParameterType::INTEGER]
            );

            $this->itemDeletionHelper->softDeleteAuxiliaryRowsForItems($stepIds, $deleterId);
        }

        // 4. Soft-delete the `todos` row itself.
        $this->connection->executeStatement(
            'UPDATE todos
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 5. Auxiliary cleanup for the todo. No annotations: todos don't carry
        //    annotations in the legacy model (same as discussions).
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Deletes a single step.
     *
     * Steps are flat, so this is always a regular soft-delete (unlike
     * discussion articles, there is no "with children"-purge-path). After
     * the step is gone, the parent todo gets a re-index event so the
     * removed step disappears from its embedded `steps` field.
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

        $this->itemDeletionHelper->softDeleteLinks($stepId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($stepId, $deleterId);
        $this->itemDeletionHelper->softDeleteFileLinks($stepId);
        $this->itemDeletionHelper->softDeleteItemsRow($stepId, $deleterId);

        // Re-index the parent todo so the removed step disappears from its
        // embedded `steps` field. ElasticaSubscriber::onItemReindex picks
        // this up; ReadStatusSubscriber invalidates the read-status cache.
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
}
