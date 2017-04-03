<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('functions/language_functions.php');
include_once('functions/text_functions.php');

function getCurrentDate () {
   return date("Ymd");
}

# format of date_string = DD.MM
function getDateFromDateString ($date_string) {
   $month = $date_string[3].$date_string[4];
   $day = $date_string[0].$date_string[1];
   return date("Ymd", mktime(date('H'), date('i'), date('s'), $month, $day, date('Y')));
}

if (!function_exists('getCurrentDateTimeInMySQL')) {
    function getCurrentDateTimeInMySQL()
    {
        return date("Y-m-d H:i:s");
    }
}

function getCurrentDateTimeMinusMinutesInMySQL ( $minutes ) {
   return date('Y-m-d H:i:s', mktime(date('H'), (date('i')-$minutes), date('s'), date('m'), date('d'), date('Y')));
}

function getCurrentDateTimeMinusSecondsInMySQL ( $seconds ) {
	return date('Y-m-d H:i:s', mktime(date('H'), date('i'), (date('s')-$seconds), date('m'), date('d'), date('Y')));
}

function getCurrentDateTimeMinusHoursInMySQL ( $hours ) {
   return date('Y-m-d H:i:s', mktime((date('H')-$hours), date('i'), date('s'), date('m'), date('d'), date('Y')));
}

if (!function_exists('getCurrentDateTimeMinusDaysInMySQL')) {
    function getCurrentDateTimeMinusDaysInMySQL($days)
    {
        return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), (date('d')-$days), date('Y')));
    }
}

function getCurrentDateTimeMinusMonthsInMySQL ( $months ) {
   return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), (date('m')-$months), date('d'), date('Y')));
}

function getCurrentDateTimePlusMinutesInMySQL ( $minutes ) {
	return date('Y-m-d H:i:s', mktime(date('H'), (date('i')+$minutes), date('s'), date('m'), date('d'), date('Y')));
}

