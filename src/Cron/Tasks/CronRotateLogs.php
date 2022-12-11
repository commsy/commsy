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

class CronRotateLogs implements CronTaskInterface
{
    private \cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @throws \Exception
     */
    public function run(?\DateTimeImmutable $lastRun): void
    {
        $logManager = $this->legacyEnvironment->getLogManager();
        $logArchiveManager = $this->legacyEnvironment->getLogArchiveManager();

        $logManager->resetLimits();
        $logManager->setContextLimit(0);
        $logManager->setRangeLimit(0, 500);
        $logManager->setTimestampOlderLimit(date('Ymd'));

        $logs = $logManager->select();
        while ((is_countable($logs) ? count($logs) : 0) > 0) {
            if ($logArchiveManager->save($logs)) {
                $logManager->deleteByArray($logs);
            }

            $logs = $logManager->select();
        }
    }

    public function getSummary(): string
    {
        return 'Rotate log entries';
    }

    public function getPriority(): int
    {
        // Must run after all other portal crons
        // Portals calculate activity based on this?!?
        return self::PRIORITY_LATE;
    }
}
