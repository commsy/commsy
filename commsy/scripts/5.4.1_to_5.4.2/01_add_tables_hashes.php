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
echo ('CommSy database, table hash will be added.'."\n");
$success = true;

$query = 'SHOW TABLES;';
$result = select($query);
$db_tables = array();
if ( mysql_num_rows($result) > 0 ) {
   while ( $row = mysql_fetch_row($result) ) {
      if ( isset($row[0]) and !empty($row[0]) ) {
         $db_tables[] = $row[0];
      }
   }
}

if ( in_array('hash',$db_tables) ) {
   echo('<br/>nothning to do: table exists'."\n");
} elseif ( in_array('hashes',$db_tables) ) {
   echo('<br/>rename table hashes to hash');
   $query  = "RENAME TABLE `hashes` TO `hash`;";
   $success = select($query);

   if ($success) {
      echo(' [ <font color="#00ff00">done</font> ]<br/>'."\n");
   } else {
      echo(' [ <font color="#ff0000">failed</font> ]<br/>'."\n");
   }

} else {
   echo('<br/>create table');
   $query  = "CREATE TABLE IF NOT EXISTS `hash` (
             `user_item_id` int(11) NOT NULL,
             `rss` char(32) default NULL,
             `ical` char(32) default NULL,
             PRIMARY KEY  (`user_item_id`)
             ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
   $success = select($query);

   if ($success) {
      echo(' [ <font color="#00ff00">done</font> ]<br/>'."\n");
   } else {
      echo(' [ <font color="#ff0000">failed</font> ]<br/>'."\n");
   }
}
flush();
sleep(1);

echo('<br/><br/>migration of hash values');
$query = 'SELECT item_id,extras FROM room WHERE extras like "%RSS_HASH_ARRAY%"';
$result = select($query);
if ( mysql_num_rows($result) > 0 ) {
   $count = mysql_num_rows($result);
   init_progress_bar($count);
   while ($row = mysql_fetch_row($result)) {
      $item_id = $row[0];
      $extra_string = $row[1];
      $extra_array = XML2Array($extra_string);
      $rss_hash_array = $extra_array['RSS_HASH_ARRAY'];
      foreach ( $rss_hash_array as $user_item_id => $rss_hash ) {
         $query = 'SELECT rss FROM hash WHERE user_item_id="'.$user_item_id.'"';
         $result2 = select($query);
         if ( mysql_num_rows($result2) > 0 ) {
            $row2 = mysql_fetch_row($result2);
            if ( empty($row2[0]) or $row2[0] == 'NULL' ) {
               $query = 'UPDATE hash SET rss="'.$rss_hash.'" WHERE user_item_id="'.$user_item_id.'";';
               $success_update = select($query);
               $success = $success and $success_update;
            }
         } else {
            $query = 'INSERT INTO hash SET user_item_id="'.$user_item_id.'", rss="'.$rss_hash.'";';
            $success_insert = insert($query);
            $success = $success and $success_insert;
         }
      }
      unset($extra_array['RSS_HASH_ARRAY']);
      $extra_string = Array2XML($extra_array);

      $query = 'UPDATE room SET extras="'.addslashes($extra_string).'" WHERE item_id="'.$item_id.'";';
      $success_room = select($query);
      $success = $success and $success_room;

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