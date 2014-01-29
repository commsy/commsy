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
echo ('db: reinsert items in table items'."\n");
$success = true;

$table = array();
$table[] = 'annotations';
$table[] = 'announcement';
$table[] = 'auth_source';
$table[] = 'dates';
$table[] = 'discussionarticles';
$table[] = 'discussions';
$table[] = 'homepage_page';
$table[] = 'labels';
$table[] = 'link_items';
$table[] = 'materials';
$table[] = 'portal';
$table[] = 'room';
$table[] = 'section';
$table[] = 'server';
$table[] = 'tag';
$table[] = 'tasks';
$table[] = 'todos';
$table[] = 'user';

$error_array = array();

foreach ( $table as $tbl ) {
   echo("<br/><br/>".$tbl);
   $count1 = array_shift(mysql_fetch_row(select("SELECT COUNT(*) FROM ".$tbl." INNER JOIN items ON ".$tbl.".item_id=items.item_id;")));
   $count2 = array_shift(mysql_fetch_row(select("SELECT COUNT(*) FROM ".$tbl.";")));
   $count = $count2-$count1;
   if ($count < 1) {
      echo "<br />nothing to do.";
   } else {
      init_progress_bar($count);

      $query  = "SELECT ".$tbl.".item_id FROM ".$tbl." INNER JOIN items ON ".$tbl.".item_id=items.item_id;";
      $result = select($query);
      $item_ids = array();
      while ($row = mysql_fetch_assoc($result) ) {
         $item_ids[] = $row['item_id'];
      }
      $query = "SELECT * FROM ".$tbl;
      if ( !empty($item_ids) ) {
         $query .= " WHERE item_id NOT IN (".implode(',',$item_ids).")";
      }
      if ( $tbl == 'materials'
           or $tbl == 'section'
         ) {
         $query .= ' ORDER BY '.$tbl.'.version_id DESC';
      }
      $query .= ';';
      $result = select($query);
      $saved_item_ids = array();
      while ($row = mysql_fetch_assoc($result) ) {
         if ( !in_array($row['item_id'],$saved_item_ids) ) {
            $query  = 'INSERT INTO items SET';
            $query .= ' item_id="'.$row['item_id'].'"';
            $query .= ' ,context_id="'.$row['context_id'].'"';
            if ( $tbl == 'room' ) {
               $query .= ' ,type="'.$row['type'].'"';
            } else {
               $query .= ' ,type="'.DBTable2Type($tbl).'"';
            }
            if ( !empty($row['deleter_id']) ) {
               $query .= ' ,deleter_id="'.$row['deleter_id'].'"';
            }
            if ( !empty($row['deletion_date']) ) {
               $query .= ' ,deletion_date="'.$row['deletion_date'].'"';
            }
            if ( !empty($row['modification_date']) ) {
               $query .= ' ,modification_date="'.$row['modification_date'].'";';
            }
            $result2 = insert($query);
         }
         update_progress_bar($count);
         $saved_item_ids[] = $row['item_id'];
      }
   }
}
echo("\n".'<br/>');

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>