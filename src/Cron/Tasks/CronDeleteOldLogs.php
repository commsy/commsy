<?php

namespace App\Cron\Tasks;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronDeleteOldLogs implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
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