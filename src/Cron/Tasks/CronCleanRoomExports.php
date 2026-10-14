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

namespace App\Cron\Tasks;

use App\Repository\RoomExportRepository;
use DateTimeImmutable;

/**
 * Removes the record of a room export once its archive is gone at the latest.
 *
 * The row names the person who asked for the export, and nothing in it is worth
 * more than the archive it belongs to. The room's page stops listing it at the
 * same moment, see {@see CronCleanTemporary::LATEST_REMOVAL_HOURS}.
 */
class CronCleanRoomExports implements CronTaskInterface
{
    public function __construct(private readonly RoomExportRepository $roomExports)
    {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $this->roomExports->removeRequestedBefore(
            new DateTimeImmutable(sprintf('-%d hours', CronCleanTemporary::LATEST_REMOVAL_HOURS))
        );
    }

    public function getSummary(): string
    {
        return 'Delete records of expired room exports';
    }
}
