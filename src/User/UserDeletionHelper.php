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

namespace App\User;

use App\Rubric\RubricDeletionHelper;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * User-scoped soft-delete primitives — tasks and related reference
 * nullifications. Complements {@see \App\Rubric\RubricDeletionHelper}
 * (item-scoped) and {@see \App\Room\RoomDeletionHelper} (room-scoped).
 */
class UserDeletionHelper
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
    ) {}

    /**
     * Soft-deletes every task created by `$userId` in `$contextId`, plus
     * the task's annotations and aux rows. Flips `status = 'CLOSED'` so
     * moderator UIs don't surface ghost rows. Parity with `cs_task_item::delete()`.
     *
     * @todo Prüfen, ob die `tasks`-Tabelle perspektivisch entsorgt werden
     *       kann — der User-Request-Workflow ließe sich auch ohne eigene
     *       Tabelle modellieren.
     */
    public function deleteUserTasks(int $userId, int $contextId, int $deleterId): void
    {
        $taskIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM tasks
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        ));
        if (empty($taskIds)) {
            return;
        }

        $this->connection->executeStatement(
            "UPDATE tasks
                SET deletion_date = NOW(), deleter_id = :deleterId, status = 'CLOSED'
                WHERE item_id IN (:ids)",
            ['deleterId' => $deleterId, 'ids' => $taskIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        foreach ($taskIds as $taskId) {
            $this->rubricDeletionHelper->softDeleteAnnotations($taskId, $deleterId);
        }

        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($taskIds, $deleterId);
    }

    /**
     * NULLifies `tasks.creator_id` references to `$userId` within `$contextId`.
     * Called on both CASCADE_ITEMS and KEEP_ITEMS so surviving tasks become
     * author-less. The `tasks` table has no `modifier_id` column.
     */
    public function nullifyUserTaskReferences(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE tasks SET creator_id = NULL
                WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
