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
echo ('This script adds the field "auth_source" to the table user anf fills it with the item id of the default auth source'."\n");

if (mysql_fetch_row(select('SHOW COLUMNS FROM user LIKE "auth_source" '))) {
   $success = true;
} else {
   $query = 'ALTER TABLE `user` ADD `auth_source` INT DEFAULT NULL;';
   $success = select($query);
}

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(portal.item_id) FROM portal WHERE portal.deletion_date IS NULL;")));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);

   $query  = "SELECT portal.item_id, portal.extras FROM portal WHERE portal.deletion_date IS NULL;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $item_id = $row[0];
   $extra = $row[1];
   while ($item_id) {
      $room_id_array = array();
      $query2 = 'SELECT room.item_id FROM room WHERE context_id = "'.$item_id.'";';
      $result2 = select($query2);
      $row2 = mysql_fetch_row($result2);
      $room_id = $row2[0];
      while ($room_id) {
         $room_id_array[] = $room_id;
         $row2 = mysql_fetch_row($result2);
         $room_id = $row2[0];
      }

      $extra_array = xml2Array($extra);
      $default_auth_source_id = $extra_array['DEFAULT_AUTH'];
      $room_id_array[] = $item_id;

      $update_query = 'UPDATE user SET auth_source="'.$default_auth_source_id.'" WHERE context_id IN ('.implode(',',$room_id_array).')';
      $success2 = select($update_query);
      $success = $success and $success2;

      $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>