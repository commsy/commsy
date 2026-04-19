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
 * User-scoped soft-delete primitives — complementary to
 * {@see \App\Rubric\RubricDeletionHelper}.
 *
 * Where `RubricDeletionHelper` operates on a single rubric item and its
 * auxiliary rows, this class deals with artefacts that hang off a **user**
 * identity inside a room context: the `tasks` table (user-request workflow)
 * and related reference nullifications triggered when a user leaves a room
 * (see {@see UserMembershipDeleter}) or is wiped by KEEP_ITEMS strategy.
 *
 * Tasks are deliberately not modelled as a rubric — they are a system workflow
 * artefact (TASK_USER_REQUEST on moderated-room applications and similar
 * legacy entries). Keeping them here, next to other user-scoped cleanup,
 * makes the bounded context explicit.
 *
 * Naming: mirrors {@see \App\Rubric\RubricDeletionHelper} and
 * {@see \App\Room\RoomDeletionHelper}. Same `…DeletionHelper` suffix, same
 * role: primitives consumed by leaf deleters, never the delete interface
 * itself.
 *
 * Composition with `RubricDeletionHelper`: this class does not re-implement
 * the aux-row cascade for the task rows it soft-deletes. It delegates to
 * `RubricDeletionHelper::softDeleteAnnotations()` + `…softDeleteAuxiliary
 * RowsForItems()` — a task has annotations and link_items / items-twin rows
 * just like any other item, and the rubric helper is the canonical place
 * for that cleanup. Keeping the two classes coupled by composition (not
 * inheritance, not a shared trait) keeps the bounded contexts explicit
 * while reusing the primitives.
 *
 * (Split from `App\Rubric\ItemDeletionHelper` in the post-#5082 consolidation
 * pass so rubric- and user-scoped primitives live in separate classes.)
 */
class UserDeletionHelper
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
    ) {}

    /**
     * Soft-deletes every task created by `$userId` in `$contextId`, along
     * with the task's annotations and auxiliary rows (link_items, links,
     * file_links, items twin).
     *
     * Mirrors the legacy `cs_task_item::delete()` behaviour including the
     * `status = 'CLOSED'` flip (so moderator UIs that still look at open
     * requests never surface ghost rows of deleted users).
     *
     * @todo Prüfen, ob die `tasks`-Tabelle perspektivisch entsorgt werden
     *       kann — der User-Request-Workflow ließe sich auch ohne eigene
     *       Tabelle modellieren. Solange sie bleibt, ist das hier der
     *       einzige aktive Löschpfad in der neuen Architektur.
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
     * Called unconditionally (both CASCADE_ITEMS and KEEP_ITEMS strategies)
     * so surviving tasks of the deleted user become author-less. The `tasks`
     * table has no `modifier_id` column (see initial.sql), so creator is the
     * only reference to clean.
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
