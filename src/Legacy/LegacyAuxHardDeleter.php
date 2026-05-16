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
     * Tables whose `creator_id` column references `user.item_id` semantically
     * (no FK constraint) and therefore needs to be nulled in application
     * code before a user row is hard-deleted. Mirrors `App\Database\FixCreator`
     * one-to-one. `room` and `user` are NOT in this list — their FK on
     * `creator_id` is enforced and {@see hardDeleteUserRows} handles them
     * with dedicated UPDATEs.
     */
    private const NON_FK_TABLES_WITH_CREATOR_REF = [
        'annotations', 'announcement', 'assessments', 'dates', 'discussionarticles',
        'discussions', 'files', 'labels', 'link_items', 'materials', 'section',
        'server', 'step', 'tag', 'tag2tag', 'tasks', 'todos',
    ];

    /**
     * Tables whose `modifier_id` column references `user.item_id` semantically
     * (no FK constraint). Mirrors `App\Database\FixModifier`. `room` and `user`
     * are again excluded — handled by their FKs.
     */
    private const NON_FK_TABLES_WITH_MODIFIER_REF = [
        'annotations', 'announcement', 'dates', 'discussionarticles',
        'discussions', 'labels', 'materials', 'section',
        'server', 'step', 'tag', 'tag2tag', 'todos',
    ];

    /**
     * Hard-deletes soft-deleted `user` rows older than the retention
     * window — the personally-identifying parts of the row (firstname,
     * lastname, email, city, …) live nowhere else, so this is the
     * data-erasure step.
     *
     * Two classes of incoming references need handling before the
     * `DELETE FROM user` can run:
     *
     *  1. FK-enforced refs on `user.item_id` (`room.creator_id`,
     *     `room.modifier_id`, plus the `user` self-refs for
     *     `creator_id` / `modifier_id`). Without nulling these the
     *     database would refuse the DELETE.
     *  2. Semantic refs on the tables that use
     *     {@see \App\Utils\EntityUsersTrait} but carry no FK constraint
     *     (`tasks.creator_id`, `dates.creator_id`, `annotations.modifier_id`,
     *     …). The DB wouldn't reject the DELETE, but leaving the refs
     *     dangling makes Doctrine ORM proxies throw
     *     `EntityNotFoundException` whenever later code touches them —
     *     Elastica reindex, "created by" UI labels, exports.
     *
     * We do (1) in application code (not via `ON DELETE SET NULL` on the
     * FKs) because `creator_id`/`modifier_id` are mapped through the trait
     * shared with 14 other entities whose tables have no FK at all;
     * declaring `onDelete` in the trait would propagate a schema intent
     * the database does not enforce on those tables.
     *
     * `link_modifier_item` is a pure join table — there is no domain row
     * to keep alive without its user reference, so DELETE (not nulling)
     * is the right action.
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

        // Non-FK semantic refs — same INNER-JOIN-with-retention pattern as
        // above, applied to every table that uses EntityUsersTrait. Without
        // these, Doctrine proxies on $entity->getCreator() / getModifier()
        // throw EntityNotFoundException after the user row is gone.
        foreach (self::NON_FK_TABLES_WITH_CREATOR_REF as $table) {
            $this->connection->executeStatement(
                "UPDATE `$table` t
                    INNER JOIN user u ON t.creator_id = u.item_id
                    SET t.creator_id = NULL
                    WHERE {$retentionClause}",
                ['days' => $days]
            );
        }
        foreach (self::NON_FK_TABLES_WITH_MODIFIER_REF as $table) {
            $this->connection->executeStatement(
                "UPDATE `$table` t
                    INNER JOIN user u ON t.modifier_id = u.item_id
                    SET t.modifier_id = NULL
                    WHERE {$retentionClause}",
                ['days' => $days]
            );
        }
        $this->connection->executeStatement(
            "DELETE t FROM link_modifier_item t
                INNER JOIN user u ON t.modifier_id = u.item_id
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
