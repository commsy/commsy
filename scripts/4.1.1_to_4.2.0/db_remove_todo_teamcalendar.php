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
echo ('This script removes switches (todo and teamcalendar) out of the extra fields of project rooms'."\n");
$success = true;
flush();

$count_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room;")));
if ($count_rooms < 1) {
   echo "nothing to do."."\n";
} else {
   init_progress_bar($count_rooms);
   $query  = "SELECT room.item_id, room.extras FROM room ORDER BY room.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( strstr($extra,'</TODO>') or strstr($extra,'</TEAMCALENDAR>') ) {
         if ( strstr($extra,'</TODO>') ) {
            $extra = preg_replace('$<TODO>[0-9]</TODO>\n$','',$extra);
         }
         if ( strstr($extra,'</TEAMCALENDAR>') ) {
            $extra = preg_replace('$<TEAMCALENDAR>[0-9]</TEAMCALENDAR>\n$','',$extra);
         }

         // save room
         $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>