if (!function_exists('getCurrentDateTimePlusDaysInMySQL')) {
    function getCurrentDateTimePlusDaysInMySQL ($days, $withoutTime = false)
    {
        if (!$withoutTime) {
            return date('Y-m-d H:i:s', mktime(date('H'), (date('i')), date('s'), date('m'), date('d') + $days, date('Y')));
        } else {
            return date('Y-m-d 00:00:00', mktime(date('H'), (date('i')), date('s'), date('m'), date('d') + $days, date('Y')));
        }
    }
}

   function convertDateFromInput ( $date,$language) {
      $region = '';
      $converted = array();
      $matches = array();
      $original  = $date;
      $date = str_replace(' ', '', $date);

      //select region of time format
      switch ($language) {
         case 'en':
            $region = 'british';
            break;
         default:
            $region = 'europe';
            break;
      }

      //set month/year/date depending on region
      if ($region == 'europe') {
         // TT.MM.YYYY
         $pattern = '~([0-9]{1,2})([./])([0-9]{1,2})([./]([0-9]{1,4}))?~u';
         if (preg_match($pattern.'i',$date,$matches)) {
            if (!empty($matches[5])) {
               $year = $matches[5];
            } else {
               $year = date('Y');
            }
            $month = $matches[3];
            $day = $matches[1];
         }
      } else if ($region = 'british') {
         // MM/TT/YYYY
         $pattern = '~([0-9]{1,2})([./])([0-9]{1,2})([./]([0-9]{1,4}))?~u';
         if (preg_match($pattern.'i',$date,$matches)) {
            if (!empty($matches[5])) {
               $year = $matches[5];
            } else {
               $year = date('Y');
            }
            $month = $matches[1];
            $day = $matches[3];
         }
      }
      //try DB time format if not succesfull yet
      if (empty($matches)) {
         $pattern = '~([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})~u';
         if (preg_match($pattern.'i',$date,$matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
         }
      }
      if (empty($matches)) {
      //try TT Mon YYYY format if not succesfull yet
         $pattern = '~([0-9]{1,2})([A-Za-z]{1,4})([0-9]{1,4})?~u';
         if (preg_match($pattern.'i',$date,$matches)) {
            if (!empty($matches[3])) {
               $year = $matches[3];
            } else {
               $year = date('Y');
            }
            $month = getShortMonthNameToInt($matches[2]);
            $day = $matches[1];
         }
      }

      //if still unsuccsessfull- don't parse, use original values
      if (empty($matches)) {
         $converted['conforms'] = false;
         $converted['timestamp'] = date('Ymd');
         $converted['datetime']  = date('Y-m-d');
         $converted['display']  = $original;
         $converted['error'] = false;
      } else {
         if ( $year >= 1 and $year < 70 ) {
            $year += 2000;
         } elseif ( $year >= 70 and $year <= 99 ) {
            $year += 1900;
         }

         if ( $month < 1 or $month > 12 or $day < 1 or $day > 31 ) {
            $converted['conforms']  = false;
            $converted['timestamp'] = date('Ymd');
            $converted['datetime']  = date('Y-m-d');
            $converted['display']  = $original;
            $converted['error'] = true;
         } else {
            $converted['conforms'] = true;
            $converted['timestamp'] = str_pad($year, 4, '0', STR_PAD_LEFT).str_pad($month, 2, '0', STR_PAD_LEFT).str_pad($day, 2, '0', STR_PAD_LEFT);
            $converted['datetime']  = str_pad($year, 4, '0', STR_PAD_LEFT).'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
            $converted['display']  = '';
            $converted['error'] = false;
         }
      }
      return $converted;
   }

   function convertTimeFromInput ( $time ) {
      $converted = array();
      $original  = $time;

      // Remove spaces to prevent hassle
      $time = trim(str_replace(' ', '', $time));


     $hours = '00';
     $minutes = '00';
     $secs = '00';
     $ampm = '';
     $stct = '';
     $conforms = false;

      if ( preg_match('~^([01]?[0-9]|2[0-3])([\.:]([0-5]?[0-9]))?([\.:]([0-5]?[0-9]))?(am|pm)?((s|c)\.?t\.?)?$~iu',$time,$matches) ) {
        $hours = $matches[1];
        if ( !empty($matches[3]) ) {
           $minutes = $matches[3];
        }
        if ( !empty($matches[5]) ) {
           $secs = $matches[5];
        }
        if ( !empty($matches[3]) ) {
           $minutes = $matches[3];
        }
        if ( !empty($matches[6]) ) {
             $ampm = $matches [6];
        }
        if ( !empty($matches[7]) ) {
             $stct = $matches [7];
        }

         if ( ($hours < 12) and ($hours >= 1) and ($ampm == 'pm') ) {
            $hours += 12;
         }
         if ( ($hours >= 12) and ($hours <= 23) and ($ampm == 'am') ) {
            $hours -= 12;
         }

         if ( $stct == 'st' ) {
            $minutes = '00';
         } elseif ( $stct == 'ct' ) {
            $minutes = '15';
         }

         $conforms = true;
      }

      if ( $conforms ) {
         $converted['conforms']  = true;
         $converted['timestamp'] = str_pad($hours, 2, '0', STR_PAD_LEFT).str_pad($minutes, 2, '0', STR_PAD_LEFT).$secs;
         $converted['datetime']  = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.$secs;
         if ( empty($stct) ) {
            $converted['display']  = '';
         } else {
            $converted['display']  = $stct;
         }
      } else {
         $converted['conforms']  = false;
         $converted['timestamp'] = '000000';
         $converted['datetime']  = '00:00:00';
         $converted['display']   = $original;
      }
     return $converted;
   }

   function extractDateTimeFromInput($datetime) {
      $parts = explode(' ',$datetime);
      $result = array();
      $result["Time"] = $parts[1];
      $result["Date"] = $parts[0];
      return $result;
   }

   function getDifference ($timestamp_lower, $timestamp_higher){

      $day_lower = substr($timestamp_lower,6,2);
      $day_higher = substr($timestamp_higher,6,2);

      $month_lower = substr($timestamp_lower,4,2);
      $month_higher = substr($timestamp_higher,4,2);

      $year_lower = substr($timestamp_lower,0,4);
      $year_higher = substr($timestamp_higher,0,4);

      $from_date = mktime(0, 0, 0, $month_lower, $day_lower, $year_lower);
      $till_date = mktime(0, 0, 0, $month_higher, $day_higher, $year_higher);
      $begtimestamp = "";

      for($ts = $from_date; $ts <= $till_date; $ts+=86400) {
         if ($begtimestamp=="") {
            $begtimestamp=$ts; //this line freezes first timestamp
         }
      }
      $endtimestamp=$ts; //this line freezes the last timestamp

      $totaldays = (($endtimestamp-$begtimestamp) / 86400);

      return $totaldays;
   }

   function getTimeDifference ($timestamp_lower, $timestamp_higher){

      $hour_lower = substr($timestamp_lower,0,2);
      $hour_higher = substr($timestamp_higher,0,2);

      $minute_lower = substr($timestamp_lower,2,2);
      $minute_higher = substr($timestamp_higher,2,2);

      $second_lower = substr($timestamp_lower,4,2);
      $second_higher = substr($timestamp_higher,4,2);

      $from_time = mktime($hour_lower, $minute_lower, $second_lower,1,1,1990);
      $till_time = mktime($hour_higher, $minute_higher, $second_higher,1,1,1990);

      /*$begtimestamp = "";

      for($ts = $from_time; $ts <= $till_time; $ts+=3600) {
         if ($begtimestamp=="") {
            $begtimestamp=$ts; //this line freezes first timestamp
         }
      }
      $endtimestamp=$ts; //this line freezes the last timestamp

      $totalhours = (($endtimestamp-$begtimestamp) / 3600);*/
      $totalhours = round(($till_time - $from_time)/3600);
      return $totalhours;
   }

   function getSecondDifference ($datetime_lower, $datetime_higher){

      $hour_lower = substr($datetime_lower,11,2);
      $hour_higher = substr($datetime_higher,11,2);

      $minute_lower = substr($datetime_lower,14,2);
      $minute_higher = substr($datetime_higher,14,2);

      $second_lower = substr($datetime_lower,17,2);
      $second_higher = substr($datetime_higher,17,2);

      $from_time = mktime($hour_lower, $minute_lower, $second_lower,1,1,1990);
      $till_time = mktime($hour_higher, $minute_higher, $second_higher,1,1,1990);

      $totalseconds = round(($till_time - $from_time));
      return $totalseconds;
   }

   #####
   # date functions -> now in translation object
   #####

   function getDateTimeInLang($datetime, $oclock =true) {
      global $environment;
      $translator = $environment->getTranslationObject();
      return $translator->getDateTimeInLang($datetime,$oclock);
   }

   function getTimeInLang($datetime) {
      global $environment;
      $translator = $environment->getTranslationObject();
      return $translator->getTimeInLang($datetime);
   }

   function getDateInLang($datetime) {
      global $environment;
      $translator = $environment->getTranslationObject();
      return $translator->getDateInLang($datetime);
   }

   function getTimeLanguage($timestring) {
      global $environment;
      $translator = $environment->getTranslationObject();
      return $translator->getTimeLanguage($timestring);
   }

   function getDateLanguage($datestring) {
      global $environment;
      $translator = $environment->getTranslationObject();
      return $translator->getDateLanguage($datestring);
   }


   //TimeString in following format: YYYYMMDD
   function getDateFromString($timestring) {
      $result = array();
      $result['day'] = substr($timestring,6,2);
      $result['month'] = substr($timestring,4,2);
      $result['year'] = substr($timestring,0,4);
      $result['timestamp'] = $timestring;
      return $result;

   }


/*
 * Find the number of days in a month
 * Year is between 1 and 32767 inclusive
 * Month is between 1 and 12 inclusive
 */
function daysInMonth($month, $year) {
   if (strlen($month) == 8){
      $month = substr($month,4,2);
   }
   $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
   $days = 0;
   if ($month != 2) {
      $days = $daysInMonth[$month - 1];
   } else {
      $days = (checkdate($month, 29, $year)) ? 29 : 28;
   }
   return $days;
}


function DateAdd($v,$d=null , $f="d/m/Y"){
  $d=($d?$d:date("Y-m-d h:m:s"));
  return date($f,strtotime($v." days",strtotime($d)));
}

function getDayNameFromInt($day) {
   global $environment;
   $translator = $environment->getTranslationObject();

   switch($day) {
     case '0': $ret = $translator->getMessage('COMMON_DATE_SUNDAY'); break;
     case '1': $ret = $translator->getMessage('COMMON_DATE_MONDAY'); break;
     case '2': $ret = $translator->getMessage('COMMON_DATE_TUESDAY'); break;
     case '3': $ret = $translator->getMessage('COMMON_DATE_WEDNESDAY'); break;
     case '4': $ret = $translator->getMessage('COMMON_DATE_THURSDAY'); break;
     case '5': $ret = $translator->getMessage('COMMON_DATE_FRIDAY'); break;
     case '6': $ret = $translator->getMessage('COMMON_DATE_SATURDAY'); break;
     default: $ret = '';
   }
   return $ret;
}

function getLongMonthNameFromInt ($int) {
	return getLongMonthName(($int-1));
}

function getLongMonthName($month) {
   global $environment;
   $translator = $environment->getTranslationObject();

   switch($month) {
     case '0': $ret = $translator->getMessage('COMMON_DATE_JANUARY_LONG'); break;
     case '1': $ret = $translator->getMessage('COMMON_DATE_FEBRUARY_LONG'); break;
     case '2': $ret = $translator->getMessage('COMMON_DATE_MARCH_LONG'); break;
     case '3': $ret = $translator->getMessage('COMMON_DATE_APRIL_LONG'); break;
     case '4': $ret = $translator->getMessage('COMMON_DATE_MAY_LONG'); break;
     case '5': $ret = $translator->getMessage('COMMON_DATE_JUNE_LONG'); break;
     case '6': $ret = $translator->getMessage('COMMON_DATE_JULY_LONG'); break;
     case '7': $ret = $translator->getMessage('COMMON_DATE_AUGUST_LONG'); break;
     case '8': $ret = $translator->getMessage('COMMON_DATE_SEPTEMBER_LONG'); break;
     case '9': $ret = $translator->getMessage('COMMON_DATE_OCTOBER_LONG'); break;
     case '10': $ret = $translator->getMessage('COMMON_DATE_NOVEMBER_LONG'); break;
     case '11': $ret = $translator->getMessage('COMMON_DATE_DECEMBER_LONG'); break;
   }
   return $ret;
}
function getShortMonthName($month) {
   global $environment;
   $translator = $environment->getTranslationObject();
   switch($month) {
     case '0': $ret = $translator->getMessage('COMMON_DATE_JANUARY_SHORT'); break;
     case '1': $ret = $translator->getMessage('COMMON_DATE_FEBRUARY_SHORT'); break;
     case '2': $ret = $translator->getMessage('COMMON_DATE_MARCH_SHORT'); break;
     case '3': $ret = $translator->getMessage('COMMON_DATE_APRIL_SHORT'); break;
     case '4': $ret = $translator->getMessage('COMMON_DATE_MAY_SHORT'); break;
     case '5': $ret = $translator->getMessage('COMMON_DATE_JUNE_SHORT'); break;
     case '6': $ret = $translator->getMessage('COMMON_DATE_JULY_SHORT'); break;
     case '7': $ret = $translator->getMessage('COMMON_DATE_AUGUST_SHORT'); break;
     case '8': $ret = $translator->getMessage('COMMON_DATE_SEPTEMBER_SHORT'); break;
     case '9': $ret = $translator->getMessage('COMMON_DATE_OCTOBER_SHORT'); break;
     case '10': $ret = $translator->getMessage('COMMON_DATE_NOVEMBER_SHORT'); break;
     case '11': $ret = $translator->getMessage('COMMON_DATE_DECEMBER_SHORT'); break;
   }
   return $ret;
}

function getShortMonthNameToInt($month) {
   global $environment;
   $translator = $environment->getTranslationObject();
   switch($month) {
     case $translator->getMessage('COMMON_DATE_JANUARY_SHORT'): $ret = '01'; break;
     case $translator->getMessage('COMMON_DATE_FEBRUARY_SHORT'): $ret = '02'; break;
     case $translator->getMessage('COMMON_DATE_MARCH_SHORT'): $ret = '03'; break;
     case $translator->getMessage('COMMON_DATE_APRIL_SHORT'): $ret = '04'; break;
     case $translator->getMessage('COMMON_DATE_MAY_SHORT'): $ret = '05'; break;
     case $translator->getMessage('COMMON_DATE_JUNE_SHORT'): $ret = '06'; break;
     case $translator->getMessage('COMMON_DATE_JULY_SHORT'): $ret = '07'; break;
     case $translator->getMessage('COMMON_DATE_AUGUST_SHORT'): $ret = '08'; break;
     case $translator->getMessage('COMMON_DATE_SEPTEMBER_SHORT'): $ret = '09'; break;
     case $translator->getMessage('COMMON_DATE_OCTOBER_SHORT'): $ret = '10'; break;
     case $translator->getMessage('COMMON_DATE_NOVEMBER_SHORT'): $ret = '11'; break;
     case $translator->getMessage('COMMON_DATE_DECEMBER_SHORT'): $ret = '12'; break;
     case $translator->getMessage('COMMON_DATE_JANUARY_LONG'): $ret = '01'; break;
     case $translator->getMessage('COMMON_DATE_FEBRUARY_LONG'): $ret = '02'; break;
     case $translator->getMessage('COMMON_DATE_MARCH_LONG'): $ret = '03'; break;
     case $translator->getMessage('COMMON_DATE_APRIL_LONG'): $ret = '04'; break;
     case $translator->getMessage('COMMON_DATE_MAY_LONG'): $ret = '05'; break;
     case $translator->getMessage('COMMON_DATE_JUNE_LONG'): $ret = '06'; break;
     case $translator->getMessage('COMMON_DATE_JULY_LONG'): $ret = '07'; break;
     case $translator->getMessage('COMMON_DATE_AUGUST_LONG'): $ret = '08'; break;
     case $translator->getMessage('COMMON_DATE_SEPTEMBER_LONG'): $ret = '09'; break;
     case $translator->getMessage('COMMON_DATE_OCTOBER_LONG'): $ret = '10'; break;
     case $translator->getMessage('COMMON_DATE_NOVEMBER_LONG'): $ret = '11'; break;
     case $translator->getMessage('COMMON_DATE_DECEMBER_LONG'): $ret = '12'; break;
     default : $ret = $month;
   }
   return $ret;
}

function getYearFromDateTime ( $datetime ) {
   $retour = '';
   if ( !empty($datetime) ) {
      $retour = substr($datetime,0,4);
   }
   return $retour;
}

function getMonthFromDateTime ( $datetime ) {
   $retour = '';
   if ( !empty($datetime) ) {
      $retour = substr($datetime,5,2);
   }
   return $retour;
}

function getDayFromDateTime ( $datetime ) {
   $retour = '';
   if ( !empty($datetime) ) {
      $retour = substr($datetime,8,2);
   }
   return $retour;
}

function datetime2Timestamp ( $datetime ) {
   $year = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
   $month = $datetime[5].$datetime[6];
   $day = $datetime[8].$datetime[9];
   $hour = $datetime[11].$datetime[12];
   $min = $datetime[14].$datetime[15];
   $sec = $datetime[17].$datetime[18];
   return mktime($hour,$min,$sec,$month,$day,$year);
}

function isDatetimeCorrect ( $language, $date, $time='' ) {
   $retour = false;
   $date_result = convertDateFromInput($date,$language);
   if ( empty($time)
        or preg_replace('/[A-Za-z ]/', '', $time) != $time
      ) {
      $time_result['conforms'] = '1';
      $time_result['datetime'] = '00:00:00';
   } else {
      $time_result = convertTimeFromInput($time);
   }
   if ( empty($date_result['error'])
        and empty($time_result['error'])
        and !empty($date_result['conforms'])
        and !empty($time_result['conforms'])
      ) {
      if ( !empty($time_result['datetime'])
           and !empty($date_result['datetime'])
         ) {
         $value = $date_result['datetime'].' '.$time_result['datetime'];
         if ( $value == date('Y-m-d H:i:s',datetime2Timestamp($value)) ) {
            $retour = true;
         }
      }
   }
   return $retour;
}

date_default_timezone_set(date_default_timezone_get());
?>