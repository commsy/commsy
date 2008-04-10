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
echo ('This script creates and sets the continuous column in the room table'."\n");
$success = true;

echo ('room table:'."\n");
if (mysql_fetch_row(select('SHOW COLUMNS FROM room LIKE "continuous" '))) {
   echo "<br/>nothing to do."."\n";
   flush();
} else {
   echo ('creating continuous column:'."\n");
   $query = 'ALTER TABLE room ADD continuous TINYINT DEFAULT -1 NOT NULL';
   $success = select($query);
   if ($success) {
      echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
      echo ('setting collumn values for community rooms:'."\n");
      $query = 'UPDATE room SET continuous = 1 WHERE type LIKE "community"';
      $success = select($query);
      if ($success) {
         echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
      } else {
         echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
      }
   } else {
      echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
   }
}

echo("\n".'<br/>set closure date of closed rooms: '."\n");
flush();
$count_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room WHERE room.deletion_date IS NULL AND room.status='2';")));
if ($count_rooms < 1) {
   echo "nothing to do."."\n";
} else {
   init_progress_bar($count_rooms);
   $query  = "SELECT room.item_id, room.modification_date, room.extras FROM room WHERE room.deletion_date IS NULL AND room.status='2' ORDER BY room.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $mod_date = $row[1];
   $extra = $row[2];
   while ($room_id) {
      if ( !strstr($extra,'</CLOSURE_DATE>')
           or strstr($extra,'<CLOSURE_DATE></CLOSURE_DATE>') ) {

         if ( !strstr($extra,'</CLOSURE_DATE>') ) {
            $extra .= '<CLOSURE_DATE>'.$mod_date.'</CLOSURE_DATE>';
         } else {
            $extra = preg_replace('$<CLOSURE_DATE></CLOSURE_DATE>$','<CLOSURE_DATE>'.$mod_date.'</CLOSURE_DATE>',$extra);
         }

         // save room
         $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $mod_date = $row[1];
      $extra = $row[2];
      update_progress_bar($count_rooms);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>