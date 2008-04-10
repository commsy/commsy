<?php
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
echo ('This script changes the configuration of the name of rubrics.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

$count_project = array_shift(mysql_fetch_row(select("SELECT COUNT(project.item_id) FROM project WHERE project.deletion_date IS NULL;")));
$count_community = array_shift(mysql_fetch_row(select("SELECT COUNT(community.item_id) FROM community WHERE community.deletion_date IS NULL;")));
$count_rooms = $count_project + $count_community;
if ($count_rooms < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_rooms);

   // project project
   $query  = "SELECT project.item_id, project.extras FROM project WHERE project.deletion_date IS NULL ORDER BY project.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
     if ( strstr($extra,'</COURSE>') ) {
        $extra = preg_replace('$<COURSE><NAME>courses</NAME>$','<COURSE><NAME>course</NAME>',$extra);
     }
     if ( strstr($extra,'</TOPIC>') ) {
        $extra = preg_replace('$<TOPIC><NAME>topics</NAME>$','<TOPIC><NAME>topic</NAME>',$extra);
     }

      // save room
      $insert_query = 'UPDATE project SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
      select($insert_query);
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }

   // community project
   $query  = "SELECT community.item_id, community.extras FROM community WHERE community.deletion_date IS NULL ORDER BY community.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( strstr($extra,'</COURSE>') ) {
         $extra = preg_replace('$<COURSE><NAME>courses</NAME>$','<COURSE><NAME>course</NAME>',$extra);
      }
      if ( strstr($extra,'</TOPIC>') ) {
         $extra = preg_replace('$<TOPIC><NAME>topics</NAME>$','<TOPIC><NAME>topic</NAME>',$extra);
      }

      // save room
      $insert_query = 'UPDATE community SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
      select($insert_query);
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