<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2008 Iver Jackewitz
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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

// init database connection
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script re-insert connections: user 2 group ALL in projectrooms.");

$counter = 0;

// get all rooms not deleted
$count_rooms = array_shift(mysql_fetch_row(mysql_query("SELECT COUNT(room.item_id) FROM room WHERE room.type='project' AND room.deletion_date IS NULL AND room.deleter_id IS NULL;")));
if ($count_rooms < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count_rooms);
   $query  = "SELECT room.item_id FROM room WHERE room.type='project' AND room.deletion_date IS NULL AND room.deleter_id IS NULL ORDER BY room.item_id;";
   $result = mysql_query($query);
   if ( $error = mysql_error() ) {
      echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
   }
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   while ($room_id) {
      // get group all for room
      $query  = "SELECT labels.item_id FROM labels WHERE labels.context_id='".$room_id."' AND labels.name='ALL' AND labels.deletion_date IS NULL and labels.deleter_id IS NULL;";
      $result2 = mysql_query($query);
      if ( $error = mysql_error() ) {
         echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
      }
      $row2 = mysql_fetch_row($result2);
      $group_all_id = $row2[0];

      if ( !empty($group_all_id) ) {

         // get all users in project room
         $query  = "SELECT user.item_id FROM user WHERE user.context_id='".$room_id."' AND user.status > 1 AND user.deletion_date IS NULL AND user.deleter_id IS NULL ORDER BY user.item_id;";
         $result2 = mysql_query($query);
         if ( $error = mysql_error() ) {
            echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
         }
         $row2 = mysql_fetch_row($result2);
         $user_id = $row2[0];
         while ($user_id) {
            // check if user is connected to group all
            $query  = "SELECT link_items.item_id FROM link_items WHERE
                       ((link_items.first_item_id='".$group_all_id."' AND
                        link_items.first_item_type='group' AND
                        link_items.second_item_id='".$user_id."' AND
                        link_items.second_item_type='user') OR
                       (link_items.second_item_id='".$group_all_id."' AND
                        link_items.second_item_type='group' AND
                        link_items.first_item_id='".$user_id."' AND
                        link_items.first_item_type='user')) AND
                        link_items.deletion_date IS NULL AND
                        link_items.deleter_id IS NULL;";
            $result3 = mysql_query($query);
            if ( $error = mysql_error() ) {
               echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
            }
            $row3 = mysql_fetch_row($result3);
            $link_id = $row3[0];
            if (empty($link_id)) {
               // insert link between user and group ALL
               $insert_query = 'INSERT INTO items ( context_id , type , deleter_id , deletion_date , modification_date )
                                VALUES ('.$room_id.' , "link_item", NULL , NULL, "'.date("Y-m-d H:i:s").'")';
               mysql_query($insert_query);
               if ($error = mysql_error()) {
                  echo $error." QUERY: ".$insert_query;
                  $success = false;
               } else {
                  $link_id = mysql_insert_id();

                  $insert_query = 'INSERT INTO link_items ( item_id, context_id , creator_id , deleter_id ,
                                                            creation_date , modification_date , deletion_date , first_item_id ,
                                                            first_item_type , second_item_id , second_item_type )
                                   VALUES ("'.$link_id.'", "'.$room_id.'", "'.$user_id.'", NULL , "'
                                             .date("Y-m-d H:i:s").'", "'.date("Y-m-d H:i:s").'", NULL , "'.$user_id.'", '
                                             .'"user", "'.$group_all_id.'", '
                                             .'"group")';
                  mysql_query($insert_query);
                  if ($error = mysql_error()) {
                     echo '<br />'.$error." QUERY: ".$insert_query.'<br />'."\n";
                     $success = false;
                  }
               }
               $counter++;
            }

            // next user
            $row2 = mysql_fetch_row($result2);
            $user_id = $row2[0];
         }
      }

      // next room
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $campus_id = $row[1];
      update_progress_bar($count_rooms);
   }
}

echo('<br/>re-insert '.$counter.' connections to group all');
// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>