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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

// init database connection
#include_once('../migration.conf.php');
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script checks the status of all users in community rooms. ");
echo ("If a user is an orgnizer of a project room, the status in the community room will be set to organizer.");

// count user
$count = array_shift(mysql_fetch_row(mysql_query('SELECT COUNT(user.item_id) FROM user WHERE user.room_id IS NULL AND user.status = "2"')));
init_progress_bar($count);

// get all user
$query = 'SELECT item_id, user_id, campus_id FROM user WHERE user.room_id IS NULL AND user.status = "2"';
$result_user = mysql_query($query);
if($error = mysql_error()) echo $error.". QUERY: ".$query;

$user_item = mysql_fetch_array($result_user);
while ($user_item) {

   // count user as organizer in other projectrooms
   $query = 'SELECT COUNT(user.item_id) AS count
             FROM user
                INNER JOIN rooms ON
                rooms.item_id = user.room_id
                AND rooms.deletion_date IS NULL
             WHERE user.room_id IS NOT NULL
             AND user.status = "4"
             AND user.deletion_date IS NULL
             AND user.campus_id = "'.$user_item['campus_id'].'"
             AND user.user_id = "'.$user_item['user_id'].'"';
   $count_user = mysql_fetch_array(mysql_query($query));
   if($error = mysql_error()) echo $error.". QUERY: ".$query;

   // and now, do it
   if ($do_it) {
      if ($count_user['count'] > 0) {
         $query = 'UPDATE user SET status = "4" ';
         $query .='WHERE item_id = "'.$user_item['item_id'].'"';
         $result = mysql_query($query);
         if ($error = mysql_error() ) {
            echo ($error.". QUERY: ".$query."<br />");
            $success == false;
         }
      }
      update_progress_bar($count);
   } else {
      echo ("user-id: ".$user_item['user_id']."  item-id: ".$user_item['item_id']." campus-id: ".$user_item['campus_id']."<br />");
   }
   $user_item = mysql_fetch_array($result_user);
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>