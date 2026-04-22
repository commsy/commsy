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
 * Finds soft-deleted rooms / portals past the grace period and dispatches
 * physical cleanup. Replaces legacy `cs_room_manager::deleteReallyOlderThan()`
 * and re-activates the portal branch CronHardDelete used to skip.
 */
readonly class RoomHardDeleter
{
    public function __construct(
        private Connection $connection,
        private RoomDeleterRegistry $roomDeleterRegistry,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Returns the number of rooms physically removed.
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
                // Non-canonical type (stray myroom/server, unregistered future type).
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
                // Surface but keep going so one bad row doesn't kill the cron run.
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
     * Physically removes soft-deleted portals past threshold. Non-cascading:
     * the rooms of a deleted portal carry their own `deletion_date` and are
     * purged independently by {@see hardDeleteRoomsOlderThan()}.
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
