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

    /**
     * Formats a MySQL datetime to the localized date (replaces the legacy
     * cs_translator::getDateInLang()): de "d.m.Y", en "m/d/Y".
     */
    public static function formatDate(string $datetime, string $locale): string
    {
        if ('' === trim($datetime)) {
            return '';
        }

        $date = new DateTimeImmutable($datetime);

        return 'de' === $locale ? $date->format('d.m.Y') : $date->format('m/d/Y');
    }

    /**
     * Formats a "HH:MM"-style time string to the localized time (replaces the legacy
     * cs_translator::getTimeLanguage()): de "H:i", en "h:i am/pm".
     */
    public static function formatTime(string $timestring, string $locale): string
    {
        if (2 === mb_substr_count($timestring, ':')) {
            $hour = $timestring[0].$timestring[1];
            $min = $timestring[3].$timestring[4];
        } else {
            $hour = $timestring[0].$timestring[1];
            $min = $timestring[2].$timestring[3];
        }

        if ('de' !== $locale) {
            $ampm = ' am';
            if ($hour > 12) {
                $hour -= 12;
                $ampm = ' pm';
            } elseif (12 == $hour) {
                $ampm = ' pm';
            }
            if (1 === mb_strlen((string) $hour)) {
                $hour = '0'.$hour;
            }

            return $hour.':'.$min.$ampm;
        }

        return $hour.':'.$min;
    }

    /**
     * Parses a user time input ("14:30", "2pm", "10ct", …) into its parts.
     * Migrated from the legacy global convertTimeFromInput(); pure, no translator.
     *
     * @return array{conforms: bool, timestamp: string, datetime: string, display: string}
     */
    public static function convertTimeFromInput($time): array
    {
        $original = $time;
        $time = trim(str_replace(' ', '', (string) $time));

        $hours = '00';
        $minutes = '00';
        $secs = '00';
        $ampm = '';
        $stct = '';
        $conforms = false;

        if (preg_match('~^([01]?[0-9]|2[0-3])([\.:]([0-5]?[0-9]))?([\.:]([0-5]?[0-9]))?(am|pm)?((s|c)\.?t\.?)?$~iu', $time, $matches)) {
            $hours = $matches[1];
            if (!empty($matches[3])) {
                $minutes = $matches[3];
            }
            if (!empty($matches[5])) {
                $secs = $matches[5];
            }
            if (!empty($matches[6])) {
                $ampm = $matches[6];
            }
            if (!empty($matches[7])) {
                $stct = $matches[7];
            }

            if (($hours < 12) && ($hours >= 1) && ('pm' === $ampm)) {
                $hours += 12;
            }
            if (($hours >= 12) && ($hours <= 23) && ('am' === $ampm)) {
                $hours -= 12;
            }

            if ('st' === $stct) {
                $minutes = '00';
            } elseif ('ct' === $stct) {
                $minutes = '15';
            }

            $conforms = true;
        }

        if (!$conforms) {
            return ['conforms' => false, 'timestamp' => '000000', 'datetime' => '00:00:00', 'display' => $original];
        }

        return [
            'conforms' => true,
            'timestamp' => str_pad((string) $hours, 2, '0', STR_PAD_LEFT).str_pad($minutes, 2, '0', STR_PAD_LEFT).$secs,
            'datetime' => str_pad((string) $hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.$secs,
            'display' => empty($stct) ? '' : $stct,
        ];
    }
}
