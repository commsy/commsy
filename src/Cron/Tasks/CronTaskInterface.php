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
