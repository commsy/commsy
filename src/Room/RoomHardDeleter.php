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

namespace App\Room;

use Doctrine\DBAL\Connection;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Finds soft-deleted rooms / portals past the configured grace period and
 * dispatches their physical cleanup. Replaces the legacy
 * {@see \cs_room_manager::deleteReallyOlderThan()} path and re-activates
 * the portal branch that {@see \App\Cron\Tasks\CronHardDelete} used to
 * skip (commented-out "not implemented yet").
 *
 * Two public entry points, one per container type:
 *
 *  - {@see hardDeleteRoomsOlderThan()} reads every `room` row whose
 *    `deletion_date` is older than `$days`, looks up the matching
 *    {@see RoomDeleter} via {@see RoomDeleterRegistry}, and calls its
 *    {@see RoomDeleter::hardDeleteRoom()}. The per-type adapter does
 *    nothing more than delegate to {@see RoomHardDeletionHelper} — but
 *    we route through the registry so that any type-specific physical
 *    cleanup (added later without touching this orchestrator) lands in
 *    the right place.
 *
 *  - {@see hardDeletePortalsOlderThan()} runs a *non-cascading* DELETE on
 *    the `portal` table for rows past threshold. A soft-deleted portal's
 *    constituent rooms have their own `deletion_date` and are picked up
 *    by the room path above, so cascading here would double-delete. The
 *    legacy `CronHardDelete` skipped portals entirely because
 *    `CS_PORTAL_TYPE` was never defined as a PHP constant (see
 *    `legacy/etc/cs_constants.php`); we do not try to reinstate the
 *    constant and instead run a direct DBAL delete on the `portal`
 *    table, which is the only artefact that legacy would have removed.
 *
 * Row counts are returned for logging / summary output; the service is
 * otherwise fire-and-forget.
 */
readonly class RoomHardDeleter
{
    public function __construct(
        private Connection $connection,
        private RoomDeleterRegistry $roomDeleterRegistry,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Returns the number of rooms that were physically removed.
     *
     * Iteration is driven by a DBAL query against the `room` table
     * rather than the ORM (`App\Entity\Room`), because most hard-delete
     * work lands on tables that have no managed entity — and keeping
     * the loop cursor-like avoids loading every soft-deleted room into
     * memory in a portal-wide cleanup run.
     */
    public function hardDeleteRoomsOlderThan(int $days): int
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT item_id, context_id, type
                FROM room
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );

        $count = 0;
        foreach ($rows as $row) {
            $roomId = (int) $row['item_id'];
            $type = (string) $row['type'];

            $roomType = RoomType::tryFromLegacyString($type);
            if ($roomType === null) {
                // Legacy room rows with non-canonical types — e.g. a
                // stray `myroom` or `server` that should never have
                // ended up in the `room` table in the first place, or
                // a future type a new migration forgot to register.
                // Log and skip rather than hard-failing the whole cron
                // run.
                $this->logger->warning(sprintf(
                    'RoomHardDeleter: skipping room %d with unsupported type "%s".',
                    $roomId,
                    $type
                ));
                continue;
            }

            try {
                $this->roomDeleterRegistry->forType($roomType)->hardDeleteRoom($roomId);
                $count++;
            } catch (LogicException $e) {
                // A deleter may refuse (e.g. because a prerequisite soft-
                // delete check fails). Surface but keep going.
                $this->logger->error(sprintf(
                    'RoomHardDeleter: failed to hard-delete room %d (type %s): %s',
                    $roomId,
                    $type,
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }

    /**
     * Physically removes soft-deleted portals past threshold. Intentionally
     * *non-cascading*: the rooms that belonged to a deleted portal carry
     * their own `deletion_date` and are purged through
     * {@see hardDeleteRoomsOlderThan()} independently. This mirrors the
     * historical behaviour — legacy never had a portal hard-delete path
     * at all, the branch in `CronHardDelete` was commented out.
     *
     * Returns the number of `portal` rows removed.
     */
    public function hardDeletePortalsOlderThan(int $days): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM portal
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
