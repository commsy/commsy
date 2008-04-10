<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

// init database connection
#include_once('../migration.conf.php');
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script deletes all old email texts in the extra field of all project rooms project.");

// count project rooms
$count = array_shift(mysql_fetch_row(mysql_query('SELECT COUNT(rooms.item_id) FROM rooms WHERE extras like "%MAILUSER%" or extras like "%MAILNEW%" or extras like "%MAILDROP%"')));
init_progress_bar($count);

// get all rooms
$query = 'SELECT item_id, campus_id, extras FROM rooms WHERE extras like "%MAILUSER%" or extras like "%MAILNEW%" or extras like "%MAILDROP%"';
$result = mysql_query($query);
if($error = mysql_error()) echo $error.". QUERY: ".$query;

$room_item = mysql_fetch_array($result);
while ($room_item) {

   // and now, do it
   if ($do_it) {
      $tag_array = array();
      $tag_array[] = 'MAILUSERREJECT';
      $tag_array[] = 'MAILUSERCLOSE';
      $tag_array[] = 'MAILUSERFREE';
      $tag_array[] = 'MAILUSERORGANIZER';
      $tag_array[] = 'MAILUSERDELETE';
      $tag_array[] = 'MAILUSERMODERATOR';
      $tag_array[] = 'MAILNEWORGANIZER';
      $tag_array[] = 'MAILDROPORGANIZER';
      foreach ($tag_array as $tag) {
         if ( strstr($room_item['extras'],'<'.$tag.'>')
              and strstr($room_item['extras'],'</'.$tag.'>') ) {
            $begin = strpos($room_item['extras'],'<'.$tag.'>');
            $end = strpos($room_item['extras'],'</'.$tag.'>');
            $first = substr($room_item['extras'],0,$begin);
            $last = substr($room_item['extras'],$end+strlen('</'.$tag.'>'));
            while (!empty($last) and $last[0] != '<') {
               $last = substr($last,1);
            }
            $room_item['extras'] = $first.$last;
         }
      }

      $query  = 'UPDATE rooms SET extras = "'.$room_item['extras'].'" ';
      $query .= 'WHERE item_id = "'.$room_item['item_id'].'"';
      $result_update = mysql_query($query);
      if ($error = mysql_error() ) {
         echo ($error.". QUERY: ".$query."<br />");
         $success == false;
      }
      update_progress_bar($count);
   } else {
      echo ("item-id: ".$room_item['item_id']." campus-id: ".$room_item['campus_id']."<br />");
   }
   $room_item = mysql_fetch_array($result);
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>