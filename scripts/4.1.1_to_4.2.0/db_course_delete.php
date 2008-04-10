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
echo ('This script deletes the rubrik courses out of the database.'."\n");
$success = true;

echo ('<br/>delete course table:'."\n");
if (!mysql_fetch_row(select('SHOW TABLES LIKE "course" '))) {
   echo "nothing to do."."\n";
   flush();
} else {
   $query = 'DROP TABLE course';
   $success = select($query);
   if ($success) {
      echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
   }
}

echo("\n".'<br/>delete course and semester from community rooms'."\n");
flush();
$count_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room WHERE type='community';")));
if ($count_rooms < 1) {
   echo "nothing to do."."\n";
} else {
   init_progress_bar($count_rooms);
   $query  = "SELECT room.item_id, room.extras FROM room WHERE type='community' ORDER BY room.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( strstr($extra,'</SEMESTER>')
           or strstr($extra,'course_')
           or strstr($extra,'</COURSE>')
         ) {

         $extra_array = XML2Array($extra);
         unset($extra_array['SEMESTER']);
         unset($extra_array['RUBRIC_TRANSLATION_ARRAY']['COURSE']);
         $home_conf = $extra_array['HOMECONF'];
         $home_conf_array = explode(',',$home_conf);
         $home_conf_array2 = array();
         foreach ($home_conf_array as $value) {
            if (!stristr($value,'course')) {
               $home_conf_array2[] = $value;
            }
         }
         $extra_array['HOMECONF'] = implode(',',$home_conf_array2);
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
}

echo ('<br/><br/>delete course out of item table:'."\n");
$query = 'DELETE FROM items WHERE type="course";';
$success = select($query);
if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
}

echo ('<br/>delete course out of link item table:'."\n");
$query = 'DELETE FROM link_items WHERE first_item_type="course" or second_item_type="course";';
$success = select($query);
if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>