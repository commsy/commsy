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
echo ('This script moves the configuration of the newsletter from cs_config.php to db.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

$count_room = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room WHERE room.deletion_date IS NULL;")));
$count_portal = array_shift(mysql_fetch_row(select("SELECT COUNT(portal.item_id) FROM portal WHERE portal.deletion_date IS NULL;")));
$count_rooms = $count_room + $count_community + $count_portal;
if ($count_rooms < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_rooms);

   // rooms
   $query  = "SELECT item_id, extras FROM room WHERE deletion_date IS NULL ORDER BY item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !empty($newsletter_enabled_for) and in_array($room_id,$newsletter_enabled_for) ) {

         $extra_array = XML2Array( $extra );
         $extra_array['EXTRA_CONFIG']['NEWSLETTER'] = 1;
         $extra = array2XML($extra_array);

         // save room
         $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }

   // portal
   $query  = "SELECT item_id, extras FROM portal WHERE deletion_date IS NULL ORDER BY item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !empty($newsletter_enabled_for) and in_array($room_id,$newsletter_enabled_for) ) {

         $extra_array = XML2Array( $extra );
         $extra_array['EXTRA_CONFIG']['NEWSLETTER'] = 1;
         $extra = array2XML($extra_array);

         // save room
         $insert_query = 'UPDATE portal SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
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
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>