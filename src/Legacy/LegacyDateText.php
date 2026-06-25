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

namespace App\Legacy;

use cs_environment;

/**
 * Locale-aware date parsing/naming used by the legacy date rendering.
 *
 * Interim Legacy->App bridge: holds the bodies of the former global functions
 * convertDateFromInput()/getShortMonthNameToInt()/getDayNameFromInt() from
 * legacy/functions/date_functions.php so that file can be removed. These need
 * localised month/weekday names, so the legacy cs_environment is passed in
 * (instead of the old global $environment) — which also makes them testable.
 */
final class LegacyDateText
{
    /**
     * Parses a user date input into conforms/timestamp/datetime/display/error parts.
     * Migrated from convertDateFromInput().
     *
     * @return array<string, mixed>
     */
    public static function convertDate($date, $language, cs_environment $environment): array
    {
        $year = null;
        $month = null;
        $day = null;
        $converted = [];
        $matches = [];
        $original = $date;
        $date = str_replace(' ', '', (string) $date);

        $region = match ($language) {
            'en' => 'british',
            default => 'europe',
        };

        // set month/year/date depending on region
        if ('europe' == $region) {
            // TT.MM.YYYY
            $pattern = '~([0-9]{1,2})([./])([0-9]{1,2})([./]([0-9]{1,4}))?~u';
            if (preg_match($pattern.'i', $date, $matches)) {
                if (!empty($matches[5])) {
                    $year = $matches[5];
                } else {
                    $year = date('Y');
                }
                $month = $matches[3];
                $day = $matches[1];
            }
        } elseif ($region = 'british') {
            // MM/TT/YYYY
            $pattern = '~([0-9]{1,2})([./])([0-9]{1,2})([./]([0-9]{1,4}))?~u';
            if (preg_match($pattern.'i', $date, $matches)) {
                if (!empty($matches[5])) {
                    $year = $matches[5];
                } else {
                    $year = date('Y');
                }
                $month = $matches[1];
                $day = $matches[3];
            }
        }
        // try DB time format if not succesfull yet
        if (empty($matches)) {
            $pattern = '~([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})~u';
            if (preg_match($pattern.'i', $date, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
            }
        }
        if (empty($matches)) {
            // try TT Mon YYYY format if not succesfull yet
            $pattern = '~([0-9]{1,2})([A-Za-z]{1,4})([0-9]{1,4})?~u';
            if (preg_match($pattern.'i', $date, $matches)) {
                if (!empty($matches[3])) {
                    $year = $matches[3];
                } else {
                    $year = date('Y');
                }
                $month = self::monthNameToInt($matches[2], $environment);
                $day = $matches[1];
            }
        }

        // if still unsuccsessfull- don't parse, use original values
        if (empty($matches)) {
            $converted['conforms'] = false;
            $converted['timestamp'] = date('Ymd');
            $converted['datetime'] = date('Y-m-d');
            $converted['display'] = $original;
            $converted['error'] = false;
        } else {
            if ($year >= 1 and $year < 70) {
                $year += 2000;
            } elseif ($year >= 70 and $year <= 99) {
                $year += 1900;
            }

            if ($month < 1 or $month > 12 or $day < 1 or $day > 31) {
                $converted['conforms'] = false;
                $converted['timestamp'] = date('Ymd');
                $converted['datetime'] = date('Y-m-d');
                $converted['display'] = $original;
                $converted['error'] = true;
            } else {
                $converted['conforms'] = true;
                $converted['timestamp'] = str_pad((string) $year, 4, '0', STR_PAD_LEFT).str_pad((string) $month, 2, '0', STR_PAD_LEFT).str_pad($day, 2, '0', STR_PAD_LEFT);
                $converted['datetime'] = str_pad((string) $year, 4, '0', STR_PAD_LEFT).'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
                $converted['display'] = '';
                $converted['error'] = false;
            }
        }

        return $converted;
    }

    /**
     * Maps a localised (short or long) month name to its two-digit number,
     * or returns the input unchanged. Migrated from getShortMonthNameToInt().
     */
    public static function monthNameToInt($month, cs_environment $environment): string
    {
        return match ($month) {
            $environment->translate('COMMON_DATE_JANUARY_SHORT') => '01',
            $environment->translate('COMMON_DATE_FEBRUARY_SHORT') => '02',
            $environment->translate('COMMON_DATE_MARCH_SHORT') => '03',
            $environment->translate('COMMON_DATE_APRIL_SHORT') => '04',
            $environment->translate('COMMON_DATE_MAY_SHORT') => '05',
            $environment->translate('COMMON_DATE_JUNE_SHORT') => '06',
            $environment->translate('COMMON_DATE_JULY_SHORT') => '07',
            $environment->translate('COMMON_DATE_AUGUST_SHORT') => '08',
            $environment->translate('COMMON_DATE_SEPTEMBER_SHORT') => '09',
            $environment->translate('COMMON_DATE_OCTOBER_SHORT') => '10',
            $environment->translate('COMMON_DATE_NOVEMBER_SHORT') => '11',
            $environment->translate('COMMON_DATE_DECEMBER_SHORT') => '12',
            $environment->translate('COMMON_DATE_JANUARY_LONG') => '01',
            $environment->translate('COMMON_DATE_FEBRUARY_LONG') => '02',
            $environment->translate('COMMON_DATE_MARCH_LONG') => '03',
            $environment->translate('COMMON_DATE_APRIL_LONG') => '04',
            $environment->translate('COMMON_DATE_MAY_LONG') => '05',
            $environment->translate('COMMON_DATE_JUNE_LONG') => '06',
            $environment->translate('COMMON_DATE_JULY_LONG') => '07',
            $environment->translate('COMMON_DATE_AUGUST_LONG') => '08',
            $environment->translate('COMMON_DATE_SEPTEMBER_LONG') => '09',
            $environment->translate('COMMON_DATE_OCTOBER_LONG') => '10',
            $environment->translate('COMMON_DATE_NOVEMBER_LONG') => '11',
            $environment->translate('COMMON_DATE_DECEMBER_LONG') => '12',
            default => $month,
        };
    }

    /**
     * Maps a weekday number ("0"=Sunday … "6"=Saturday) to its localised name,
     * or "" for an unknown value. Migrated from getDayNameFromInt().
     */
    public static function weekdayName($day, cs_environment $environment): string
    {
        return match ($day) {
            '0' => $environment->translate('COMMON_DATE_SUNDAY'),
            '1' => $environment->translate('COMMON_DATE_MONDAY'),
            '2' => $environment->translate('COMMON_DATE_TUESDAY'),
            '3' => $environment->translate('COMMON_DATE_WEDNESDAY'),
            '4' => $environment->translate('COMMON_DATE_THURSDAY'),
            '5' => $environment->translate('COMMON_DATE_FRIDAY'),
            '6' => $environment->translate('COMMON_DATE_SATURDAY'),
            default => '',
        };
    }
}
