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
echo ('This script set CHECKNEWMEMBERS in extra field to 1 if it is empty and to -1 if it is 0.'."\n");
$success = true;

$count_rooms = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM room;')));
$count_portals = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM portal;')));
$count = $count_rooms + $count_portals;
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // rooms
   $query  = 'SELECT room.item_id,extras FROM room;';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {

      $extra_array = xml2array($extra);
      $change_extra = false;
      if (!isset($extra_array['CHECKNEWMEMBERS'])) {
         $extra_array['CHECKNEWMEMBERS'] = 1;
         $change_extra = true;
      }
      if (empty($extra_array['CHECKNEWMEMBERS']) or $extra_array['CHECKNEWMEMBERS'] == 0) {
         $extra_array['CHECKNEWMEMBERS'] = -1;
         $change_extra = true;
      }

      if ($change_extra) {
         $extra = array2xml($extra_array);
         $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         $success = select($insert_query);
      }

      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count);
   }

   // portals
   $query  = 'SELECT portal.item_id,extras FROM portal;';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {

      $extra_array = xml2array($extra);
      if (!$extra_array['CHECKNEWMEMBERS'] == 1) {
         $extra_array['CHECKNEWMEMBERS'] = -1;
         $extra = array2xml($extra_array);
         $insert_query = 'UPDATE portal SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         $success = select($insert_query);
      }

      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count);
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