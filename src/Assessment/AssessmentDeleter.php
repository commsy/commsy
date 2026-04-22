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

namespace App\Assessment;

use Doctrine\DBAL\Connection;

/**
 * Soft- and hard-delete service for `assessments` rows (per-user ratings
 * attached to a rubric item). Assessments are auxiliary — not a rubric,
 * not indexed, no attachments — hence this class lives outside
 * {@see \App\Rubric} and is wired in explicitly at its call sites
 * (cron + user-footprint erasure).
 *
 * The `assessments` schema has no `modifier_id` column — ratings are set
 * once and overwritten in place, so {@see nullifyReferencesInContext()}
 * only touches `creator_id`.
 *
 * Closes a legacy gap: `cs_assessments_manager` had no
 * `deleteReallyOlderThan`, so soft-deleted rows accumulated forever.
 */
class AssessmentDeleter
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Soft-deletes a single assessment row plus its shared `items` twin.
     */
    public function softDelete(int $itemId, int $deleterId): void
    {
        $this->connection->executeStatement(
            'UPDATE assessments
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->connection->executeStatement(
            'UPDATE items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }

    /**
     * Soft-deletes every alive assessment the given user created in the
     * given context. Called from
     * {@see \App\Rubric\UserContentDeleter::eraseUserFootprint} in
     * CASCADE_ITEMS mode.
     */
    public function softDeleteAssessmentsByUser(int $userId, int $contextId, int $deleterId): void
    {
        $itemIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM assessments
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        ));

        foreach ($itemIds as $itemId) {
            $this->softDelete($itemId, $deleterId);
        }
    }

    /**
     * NULLifies `creator_id` on every assessment the given user authored
     * in the given context. In KEEP_ITEMS mode this is the only cleanup:
     * rating values survive, authorship vanishes.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE assessments SET creator_id = NULL
                WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    /**
     * Physically removes every `assessments` row whose `deletion_date` is
     * older than the grace period. The shared `items` twin row is swept
     * by the common items-table cutoff elsewhere.
     *
     * Returns the number of rows physically removed.
     */
    public function hardDeleteOlderThan(int $days): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM assessments
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
