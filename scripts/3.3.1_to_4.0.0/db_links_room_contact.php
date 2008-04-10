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
echo ('This script insert contacts from courses to project room.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(link_items.item_id) FROM link_items WHERE first_item_type="course" and second_item_type="user"')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);
   $query0  = "ALTER TABLE user ADD is_contact TINYINT DEFAULT '0' NOT NULL AFTER status ;";
   $result0 = select($query0);

   // course
   $query  = 'SELECT link_items.* FROM link_items WHERE first_item_type="course" and second_item_type="user"';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $course_id = $row[7];
   $user_id = $row[9];
   $deleter_id = $row[3];
   $creation_date = $row[4];
   $context_id = $row[1];
   $link_item_id = $row[0];
   while ($link_item_id) {
      $room_id='';
      $query4  = 'SELECT link_items.* FROM link_items WHERE first_item_type="course" and second_item_type="project" and first_item_id="'.$course_id.'"';
      $result2 = select($query4);
      $row2 = mysql_fetch_row($result2);
      if (!empty($row2)){
         if (empty($row[3]) and empty($row[5])){
            $room_id = $row2[9];
         }
      }
      if (!empty($room_id)){
         $query4  = 'SELECT user.item_id FROM user LEFT JOIN user AS user_temp ON user.user_id=user_temp.user_id WHERE user_temp.item_id="'.$user_id.'" and user.context_id="'.$room_id.'"';
         $result4 = select($query4);
         $row4 = mysql_fetch_row($result4);
         $user_id = $row4[0];
         // first insert item in table items to get item_id
         $query5 = 'UPDATE user SET is_contact="1" WHERE user.item_id="'.$user_id.'" and user.context_id="'.$room_id.'"';
         select($query5);
      }
      update_progress_bar($count);
      $row = mysql_fetch_row($result);
      $course_id = $row[7];
      $user_id = $row[9];
      $creation_date = $row[4];
      $deleter_id = $row[3];
      $context_id = $row[1];
      $link_item_id = $row[0];
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