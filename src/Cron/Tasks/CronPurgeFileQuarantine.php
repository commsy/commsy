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

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Files\Cleanup\FileCleanupLock;
use App\Files\Cleanup\FileQuarantine;
use DateTimeImmutable;

/**
 * Removes for good what the file cleanup moved into the quarantine, once its
 * run is older than {@see FileQuarantine::RETENTION_DAYS} days.
 */
class CronPurgeFileQuarantine implements CronTaskInterface
{
    public function __construct(private readonly FileQuarantine $quarantine)
    {
    }

    /**
     * Waits for nothing: while a cleanup or a restore runs, the next night
     * purges instead.
     */
    public function run(?DateTimeImmutable $lastRun): void
    {
        $lock = FileCleanupLock::create();
        if (!$lock->acquire()) {
            return;
        }

        try {
            $this->quarantine->purge();
        } finally {
            $lock->release();
        }
    }

    public function getSummary(): string
    {
        return 'Purge the file quarantine';
    }
}
