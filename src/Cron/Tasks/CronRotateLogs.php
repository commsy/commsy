<?php

namespace App\Cron\Tasks;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Exception;

class CronRotateLogs implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @throws Exception
     */
    public function run(?DateTimeImmutable $lastRun): void
    {
        $logManager = $this->legacyEnvironment->getLogManager();
        $logArchiveManager = $this->legacyEnvironment->getLogArchiveManager();

        $logManager->resetLimits();
        $logManager->setContextLimit(0);
        $logManager->setRangeLimit(0, 500);
        $logManager->setTimestampOlderLimit(date("Ymd"));

        $logs = $logManager->select();
        while (count($logs) > 0) {
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