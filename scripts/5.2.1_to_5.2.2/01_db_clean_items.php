<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2007 Iver Jackewitz
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
echo ('This script cleans up the items table'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(room.item_id) FROM room')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // get wrong file entries
   $query  = 'SELECT room.item_id, room.context_id FROM room';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $item_id = $row[0];
   $context_id = $row[1];
   while ($item_id) {
   
      // get item_id
      $query2 = 'SELECT context_id FROM items WHERE item_id="'.$item_id.'";';
      $result2 = select($query2);
      $row2 = mysql_fetch_row($result2);
      $context_id2 = $row2[0];
      
      // get context_id
      if ( empty($context_id2) or ( !empty($context_id2) and $context_id != $context_id2 ) ) {      
         $query3 = 'UPDATE items SET context_id="'.$context_id.'"WHERE item_id="'.$item_id.'";';
         $result3 = select($query3);
      }
      
	   $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $context_id = $row[1];
      update_progress_bar($count);
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
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>