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

// save server item from item table
$sql = 'select modification_date from items where item_id="99";';
$result = select($sql);
$server_row = mysql_fetch_assoc($result);

// time management for this script
$time_start = getmicrotime();

echo ('delete deleted content'."\n");
echo ('<br>----------------------'."\n");
$success = true;

$context_array = array();
$context_array[] = 99; // server

$sql = 'select item_id from portal where deletion_date is NULL and deleter_id is NULL;';
$result = select($sql);
while ( $row = mysql_fetch_assoc($result) ) {
   $context_array[] = $row['item_id'];
}

$sql = 'select item_id from room where deletion_date is NULL and deleter_id is NULL AND context_id in('.implode(',',$context_array).') order by item_id';
$result = select($sql);
while ( $row = mysql_fetch_assoc($result) ) {
   $context_array[] = $row['item_id'];
}

$datetime = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), (date('d')-30), date('Y')));
$sql = 'select item_id from room where deletion_date > "'.$datetime.'" AND context_id in('.implode(',',$context_array).') order by item_id';
$result = select($sql);
while ( $row = mysql_fetch_assoc($result) ) {
   $context_array[] = $row['item_id'];
}

$table = array();
$table[] = 'annotations';
$table[] = 'announcement';
$table[] = 'auth'; // -> commsy_id
$table[] = 'auth_source';
$table[] = 'dates';
$table[] = 'discussionarticles';
$table[] = 'discussions';
$table[] = 'files';
$table[] = 'homepage_link_page_page';
$table[] = 'homepage_page';
$table[] = 'items';
$table[] = 'labels';
$table[] = 'links';
$table[] = 'link_items';
$table[] = 'log'; // -> cid
$table[] = 'log_ads'; // -> cid
$table[] = 'log_archive'; // -> cid
$table[] = 'log_error'; // -> context
$table[] = 'materials';
$table[] = 'portal';
$table[] = 'room';
$table[] = 'section';
$table[] = 'tag';
$table[] = 'tag2tag';
$table[] = 'tasks';
$table[] = 'todos';
$table[] = 'user';

foreach ( $table as $tbl ) {
   echo("<br/>".$tbl);
   $sql = 'delete from '.$tbl.' WHERE ';
   if ( $tbl == 'log'
        or $tbl == 'log_ads'
        or $tbl == 'log_archive'
      ) {
      $sql .= 'cid';
   } elseif ( $tbl == 'auth' ) {
      $sql .= 'commsy_id';
   } elseif ( $tbl == 'log_error' ) {
      $sql .= 'context';
   } else {
      $sql .= 'context_id';
   }
   $sql .= ' not in ('.implode(',',$context_array).')';
   $result = select($sql);
}

echo('<br/><br/>special tables'."\n");

$table = array();
$table[] = 'external2commsy_id'; // -> commsy_id
$table[] = 'hash'; // -> user_item_id
$table[] = 'item_link_file'; // -> item_iid, file_id
$table[] = 'link_modifier_item'; // -> item_id , modifier_id
$table[] = 'noticed'; // -> item_id , user_id
$table[] = 'reader'; // -> item_id , user_id

foreach ( $table as $tbl ) {
   echo("<br/><br/>".$tbl);
   $count = array_shift(mysql_fetch_row(select("SELECT COUNT(*) FROM ".$tbl.";")));
   if ($count < 1) {
      echo "<br />nothing to do.";
   } else {
      init_progress_bar($count);

      $query  = "SELECT * FROM ".$tbl.";";
      $result = select($query);
      while ( $row = mysql_fetch_assoc($result) ) {
         if ( $tbl == 'external2commsy_id' ) {
            $item_id = $row['commsy_id'];
         } elseif ( $tbl == 'hash' ) {
            $item_id = $row['user_item_id'];
         } elseif ( $tbl == 'item_link_file' ) {
            $item_id = $row['item_iid'];
         } else {
            $item_id = $row['item_id'];
         }
         $sql = 'SELECT count(*) as count FROM items WHERE item_id='.$item_id.';';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);
         if ( empty($row2['count']) or $row2['count'] == 0 ) {
            $sql = 'DELETE FROM '.$tbl.' WHERE ';
            $arg_array = array();
            foreach ( $row as $key => $value ) {
               $arg_array[] = $key.'="'.$value.'"';
            }
            $sql .= implode(' AND ',$arg_array);
            $sql .= ';';
            $result2 = select($sql);
         }

         if ( $tbl == 'item_link_file' ) {
            $sql = 'SELECT count(*) as count FROM files WHERE files_id='.$row['file_id'].';';
            $result2 = select($sql);
            $row2 = mysql_fetch_assoc($result2);
            if ( empty($row2['count']) or $row2['count'] == 0 ) {
               $sql = 'DELETE FROM '.$tbl.' WHERE ';
               $arg_array = array();
               foreach ( $row as $key => $value ) {
                  $arg_array[] = $key.'="'.$value.'"';
               }
               $sql .= implode(' AND ',$arg_array);
               $sql .= ';';
               $result2 = select($sql);
            }
         }

         if ( $tbl == 'noticed' or $tbl == 'reader' ) {
            $sql = 'SELECT count(*) as count FROM user WHERE item_id='.$row['user_id'].';';
            $result2 = select($sql);
            $row2 = mysql_fetch_assoc($result2);
            if ( empty($row2['count']) or $row2['count'] == 0 ) {
               $sql = 'DELETE FROM '.$tbl.' WHERE ';
               $arg_array = array();
               foreach ( $row as $key => $value ) {
                  $arg_array[] = $key.'="'.$value.'"';
               }
               $sql .= implode(' AND ',$arg_array);
               $sql .= ';';
               $result2 = select($sql);
            }
         }

         update_progress_bar($count);
      }
   }
}

$sql = 'select count(*) as count from items where item_id="99";';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( empty($row)
     or empty($row['count'])
     or $row['count'] == 0
   ) {
   $sql  = 'insert into items set item_id="99", type="server"';
   if ( !empty($server_row[0]) ) {
      $sql .= ', modification_date="'.$server_row[0].'"';
    } else {
      $sql .= ', modification_date=now()';
   }
   $sql .= ';';
   $result = select($sql);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>