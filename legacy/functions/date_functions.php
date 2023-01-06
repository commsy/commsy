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

function getCurrentDate()
{
    return date('Ymd');
}

// format of date_string = DD.MM
function getDateFromDateString($date_string)
{
    $month = $date_string[3].$date_string[4];
    $day = $date_string[0].$date_string[1];

    return date('Ymd', mktime(date('H'), date('i'), date('s'), $month, $day, date('Y')));
}

if (!function_exists('getCurrentDateTimeInMySQL')) {
    function getCurrentDateTimeInMySQL()
    {
        return date('Y-m-d H:i:s');
    }
}

function getCurrentDateTimeMinusMinutesInMySQL($minutes)
{
    return date('Y-m-d H:i:s', mktime(date('H'), date('i') - $minutes, date('s'), date('m'), date('d'), date('Y')));
}

function getCurrentDateTimeMinusSecondsInMySQL($seconds)
{
    return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s') - $seconds, date('m'), date('d'), date('Y')));
}

function getCurrentDateTimeMinusHoursInMySQL($hours)
{
    return date('Y-m-d H:i:s', mktime(date('H') - $hours, date('i'), date('s'), date('m'), date('d'), date('Y')));
}

if (!function_exists('getCurrentDateTimeMinusDaysInMySQL')) {
    function getCurrentDateTimeMinusDaysInMySQL($days)
    {
        return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - $days, date('Y')));
    }
}

function getCurrentDateTimeMinusMonthsInMySQL($months)
{
    return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m') - $months, date('d'), date('Y')));
}

function getCurrentDateTimePlusMinutesInMySQL($minutes)
{
    return date('Y-m-d H:i:s', mktime(date('H'), date('i') + $minutes, date('s'), date('m'), date('d'), date('Y')));
}

if (!function_exists('getCurrentDateTimePlusDaysInMySQL')) {
    function getCurrentDateTimePlusDaysInMySQL($days, $withoutTime = false)
    {
        if (!$withoutTime) {
            return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $days, date('Y')));
        } else {
            return date('Y-m-d 00:00:00', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + $days, date('Y')));
        }
    }
}

function convertDateFromInput($date, $language)
{
    $year = null;
    $month = null;
    $day = null;
    $region = '';
    $converted = [];
    $matches = [];
    $original = $date;
    $date = str_replace(' ', '', $date);

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
            $month = getShortMonthNameToInt($matches[2]);
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
            $converted['timestamp'] = str_pad($year, 4, '0', STR_PAD_LEFT).str_pad($month, 2, '0', STR_PAD_LEFT).str_pad($day, 2, '0', STR_PAD_LEFT);
            $converted['datetime'] = str_pad($year, 4, '0', STR_PAD_LEFT).'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
            $converted['display'] = '';
            $converted['error'] = false;
        }
    }

    return $converted;
}

function convertTimeFromInput($time)
{
    $converted = [];
    $original = $time;

    // Remove spaces to prevent hassle
    $time = trim(str_replace(' ', '', $time));

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
        if (!empty($matches[3])) {
            $minutes = $matches[3];
        }
        if (!empty($matches[6])) {
            $ampm = $matches[6];
        }
        if (!empty($matches[7])) {
            $stct = $matches[7];
        }

        if (($hours < 12) and ($hours >= 1) and ('pm' == $ampm)) {
            $hours += 12;
        }
        if (($hours >= 12) and ($hours <= 23) and ('am' == $ampm)) {
            $hours -= 12;
        }

        if ('st' == $stct) {
            $minutes = '00';
        } elseif ('ct' == $stct) {
            $minutes = '15';
        }

        $conforms = true;
    }

    if ($conforms) {
        $converted['conforms'] = true;
        $converted['timestamp'] = str_pad($hours, 2, '0', STR_PAD_LEFT).str_pad($minutes, 2, '0', STR_PAD_LEFT).$secs;
        $converted['datetime'] = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.$secs;
        if (empty($stct)) {
            $converted['display'] = '';
        } else {
            $converted['display'] = $stct;
        }
    } else {
        $converted['conforms'] = false;
        $converted['timestamp'] = '000000';
        $converted['datetime'] = '00:00:00';
        $converted['display'] = $original;
    }

    return $converted;
}

