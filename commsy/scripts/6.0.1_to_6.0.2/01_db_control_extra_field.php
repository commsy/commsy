<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

// add column "has_html" to table "files"
echo ('extras: control serialized array'."\n");
$success = true;

$table = array();
$table[] = 'auth_source';
$table[] = 'labels';
$table[] = 'materials';
$table[] = 'portal';
$table[] = 'room';
$table[] = 'server';
$table[] = 'user';

$error_array = array();

foreach ( $table as $tbl ) {
   echo("<br/><br/>".$tbl);
   $count = array_shift(mysql_fetch_row(select("SELECT COUNT(*) FROM ".$tbl.";")));
   if ($count < 1) {
      echo "<br />nothing to do.";
   } else {
      init_progress_bar($count);

      $query  = "SELECT item_id, extras FROM ".$tbl.";";
      $result = select($query);
      $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $extra = $row[1];
      while (isset($item_id)) {
         if ( !empty($extra)
              and $extra != 'a:0:{}'
              and $extra != 'b:0;'
              and $extra != 's:0:"";'
            ) {
            $extra_array = unserialize($extra);
            if ( empty($extra_array) ) {
               $temp_array = array();
               $temp_array['item_id'] = $item_id;

               $text = $extra;
               $counter = 0;
               $laenge = array();
               $temp_text = array();
               while ( strstr($text,'<!-- KFC TEXT -->') ) {
                  $pos1 = mb_strpos($text,'<!-- KFC TEXT -->');
                  $text_temp = mb_substr($text,$pos1+17);
                  $pos2 = mb_strpos($text_temp,'<!-- KFC TEXT -->');
                  $text_value = mb_substr($text_temp,0,$pos2);
                  $laenge[$counter] = mb_strlen('<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->');
                  $temp_text['FCK_TEXT_'.$counter] = '<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->';
                  $text = str_replace('<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->','FCK_TEXT_'.$counter,$text);
                  $counter++;
               }
               preg_match_all('~s:([0-9]*):"FCK_TEXT_([0-9]*)~u',$text,$values);
               foreach ( $values[0] as $key => $wert ) {
                  $wert2 = str_replace($values[1][$key],$laenge[$values[2][$key]],$wert);
                  $text = str_replace($wert,$wert2,$text);
               }

               preg_match_all('~FCK_TEXT_[0-9]*~u',$text,$values);
               foreach ( $values[0] as $key => $wert ) {
                  $text = str_replace($wert,$temp_text[$wert],$text);
               }
               $extra_array = unserialize($text);
               if ( !empty($extra_array) ) {
                  $temp_array['repair'] = 'yes1';
               } else {
                  preg_match_all('~s:([0-9]*):"([^(";)]*)";~u',$text,$values);
                  if ( !empty($values[0]) ) {
                     foreach ( $values[0] as $key => $wert ) {
                        if (mb_strlen($values[2][$key]) != $values[1][$key] ) {
                           $wert2 = str_replace($values[1][$key],mb_strlen($values[2][$key]),$wert);
                           $text = str_replace($wert,$wert2,$text);
                        }
                     }
                  }
                  $extra_array = unserialize($text);
                  if ( !empty($extra_array) ) {
                     $temp_array['repair'] = 'yes2';
                  } else {
                     $text = str_replace('(','[',$text);
                     $text = str_replace(')',']',$text);
                     $text = str_replace(':"','DOPPELPUNKTHOCH',$text);
                     $text = str_replace('";','HOCHSEMIKOLON',$text);
                     $text = str_replace('"','\'',$text);
                     $text = str_replace('DOPPELPUNKTHOCH',':"',$text);
                     $text = str_replace('HOCHSEMIKOLON','";',$text);
                     preg_match_all('~s:([0-9]*):"([^(";)]*)";~u',$text,$values);
                     if ( !empty($values[0]) ) {
                        foreach ( $values[0] as $key => $wert ) {
                           if (mb_strlen($values[2][$key]) != $values[1][$key] ) {
                              $wert2 = str_replace($values[1][$key],mb_strlen($values[2][$key]),$wert);
                              $text = str_replace($wert,$wert2,$text);
                           }
                        }
                     }
                     $extra_array = unserialize($text);
                     if ( !empty($extra_array) ) {
                        $temp_array['repair'] = 'yes3';
                     }
                  }
               }


               $error_array[$tbl][] = $temp_array;
               unset($temp_array);
            }

            if ( !empty($error_array) and !empty($temp_array['repair']) ) {
               $update_query = 'UPDATE '.$tbl.' SET extras="'.addslashes(serialize($extra_array)).'" WHERE item_id="'.$item_id.'";';
               $success2 = select($update_query);
               $success = $success and $success2;
            }
         }
         $row = mysql_fetch_row($result);
         $item_id = $row[0];
         $extra = $row[1];
         update_progress_bar($count);
      }
   }
}
if ( !empty($error_array) ) {
   echo("\n\n".'<br/><br/>Fehler gefunden:');
   foreach ( $error_array as $key => $table_errors ) {
      echo("\n\n".'<br/><br/>'.$key.': '.count($table_errors).' Fehler');
      foreach ($table_errors as $no => $item_error_array) {
         echo("\n".'<br/>'.$key.': '.$item_error_array['item_id']);
         if ( !empty($item_error_array['repair']) and $item_error_array['repair'] == 'yes1' ) {
            echo(' - reparatur gelungen - 1. Versuch');
         } elseif ( !empty($item_error_array['repair']) and $item_error_array['repair'] == 'yes2' ) {
            echo(' - reparatur gelungen - 2. Versuch');
         } elseif ( !empty($item_error_array['repair']) and $item_error_array['repair'] == 'yes3' ) {
            echo(' - reparatur gelungen - 3. Versuch');
         }
      }
   }
} else {
   echo("\n\n".'<br/><br/>Keine Fehler gefunden. Alles in Ordnung.');
}
echo("\n".'<br/>');

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>