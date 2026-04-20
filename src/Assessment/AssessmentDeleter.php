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
 * Soft- and hard-delete service for `assessments` rows.
 *
 * Assessments are a per-user star/thumbs-up-style rating attached to an
 * existing rubric item (material, todo, discussion, announcement, …).
 * They are **not a rubric** on their own: there is no UI that navigates
 * to an assessment, they carry no annotations / links / file attachments,
 * they don't appear in any room-content listing, and they are not indexed
 * in Elasticsearch. They are an auxiliary data point that *adheres* to a
 * rubric item — similar in role to `tasks` (system workflow artefact) or
 * `link_modifier_item` (modifier history).
 *
 * That shape is why this class deliberately lives outside
 * {@see \App\Rubric} and does **not** implement {@see \App\Rubric\RubricDeleter}:
 *
 *  - No `app.rubric.deleter` tag — {@see \App\Rubric\RubricHardDeleter}
 *    and {@see \App\Rubric\UserContentDeleter} iterate only rubric-primary
 *    types. Assessments get wired in explicitly at their two call sites
 *    (cron + user-footprint erasure) instead.
 *  - No `rubricType()` — there is no `RubricType` enum case for
 *    assessments (by design; the legacy type string `'assessments'` maps
 *    to this auxiliary slot, not to a managed rubric).
 *  - No `ItemDeletedEvent` dispatch — assessments are not indexed, do
 *    not trigger mail notifications, and carry no etherpad / file-storage
 *    side effects. The event would be filtered out on type anyway
 *    ({@see \App\EventSubscriber\ElasticaSubscriber}).
 *  - No aux cascade (`link_items` / `links` / `file_links` /
 *    `annotations`). The rubric deleters run these cleanup helpers
 *    because rubric items extend `cs_item` and *could* carry
 *    attachments; assessments cannot — `cs_assessments_manager::_newAssessment()`
 *    never writes to any of those tables, and no UI lets the user attach
 *    anything to a rating.
 *
 * Schema (see `initial.sql`): `item_id`, `context_id`, `creator_id`,
 * `deleter_id`, `creation_date`, `deletion_date`, `item_link_id` (id of
 * the rated item), `assessment` (the integer value). Note the absence of
 * `modifier_id`: ratings are set once, edits overwrite the value in place
 * without bookkeeping — hence {@see nullifyReferencesInContext()} only
 * touches `creator_id`.
 *
 * Legacy parity: `cs_assessments_manager::delete()` was inlined into
 * {@see \App\Utils\AssessmentService::removeRating} during #5082 (commit
 * `ed718935e`) to unblock the legacy-manager removal. This class lifts
 * that inline SQL back out of the service so the caller stays a thin
 * wrapper and the cleanup has a single owner.
 *
 * **Hard-delete gap closed**: the legacy cascade had no
 * `deleteReallyOlderThan` on `cs_assessments_manager`, so soft-deleted
 * assessment rows accumulated forever. {@see hardDeleteOlderThan()} is
 * wired into {@see \App\Cron\Tasks\CronHardDelete} and closes that gap.
 */
class AssessmentDeleter
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Soft-deletes a single assessment row plus its shared `items` twin.
     *
     * The legacy cascade `cs_assessments_manager::delete()` did exactly
     * these two UPDATEs (via `parent::delete()` for the items row); we
     * keep parity.
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
     * given context, one cascade at a time.
     *
     * Called from {@see \App\Rubric\UserContentDeleter::eraseUserFootprint}
     * in CASCADE_ITEMS mode. Mirrors the pattern used for user-owned tasks
     * in the same method — an auxiliary sweep parallel to the rubric
     * deleter iteration, not inside it.
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
     * in the given context, regardless of soft-delete state.
     *
     * In KEEP_ITEMS strategy this is the only cleanup that happens: the
     * numeric rating values survive and continue to contribute to the
     * rated item's average, authorship simply vanishes. Matches the
     * semantic the rubric deleters apply to their own rows.
     *
     * Only `creator_id` — the `assessments` schema has no `modifier_id`
     * column.
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
     * Physically removes every `assessments` row whose `deletion_date`
     * is older than the given grace period.
     *
     * The shared `items` twin row is **not** touched here — it is swept
     * as part of the common items-table cutoff in
     * {@see \App\Legacy\LegacyAuxHardDeleter::hardDeleteItemsRows()},
     * identical to how every rubric deleter's `hardDeleteOlderThan()`
     * leaves the items sweep to the shared aux path.
     *
     * Returns the number of rows physically removed (for logging / tests).
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
