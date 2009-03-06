<?php
//
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// add column "has_html" to table "files"
echo ('CommSy database: create root tag item for rooms.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM room WHERE deletion_date IS NULL AND deleter_id IS NULL;')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   $query  = "SELECT * FROM room WHERE deletion_date IS NULL AND deleter_id IS NULL;";
   $result = select($query);
   while ( $row = mysql_fetch_assoc($result) ) {

      $sql = 'SELECT item_id FROM tag WHERE title="CS_TAG_ROOT" AND context_id="'.$row['item_id'].'"';
      $result2 = select($sql);
      $row2 = mysql_fetch_assoc($result2);
      if ( empty($row2['item_id']) ) {
         $sql = 'INSERT INTO items SET type="tag", context_id="'.$row['item_id'].'", modification_date=now();';
         $new_item_id = insert($sql);

         $sql  = 'INSERT INTO tag SET ';
         $sql .= ' item_id="'.$new_item_id.'",';
         $sql .= ' context_id="'.$row['item_id'].'",';
         $sql .= ' creator_id="99",';
         $sql .= ' modifier_id="99",';
         $sql .= ' creation_date=now(),';
         $sql .= ' modification_date=now(),';
         $sql .= ' title="CS_TAG_ROOT";';
         insert($sql);
      }

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