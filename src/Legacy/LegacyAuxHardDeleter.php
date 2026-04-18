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
 * Hard-deletes rows from the auxiliary tables that no rubric deleter owns:
 * `items`, `link_items`, `tag`, `tag2tag`, `tasks`. Replaces the
 * `$manager->deleteReallyOlderThan($days)` loop that `CronHardDelete`
 * previously ran against those tables' legacy managers.
 *
 * Not included here:
 *  - `files` / `item_link_file`: ports to {@see \App\Files\FileDeleter::hardDeleteExpiredFiles()}
 *    because they carry filesystem side-effects.
 *  - Rubric-primary tables (announcement, dates, …): owned by the respective
 *    `RubricHardDeleter` registrations.
 *  - Room / portal tables: owned by `RoomHardDeleter`.
 *
 * All deletes use the same cutoff as `cs_manager::deleteReallyOlderThan()`:
 * `deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)`. The
 * `items` sweep additionally preserves the legacy `type != 'user'` filter
 * (see the method docblock).
 */
class LegacyAuxHardDeleter
{
    private readonly Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Parity with the legacy `cs_item_manager::deleteReallyOlderThan()`
     * override, which keeps `type = 'user'` rows pinned.
     *
     * @todo #5082 follow-up: membership-leave does not currently nullify
     *       creator/modifier/assignee references on items the user authored
     *       (only account-delete via {@see \App\Rubric\UserContentDeleter}
     *       walks that trail). Hard-deleting a soft-deleted user here would
     *       leave dangling FKs in those content rows, so the filter stays
     *       until the nullification trail covers the membership-leave flow
     *       too. Conceptual question tracked for the Phase-3 design pass.
     */
    public function hardDeleteItemsRows(int $days): void
    {
        $this->connection->executeStatement(
            'DELETE FROM items
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)
                  AND type != :userType',
            ['days' => $days, 'userType' => CS_USER_TYPE]
        );
    }

    /**
     * Sweeps `link_items` — covers what the legacy loop did via both
     * `CS_LINK_TYPE` and `CS_LINKITEM_TYPE` (both resolve to `cs_link_manager`
     * with `_db_table = 'link_items'`, so the legacy code called the same
     * DELETE twice). One call is enough.
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