function extractDateTimeFromInput($datetime)
{
    $parts = explode(' ', $datetime);
    $result = [];
    $result['Time'] = $parts[1];
    $result['Date'] = $parts[0];

    return $result;
}

function getDifference($timestamp_lower, $timestamp_higher)
{
    $day_lower = substr($timestamp_lower, 6, 2);
    $day_higher = substr($timestamp_higher, 6, 2);

    $month_lower = substr($timestamp_lower, 4, 2);
    $month_higher = substr($timestamp_higher, 4, 2);

    $year_lower = substr($timestamp_lower, 0, 4);
    $year_higher = substr($timestamp_higher, 0, 4);

    $from_date = mktime(0, 0, 0, $month_lower, $day_lower, $year_lower);
    $till_date = mktime(0, 0, 0, $month_higher, $day_higher, $year_higher);
    $begtimestamp = '';

    for ($ts = $from_date; $ts <= $till_date; $ts += 86400) {
        if ('' == $begtimestamp) {
            $begtimestamp = $ts; // this line freezes first timestamp
        }
    }
    $endtimestamp = $ts; // this line freezes the last timestamp

    $totaldays = (($endtimestamp - $begtimestamp) / 86400);

    return $totaldays;
}

function getTimeDifference($timestamp_lower, $timestamp_higher)
{
    $hour_lower = substr($timestamp_lower, 0, 2);
    $hour_higher = substr($timestamp_higher, 0, 2);

    $minute_lower = substr($timestamp_lower, 2, 2);
    $minute_higher = substr($timestamp_higher, 2, 2);

    $second_lower = substr($timestamp_lower, 4, 2);
    $second_higher = substr($timestamp_higher, 4, 2);

    $from_time = mktime($hour_lower, $minute_lower, $second_lower, 1, 1, 1990);
    $till_time = mktime($hour_higher, $minute_higher, $second_higher, 1, 1, 1990);

    /*$begtimestamp = "";

    for($ts = $from_time; $ts <= $till_time; $ts+=3600) {
       if ($begtimestamp=="") {
          $begtimestamp=$ts; //this line freezes first timestamp
       }
    }
    $endtimestamp=$ts; //this line freezes the last timestamp

    $totalhours = (($endtimestamp-$begtimestamp) / 3600);*/
    $totalhours = round(($till_time - $from_time) / 3600);

    return $totalhours;
}

function getSecondDifference($datetime_lower, $datetime_higher)
{
    $hour_lower = substr($datetime_lower, 11, 2);
    $hour_higher = substr($datetime_higher, 11, 2);

    $minute_lower = substr($datetime_lower, 14, 2);
    $minute_higher = substr($datetime_higher, 14, 2);

    $second_lower = substr($datetime_lower, 17, 2);
    $second_higher = substr($datetime_higher, 17, 2);

    $from_time = mktime($hour_lower, $minute_lower, $second_lower, 1, 1, 1990);
    $till_time = mktime($hour_higher, $minute_higher, $second_higher, 1, 1, 1990);

    $totalseconds = round($till_time - $from_time);

    return $totalseconds;
}

// ####
// date functions -> now in translation object
// ####

function getDateTimeInLang($datetime, $oclock = true)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getDateTimeInLang($datetime, $oclock);
}

function getTimeInLang($datetime)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getTimeInLang($datetime);
}

function getDateInLang($datetime)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getDateInLang($datetime);
}

function getTimeLanguage($timestring)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getTimeLanguage($timestring);
}

function getDateLanguage($datestring)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getDateLanguage($datestring);
}

// TimeString in following format: YYYYMMDD
function getDateFromString($timestring)
{
    $result = [];
    $result['day'] = substr($timestring, 6, 2);
    $result['month'] = substr($timestring, 4, 2);
    $result['year'] = substr($timestring, 0, 4);
    $result['timestamp'] = $timestring;

    return $result;
}

