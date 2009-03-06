<?php
// $Id$
//
// Release $Name$
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
echo ('Delete double entries in link_items table.'."\n");
$success = true;
$store = array();

$query = 'SELECT * FROM `link_items` WHERE `second_item_type` LIKE "material"';
$result = select($query);
if ( mysql_num_rows($result) > 0 ) {
   $count = mysql_num_rows($result);
   init_progress_bar($count);
   while ($row = mysql_fetch_assoc($result)) {

      if ( isset($store[$row['first_item_id']]) ) {
         if ( in_array($row['second_item_id'],$store[$row['first_item_id']]) ) {
            // delete link_item
            $query = 'UPDATE link_items SET deletion_date=now(), deleter_id=creator_id WHERE item_id="'.$row['item_id'].'";';
            $success = select($query);
         } else {
            $store[$row['first_item_id']][] = $row['second_item_id'];
         }
      } else {
         $store[$row['first_item_id']][] = $row['second_item_id'];
      }

      update_progress_bar($count);
   }
} else {
   echo('<br/>nothing to do');
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>