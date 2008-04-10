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
echo ('This script copies the description of the courses to the project rooms and empty table courses.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

$count_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room WHERE room.deletion_date IS NULL AND room.type='project' AND room.title!='';")));
if ($count_rooms < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_rooms);

   // project rooms
   $query  = "SELECT item_id, extras FROM room WHERE room.deletion_date IS NULL AND room.type='project' AND room.title!='';";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      $extra_array = xml2array($extra);

      $query2 = 'SELECT c.description,c.item_id,l.item_id FROM course AS c
                 INNER JOIN link_items AS l
                    ON l.first_item_id=c.item_id
                       AND l.second_item_id='.$room_id.'';
      $result2 = select($query2);
      $row2 = mysql_fetch_row($result2);
      $desc = $row2[0];
      $course_id = $row2[1];
      $link_id = $row2[2];

      if (!empty($desc) or !empty($place) or !empty($time)) {
         $temp_desc = '';
         if (!empty($desc)) {
            $temp_desc .= $desc;
         }

         if (!empty($temp_desc)) {
            $temp_desc_array = array();
            $temp_desc_array['DE'] = $temp_desc;
            $temp_desc_array['EN'] = $temp_desc;

            $extra_array['DESCRIPTION'] = $temp_desc_array;
            $extra = array2XML($extra_array);

            $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
            select($insert_query);
         }
      }

      // delete link course - project
      $query2 = 'UPDATE link_items SET deleter_id="99", deletion_date=NOW() WHERE item_id="'.$link_id.'";';
      $result2 = select($query2);

      // delete course
      $query2 = 'UPDATE course SET deleter_id="99", deletion_date=NOW() WHERE item_id="'.$course_id.'";';
      $result2 = select($query2);

      // link room to topics and institutions and material of course (part 1)
      $query2 = 'UPDATE link_items SET first_item_id="'.$room_id.'", first_item_type="project" WHERE first_item_id="'.$course_id.'" AND first_item_type="course" AND (second_item_type="material" or second_item_type="institution" or second_item_type="topic");';
      $result2 = select($query2);

      // link room to topics and institutions and material of course (part 2)
      $query2 = 'UPDATE link_items SET second_item_id="'.$room_id.'", second_item_type="project" WHERE second_item_id="'.$course_id.'" AND second_item_type="course" AND (first_item_type="material" or first_item_type="institution" or first_item_type="topic");';
      $result2 = select($query2);

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