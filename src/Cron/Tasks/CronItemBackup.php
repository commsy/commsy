<?php

namespace App\Cron\Tasks;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class CronItemBackup implements CronTaskInterface
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
        $backupItemManager = $this->legacyEnvironment->getBackupItemManager();
        $backupItemManager->deleteOlderThan(14);
    }

    public function getSummary(): string
    {
        return 'Delete old item backups';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}