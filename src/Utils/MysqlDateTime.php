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

namespace App\Utils;

use function Symfony\Component\Clock\now;

/**
 * Current time in the string formats the (legacy) code base expects.
 *
 * Drop-in replacement for the global functions getCurrentDateTimeInMySQL() and
 * getCurrentDate() from legacy/functions/date_functions.php. Built on Symfony's
 * Clock component: now() reads the globally configured clock, so the result is
 * deterministic in tests (ClockSensitiveTrait / MockClock) without having to
 * inject a clock into every caller.
 */
final class MysqlDateTime
{
    /**
     * Current timestamp as MySQL datetime, e.g. "2024-03-15 14:30:45".
     * Replaces getCurrentDateTimeInMySQL().
     */
    public static function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Current date as a compact "Ymd" stamp, e.g. "20240315".
     * Replaces getCurrentDate().
     */
    public static function today(): string
    {
        return now()->format('Ymd');
    }
}
