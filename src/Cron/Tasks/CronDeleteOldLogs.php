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

use App\Services\LegacyEnvironment;

class CronDeleteOldLogs implements CronTaskInterface
{
    private \cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?\DateTimeImmutable $lastRun): void
    {
        $logArchiveManager = $this->legacyEnvironment->getLogArchiveManager();
        $roomManager = $this->legacyEnvironment->getRoomManager();

        $logArchiveManager->resetLimits();
        $roomManager->setContextLimit('');
        $roomManager->setLogArchiveLimit();
        $roomIds = $roomManager->getIDs();

        $logArchiveManager->deleteByContextArray($roomIds);
    }

    public function getSummary(): string
    {
        return 'Delete old log entries';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}
