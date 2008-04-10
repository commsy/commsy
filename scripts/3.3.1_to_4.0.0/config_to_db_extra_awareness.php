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

// time management for this script
$time_start = getmicrotime();

// move configuration of ads from cs_config to database
echo ('This script moves the configuration of the awareness from cs_config.php to db.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

$count_project = array_shift(mysql_fetch_row(select("SELECT COUNT(rooms.item_id) FROM rooms WHERE rooms.deletion_date IS NULL;")));
$count_community = array_shift(mysql_fetch_row(select("SELECT COUNT(campus.item_id) FROM campus WHERE campus.deletion_date IS NULL;")));
$count_rooms = $count_project + $count_community;
if ($count_rooms < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_rooms);

   // project rooms
   $query  = "SELECT rooms.item_id, rooms.extras FROM rooms WHERE rooms.deletion_date IS NULL ORDER BY rooms.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !empty($awareness_enabled_for) and in_array($room_id,$awareness_enabled_for) ) {
         if ( strstr($extra,'</AWARENESS>') ) {
            $extra = preg_replace('$<AWARENESS>0</AWARENESS>$','<AWARENESS>1</AWARENESS>',$extra);
         } elseif ( strstr($extra,'</EXTRA_CONFIG>') ) {
            $extra = preg_replace('$<EXTRA_CONFIG>([\S\s]*)</EXTRA_CONFIG>$','<EXTRA_CONFIG>$1<AWARENESS>1</AWARENESS></EXTRA_CONFIG>',$extra);
         } else {
            $extra .= '<EXTRA_CONFIG><AWARENESS>1</AWARENESS></EXTRA_CONFIG>';
         }

         // save room
         $insert_query = 'UPDATE rooms SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }

   // community rooms
   $query  = "SELECT campus.item_id, campus.extras FROM campus WHERE campus.deletion_date IS NULL ORDER BY campus.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !empty($awareness_enabled_for) and in_array($room_id,$awareness_enabled_for) ) {
         if ( strstr($extra,'</AWARENESS>') ) {
            $extra = preg_replace('$<AWARENESS>0</AWARENESS>$','<AWARENESS>1</AWARENESS>',$extra);
         } elseif ( strstr($extra,'</EXTRA_CONFIG>') ) {
            $extra = preg_replace('$<EXTRA_CONFIG>([\S\s]*)</EXTRA_CONFIG>$','<EXTRA_CONFIG>$1<AWARENESS>1</AWARENESS></EXTRA_CONFIG>',$extra);
         } else {
            $extra .= '<EXTRA_CONFIG><AWARENESS>1</AWARENESS></EXTRA_CONFIG>';
         }

         // save room
         $insert_query = 'UPDATE campus SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }
}

if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>