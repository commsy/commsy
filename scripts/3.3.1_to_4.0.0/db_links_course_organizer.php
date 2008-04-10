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
echo ('This script insert links from courses to organizers in the community room.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(user.item_id) FROM user WHERE status = '4' AND room_id IS NOT NULL;")));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // user
   $query  = "SELECT user.item_id, user.user_id, user.campus_id, user.room_id FROM user WHERE status = '4' AND room_id IS NOT NULL ORDER BY user.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $user_item_id = $row[0];
   $user_user_id = $row[1];
   $user_communityroom_id = $row[2];
   $user_projectroom_id = $row[3];
   while ($user_item_id) {

      // get course id
      $query2  = "SELECT courses.item_id FROM courses WHERE room_item_id = '".$user_projectroom_id."' AND deletion_date IS NULL;";
      $result2 = select($query2);
      $row2 = mysql_fetch_row($result2);
      $course_id = $row2[0];

      if ( !empty($course_id) ) {

         // get user item id in community room
         $query3  = "SELECT user.item_id FROM user WHERE user_id = '".$user_user_id."' AND campus_id = '".$user_communityroom_id."' AND room_id IS NULL AND deletion_date IS NULL;";
         $result3 = select($query3);
         $row3 = mysql_fetch_row($result3);
         $user_item_id_in_communityroom = $row3[0];

         if ( !empty($user_item_id_in_communityroom) ) {
            // insert link course <--> user ... means organizer

            // first insert item in table items to get item_id
            $query4 = "INSERT INTO items SET campus_id='".$user_communityroom_id."', type='link_item';";
            $new_item_id = insert($query4);

            // second insert link_item
            $query5 = "INSERT INTO link_items SET item_id = '".$new_item_id."', campus_id='".$user_communityroom_id."', creator_id = '".$user_item_id_in_communityroom."', creation_date = NOW(), first_item_id = '".$course_id."', first_item_type = 'course', second_item_id = '".$user_item_id_in_communityroom."', second_item_type = 'user';";
            insert($query5);
         }
      }


      update_progress_bar($count);

      $row = mysql_fetch_row($result);
      $user_item_id = $row[0];
      $user_user_id = $row[1];
      $user_communityroom_id = $row[2];
      $user_projectroom_id = $row[3];
   }
}

// in project rooms
$query = "UPDATE user SET status='3' WHERE status='4' AND room_id IS NOT NULL";
$success = select($query);

// in community rooms
$query = "UPDATE user SET status='2' WHERE status='4' AND room_id IS NULL";
$success = select($query);

// delete project rooms with empty title
$query = "UPDATE rooms SET deletion_date=NOW() WHERE title='';";
$success = select($query);

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