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
echo ('This script insert links from courses to project room in the community room.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(courses.item_id) FROM courses WHERE room_item_id IS NOT NULL;")));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // course
   $query  = "SELECT courses.item_id, courses.room_item_id, courses.room_id, courses.creator_id FROM courses WHERE room_item_id IS NOT NULL ORDER BY courses.room_item_id DESC;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $course_id = $row[0];
   $project_id = $row[1];
   $course_user_id = $row[3];
   $course_room_id = $row[2];
   while ($project_id) {
      // first insert item in table items to get item_id
      $query2 = "INSERT INTO items SET room_id='".$course_room_id."', type='link_item';";
      $new_item_id = insert($query2);

      // second insert link_item
      $query3 = "INSERT INTO link_items SET item_id = '".$new_item_id."', room_id='".$course_room_id."', creator_id = '".$course_user_id."', creation_date = NOW(), first_item_id = '".$course_id."', first_item_type = 'course', second_item_id = '".$project_id."', second_item_type = 'project';";
      insert($query3);
      
      update_progress_bar($count);

      $row = mysql_fetch_row($result);
      $course_id = $row[0];
      $project_id = $row[1];
      $course_user_id = $row[3];
      $course_room_id = $row[2];
   }

}

$query4 = "ALTER TABLE courses DROP room_item_id;";
insert($query4);


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