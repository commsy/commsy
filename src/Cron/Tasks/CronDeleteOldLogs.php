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

use App\Repository\LogRepository;
use DateTimeImmutable;

readonly class CronDeleteOldLogs implements CronTaskInterface
{
    public function __construct(
        private LogRepository $logRepository
    ) {}

    public function run(?DateTimeImmutable $lastRun): void
    {
        $this->logRepository->deleteOlderThen(50);
    }

    public function getSummary(): string
    {
        return 'Delete old log entries';
    }
}