/*
 * Find the number of days in a month
 * Year is between 1 and 32767 inclusive
 * Month is between 1 and 12 inclusive
 */
function daysInMonth($month, $year)
{
    if (8 == strlen($month)) {
        $month = substr($month, 4, 2);
    }
    $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $days = 0;
    if (2 != $month) {
        $days = $daysInMonth[$month - 1];
    } else {
        $days = (checkdate($month, 29, $year)) ? 29 : 28;
    }

    return $days;
}

function DateAdd($v, $d = null, $f = 'd/m/Y')
{
    $d = ($d ?: date('Y-m-d h:m:s'));

    return date($f, strtotime($v.' days', strtotime($d)));
}

function getDayNameFromInt($day)
{
    global $environment;
    $translator = $environment->getTranslationObject();

    $ret = match ($day) {
        '0' => $translator->getMessage('COMMON_DATE_SUNDAY'),
        '1' => $translator->getMessage('COMMON_DATE_MONDAY'),
        '2' => $translator->getMessage('COMMON_DATE_TUESDAY'),
        '3' => $translator->getMessage('COMMON_DATE_WEDNESDAY'),
        '4' => $translator->getMessage('COMMON_DATE_THURSDAY'),
        '5' => $translator->getMessage('COMMON_DATE_FRIDAY'),
        '6' => $translator->getMessage('COMMON_DATE_SATURDAY'),
        default => '',
    };

    return $ret;
}

function getLongMonthNameFromInt($int)
{
    return getLongMonthName($int - 1);
}

function getLongMonthName($month)
{
    $ret = null;
    global $environment;
    $translator = $environment->getTranslationObject();

    $ret = match ($month) {
        '0' => $translator->getMessage('COMMON_DATE_JANUARY_LONG'),
        '1' => $translator->getMessage('COMMON_DATE_FEBRUARY_LONG'),
        '2' => $translator->getMessage('COMMON_DATE_MARCH_LONG'),
        '3' => $translator->getMessage('COMMON_DATE_APRIL_LONG'),
        '4' => $translator->getMessage('COMMON_DATE_MAY_LONG'),
        '5' => $translator->getMessage('COMMON_DATE_JUNE_LONG'),
        '6' => $translator->getMessage('COMMON_DATE_JULY_LONG'),
        '7' => $translator->getMessage('COMMON_DATE_AUGUST_LONG'),
        '8' => $translator->getMessage('COMMON_DATE_SEPTEMBER_LONG'),
        '9' => $translator->getMessage('COMMON_DATE_OCTOBER_LONG'),
        '10' => $translator->getMessage('COMMON_DATE_NOVEMBER_LONG'),
        '11' => $translator->getMessage('COMMON_DATE_DECEMBER_LONG'),
        default => $ret,
    };

    return $ret;
}
function getShortMonthName($month)
{
    $ret = null;
    global $environment;
    $translator = $environment->getTranslationObject();
    $ret = match ($month) {
        '0' => $translator->getMessage('COMMON_DATE_JANUARY_SHORT'),
        '1' => $translator->getMessage('COMMON_DATE_FEBRUARY_SHORT'),
        '2' => $translator->getMessage('COMMON_DATE_MARCH_SHORT'),
        '3' => $translator->getMessage('COMMON_DATE_APRIL_SHORT'),
        '4' => $translator->getMessage('COMMON_DATE_MAY_SHORT'),
        '5' => $translator->getMessage('COMMON_DATE_JUNE_SHORT'),
        '6' => $translator->getMessage('COMMON_DATE_JULY_SHORT'),
        '7' => $translator->getMessage('COMMON_DATE_AUGUST_SHORT'),
        '8' => $translator->getMessage('COMMON_DATE_SEPTEMBER_SHORT'),
        '9' => $translator->getMessage('COMMON_DATE_OCTOBER_SHORT'),
        '10' => $translator->getMessage('COMMON_DATE_NOVEMBER_SHORT'),
        '11' => $translator->getMessage('COMMON_DATE_DECEMBER_SHORT'),
        default => $ret,
    };

    return $ret;
}

