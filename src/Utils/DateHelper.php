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

use DateTimeImmutable;

/**
 * Pure date calculations migrated from legacy/functions/date_functions.php.
 *
 * Time-dependent helpers ("now") deliberately live elsewhere so this class
 * stays free of side effects and fully unit-testable.
 */
final class DateHelper
{
    /**
     * Absolute number of whole days between two "Ymd" timestamps.
     *
     * Mirrors the legacy getDifference(): both operands are normalised to
     * midnight, so the result is the exact calendar-day distance regardless of
     * argument order.
     */
    public static function daysBetween(string $startYmd, string $endYmd): int
    {
        $start = DateTimeImmutable::createFromFormat('!Ymd', $startYmd);
        $end = DateTimeImmutable::createFromFormat('!Ymd', $endYmd);

        return (int) $start->diff($end)->format('%a');
    }
}
