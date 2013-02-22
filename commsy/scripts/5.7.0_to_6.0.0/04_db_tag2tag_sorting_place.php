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
echo ('tag2tag: fill sorting_place'."\n");
$success = true;

$sql = 'SELECT count(*) AS anzahl FROM tag2tag WHERE deleter_id IS NULL AND deletion_date IS NULL AND sorting_place IS NULL;';
$result = select($sql);
$row = mysql_fetch_assoc($result);
$count = $row['anzahl'];

if ( $count > 0 ) {
   $children_array = array();
   $father_array = array();
   $sorting_place_array = array();
   $must_sort_father_array = array();
   $sql = 'SELECT * FROM tag2tag WHERE deleter_id IS NULL AND deletion_date IS NULL ORDER BY sorting_place;';
   $result = select($sql);
   while ( $row = mysql_fetch_assoc($result) ) {
      $father_array[$row['to_item_id']] = $row['from_item_id'];
      $children_array[$row['from_item_id']][] = $row['to_item_id'];
      $sorting_place_array[$row['to_item_id']] = $row['sorting_place'];
   }

   foreach ( $children_array as $father_id => $child_array ) {
      foreach (  $child_array as $child_id ) {
         $must_sort_father_array[] = $father_id;
      }
   }

   $sql_array = array();
   foreach (  $must_sort_father_array as $father_id ) {
      $sql = 'SELECT * FROM tag WHERE item_id IN ('.implode(',',$children_array[$father_id]).') ORDER BY title';
      $result = select($sql);
      $new_place_array = array();
      $place = 0;
      while ( $row = mysql_fetch_assoc($result) ) {
         $place++;
         $new_place_array[$row['item_id']] = $place;
      }
      foreach ($children_array[$father_id] as $child_id) {
         if ( !empty($new_place_array[$child_id]) ) {
            $sql = 'UPDATE tag2tag SET sorting_place='.$new_place_array[$child_id].' WHERE from_item_id='.$father_id.' AND to_item_id='.$child_id.';';
            $sql_array[] = $sql;
         }
      }
   }
   init_progress_bar(count(array_unique($sql_array)));
   foreach ( array_unique($sql_array) as $sql ) {
      $result = select($sql);
      update_progress_bar(count(array_unique($sql_array)));
   }
} else {
   echo('<br/>nothing to do'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>