function getShortMonthNameToInt($month)
{
    global $environment;
    $translator = $environment->getTranslationObject();
    $ret = match ($month) {
        $translator->getMessage('COMMON_DATE_JANUARY_SHORT') => '01',
        $translator->getMessage('COMMON_DATE_FEBRUARY_SHORT') => '02',
        $translator->getMessage('COMMON_DATE_MARCH_SHORT') => '03',
        $translator->getMessage('COMMON_DATE_APRIL_SHORT') => '04',
        $translator->getMessage('COMMON_DATE_MAY_SHORT') => '05',
        $translator->getMessage('COMMON_DATE_JUNE_SHORT') => '06',
        $translator->getMessage('COMMON_DATE_JULY_SHORT') => '07',
        $translator->getMessage('COMMON_DATE_AUGUST_SHORT') => '08',
        $translator->getMessage('COMMON_DATE_SEPTEMBER_SHORT') => '09',
        $translator->getMessage('COMMON_DATE_OCTOBER_SHORT') => '10',
        $translator->getMessage('COMMON_DATE_NOVEMBER_SHORT') => '11',
        $translator->getMessage('COMMON_DATE_DECEMBER_SHORT') => '12',
        $translator->getMessage('COMMON_DATE_JANUARY_LONG') => '01',
        $translator->getMessage('COMMON_DATE_FEBRUARY_LONG') => '02',
        $translator->getMessage('COMMON_DATE_MARCH_LONG') => '03',
        $translator->getMessage('COMMON_DATE_APRIL_LONG') => '04',
        $translator->getMessage('COMMON_DATE_MAY_LONG') => '05',
        $translator->getMessage('COMMON_DATE_JUNE_LONG') => '06',
        $translator->getMessage('COMMON_DATE_JULY_LONG') => '07',
        $translator->getMessage('COMMON_DATE_AUGUST_LONG') => '08',
        $translator->getMessage('COMMON_DATE_SEPTEMBER_LONG') => '09',
        $translator->getMessage('COMMON_DATE_OCTOBER_LONG') => '10',
        $translator->getMessage('COMMON_DATE_NOVEMBER_LONG') => '11',
        $translator->getMessage('COMMON_DATE_DECEMBER_LONG') => '12',
        default => $month,
    };

    return $ret;
}

function getYearFromDateTime($datetime)
{
    $retour = '';
    if (!empty($datetime)) {
        $retour = substr($datetime, 0, 4);
    }

    return $retour;
}

function getMonthFromDateTime($datetime)
{
    $retour = '';
    if (!empty($datetime)) {
        $retour = substr($datetime, 5, 2);
    }

    return $retour;
}

function getDayFromDateTime($datetime)
{
    $retour = '';
    if (!empty($datetime)) {
        $retour = substr($datetime, 8, 2);
    }

    return $retour;
}

function datetime2Timestamp($datetime)
{
    $year = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
    $month = $datetime[5].$datetime[6];
    $day = $datetime[8].$datetime[9];
    $hour = $datetime[11].$datetime[12];
    $min = $datetime[14].$datetime[15];
    $sec = $datetime[17].$datetime[18];

    return mktime($hour, $min, $sec, $month, $day, $year);
}

function isDatetimeCorrect($language, $date, $time = '')
{
    $time_result = [];
    $retour = false;
    $date_result = convertDateFromInput($date, $language);
    if (empty($time)
         or preg_replace('/[A-Za-z ]/', '', $time) != $time
    ) {
        $time_result['conforms'] = '1';
        $time_result['datetime'] = '00:00:00';
    } else {
        $time_result = convertTimeFromInput($time);
    }
    if (empty($date_result['error'])
         and empty($time_result['error'])
         and !empty($date_result['conforms'])
         and !empty($time_result['conforms'])
    ) {
        if (!empty($time_result['datetime'])
             and !empty($date_result['datetime'])
        ) {
            $value = $date_result['datetime'].' '.$time_result['datetime'];
            if ($value == date('Y-m-d H:i:s', datetime2Timestamp($value))) {
                $retour = true;
            }
        }
    }

    return $retour;
}

date_default_timezone_set(date_default_timezone_get());
