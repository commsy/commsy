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

namespace App\Legacy;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Hard-deletes rows from auxiliary tables that no rubric deleter owns:
 * `items`, `link_items`, `tag`, `tag2tag`, `tasks`. Replaces the
 * `$manager->deleteReallyOlderThan($days)` loop that `CronHardDelete`
 * previously ran against those tables' legacy managers.
 */
class LegacyAuxHardDeleter
{
    private readonly Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Hard-deletes soft-deleted rows from `items` older than the retention
     * window, including `type = 'user'` rows (those are the items twin of
     * a hard-deleted user row — {@see hardDeleteUserRows} runs first and
     * deletes the `user` child, leaving the items parent ready to go).
     */
    public function hardDeleteItemsRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM items
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }

    /**
     * Hard-deletes soft-deleted `user` rows older than the retention
     * window — the personally-identifying parts of the row (firstname,
     * lastname, email, city, …) live nowhere else, so this is the
     * data-erasure step.
     *
     * The four FK-enforced incoming references on `user.item_id`
     * (`room.creator_id`, `room.modifier_id`, plus the `user`
     * self-references for `creator_id` / `modifier_id`) are nullified
     * first; without that the `DELETE FROM user` would be refused by
     * the database. We do this in application code rather than via
     * `ON DELETE SET NULL` on the FKs because `creator_id`/`modifier_id`
     * are mapped through {@see \App\Utils\EntityUsersTrait}, which is
     * shared with 14 other entities whose tables carry no FK constraint
     * on those columns at all — declaring `onDelete` in the trait would
     * propagate a schema intent that the database does not enforce.
     *
     * Semantic references on other tables (e.g. `dates.creator_id`,
     * `todos.creator_id`, …) are not FK-constrained and therefore not
     * blocked — they may end up pointing at non-existent ids, but they
     * no longer expose personal data because the personal data was on
     * the `user` row itself.
     */
    public function hardDeleteUserRows(int $days): void
    {
        $retentionClause = 'u.deletion_date IS NOT NULL
                              AND u.deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)';

        $this->connection->executeStatement(
            "UPDATE room r
                INNER JOIN user u ON r.creator_id = u.item_id
                SET r.creator_id = NULL
                WHERE {$retentionClause}",
            ['days' => $days]
        );
        $this->connection->executeStatement(
            "UPDATE room r
                INNER JOIN user u ON r.modifier_id = u.item_id
                SET r.modifier_id = NULL
                WHERE {$retentionClause}",
            ['days' => $days]
        );

        // user self-refs — alias the source row as `u`, the referring row as
        // `u1`, so the shared `{$retentionClause}` (which qualifies on `u`)
        // matches the source.
        $this->connection->executeStatement(
            "UPDATE user u1
                INNER JOIN user u ON u1.creator_id = u.item_id
                SET u1.creator_id = NULL
                WHERE {$retentionClause}",
            ['days' => $days]
        );
        $this->connection->executeStatement(
            "UPDATE user u1
                INNER JOIN user u ON u1.modifier_id = u.item_id
                SET u1.modifier_id = NULL
                WHERE {$retentionClause}",
            ['days' => $days]
        );

        $this->connection->executeStatement(
            'DELETE FROM user
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }

    /**
     * Sweeps `link_items`. Legacy ran the same DELETE twice (via CS_LINK_TYPE
     * and CS_LINKITEM_TYPE, both pointing at cs_link_manager); one call suffices.
     */
    public function hardDeleteLinkItemRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM link_items
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }

    public function hardDeleteTagRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tag
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }

    public function hardDeleteTag2TagPivotRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tag2tag
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }

    public function hardDeleteTaskRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tasks
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
