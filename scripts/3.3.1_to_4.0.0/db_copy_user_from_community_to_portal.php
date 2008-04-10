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
set_time_limit(0);

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('This script copies all users in a community room into the corresponding portal.'."\n");

$array = array();
$count = array_shift(mysql_fetch_row(select("SELECT COUNT(user.item_id) FROM user, community WHERE user.room_id=community.item_id;")));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   $query99  = "SELECT item_id FROM community ORDER BY community.item_id;";
   $result99 = select($query99);
   $row99 = mysql_fetch_assoc($result99);
   $room_id = $row99['item_id'];
   $count = 0;
   $count_new = 0;
   while ($room_id) {
      $count += array_shift(mysql_fetch_row(select("SELECT COUNT(user.item_id) FROM user WHERE user.room_id='".$room_id."' AND user.deleter_id IS NULL and user.deletion_date IS NULL;")));
      $row99 = mysql_fetch_assoc($result99);
      $room_id = $row99['item_id'];
   }
   init_progress_bar($count);
   $query  = "SELECT item_id, room_id FROM community ORDER BY community.item_id;";
   $result = select($query);
   $row = mysql_fetch_assoc($result);
   $room_id = $row['item_id'];
   while ($room_id) {
      $query  = "SELECT * FROM user WHERE room_id = '".$room_id."' AND user.deleter_id IS NULL and user.deletion_date IS NULL ORDER BY user.item_id;";
      $result2 = select($query);
      $row2 = mysql_fetch_assoc($result2);
      $user_id = $row2['item_id'];
      while ($user_id) {
         $count2 = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM user WHERE user_id="'.addslashes($row2['user_id']).'" AND room_id="'.$row['room_id'].'"')));
         if (empty($count2)) {
            $insert_query = 'INSERT INTO items SET room_id="'.$row['room_id'].'", type="user";';
            $new_id = insert($insert_query);
            if ($row2['status'] == 1) {
               $row2['status'] = 2;
            }
            $insert_query = 'INSERT INTO user SET item_id="'.$new_id.'",
                                                  room_id="'.$row['room_id'].'",
                                                  creator_id="'.$new_id.'",
                                                  creation_date="'.$row2['creation_date'].'",
                                                  modification_date="'.$row2['modification_date'].'",
                                                  user_id="'.addslashes($row2['user_id']).'",
                                                  status="'.$row2['status'].'",
                                                  firstname="'.addslashes($row2['firstname']).'",
                                                  lastname="'.addslashes($row2['lastname']).'",
                                                  email="'.addslashes($row2['email']).'",
                                                  city="'.addslashes($row2['city']).'",
                                                  visible="'.$row2['visible'].'",
                                                  extras="'.addslashes($row2['extras']).'";';
            $new_id2 = insert($insert_query);
            if (!empty($new_id2)) {
               $success = true;
            }
         } else {
            $success = true;
         }

         $count2 = array_shift(mysql_fetch_row(select_auth('SELECT COUNT(user_id) FROM auth WHERE user_id="'.addslashes($row2['user_id']).'" AND commsy_id="'.$row['room_id'].'"')));
         if (empty($count2)) {
            $success = false;
            $query9 = 'UPDATE auth SET commsy_id="'.$row['room_id'].'" WHERE commsy_id="'.$row2['room_id'].'" AND user_id="'.addslashes($row2['user_id']).'"';
            $success = select_auth($query9);
         }

         $row2 = mysql_fetch_assoc($result2);
         $user_id = $row2['item_id'];
         update_progress_bar($count);
         $count_new++;
      }
      $row = mysql_fetch_assoc($result);
      $room_id = $row['item_id'];
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