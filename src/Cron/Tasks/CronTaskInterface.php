<?php

namespace App\Cron\Tasks;

use DateTimeImmutable;

interface CronTaskInterface
{
    public const PRIORITY_EARLY = 9;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LATE = 1;

    public function run(?DateTimeImmutable $lastRun): void;

    public function getSummary(): string;

    public function getPriority(): int;
}