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

echo ('This script adds a table "portal" to the db and copy all campus entries into this table.<br/>After that campus become community and rooms become project.'."\n");

$root_item_id = array_shift(mysql_fetch_row(select("SELECT item_id FROM user WHERE user_id='root';")));

$query = "CREATE TABLE IF NOT EXISTS `portal` (
  `item_id` int(11) NOT NULL default '0',
  `room_id` int(11) NOT NULL default '0',
  `creator_id` int(11) NOT NULL default '0',
  `modifier_id` int(11) default NULL,
  `deleter_id` int(11) default NULL,
  `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `deletion_date` datetime default NULL,
  `title` varchar(255) NOT NULL default '',
  `extras` text,
  `status` varchar(20) NOT NULL default '',
  `activity` int(11) NOT NULL default '0',
  PRIMARY KEY  (`item_id`),
  KEY `room_id` (`room_id`),
  KEY `creator_id` (`creator_id`)
) TYPE=MyISAM;
";

$portal_array = array();
$success = select($query);
if ($success) {
   $query = 'SELECT count(item_id) FROM portal';
   $result = select($query);
   $row = mysql_fetch_row($result);
   if ( $row[0] == 0 ) {
      unset($count);
      $count = array_shift(mysql_fetch_row(select("SELECT COUNT(campus.item_id) FROM campus;")));
      if ($count < 1) {
         echo "<br/>nothing to do.";
      } else {
         init_progress_bar($count);

         $query  = "SELECT * FROM campus ORDER BY campus.item_id;";
         $result = select($query);
         $row = mysql_fetch_assoc($result);
         $room_id = $row['item_id'];
         while ($room_id) {

            // save portal
            $insert_query = 'INSERT INTO items SET room_id="99", type="portal";';
            $portal_id = insert($insert_query);
            $portal_array[$room_id] = $portal_id;

            if ( !empty($portal_id) and $portal_id > 99 ) {
               $extra = $row['extras'];
               if ( strstr($extra,'<INSTITUTIONS>') ) {
                  $extra = preg_replace('$<INSTITUTIONS>(.|\n)*</INSTITUTIONS>\n$','',$extra);
                  $extra = preg_replace('$<INSTITUTIONS>(.|\n)*</INSTITUTIONS>$','',$extra);
               }
               if ( strstr($extra,'<INSTITUTIONSYNSINGULAR>') ) {
                  $extra = preg_replace('$<INSTITUTIONSYNSINGULAR>(.|\n)*</INSTITUTIONSYNSINGULAR>\n$','',$extra);
                  $extra = preg_replace('$<INSTITUTIONSYNSINGULAR>(.|\n)*</INSTITUTIONSYNSINGULAR>$','',$extra);
               }
               if ( strstr($extra,'<INSTITUTIONSYNPLURAL>') ) {
                  $extra = preg_replace('$<INSTITUTIONSYNPLURAL>(.|\n)*</INSTITUTIONSYNPLURAL>\n$','',$extra);
                  $extra = preg_replace('$<INSTITUTIONSYNPLURAL>(.|\n)*</INSTITUTIONSYNPLURAL>$','',$extra);
               }
               if ( strstr($extra,'<CONTEXT>') ) {
                  $extra = preg_replace('$<CONTEXT>(.|\n)*</CONTEXT>\n$','',$extra);
                  $extra = preg_replace('$<CONTEXT>(.|\n)*</CONTEXT>$','',$extra);
               }
               if ( strstr($extra,'<SEMESTER>') ) {
                  $extra = preg_replace('$<SEMESTER>(.|\n)*</SEMESTER>\n$','',$extra);
                  $extra = preg_replace('$<SEMESTER>(.|\n)*</SEMESTER>$','',$extra);
               }
               if ( strstr($extra,'<RUBRIC_TRANSLATION_ARRAY>') ) {
                  $extra = preg_replace('$<RUBRIC_TRANSLATION_ARRAY>(.|\n)*</RUBRIC_TRANSLATION_ARRAY>\n$','',$extra);
                  $extra = preg_replace('$<RUBRIC_TRANSLATION_ARRAY>(.|\n)*</RUBRIC_TRANSLATION_ARRAY>$','',$extra);
               }
               if ( strstr($extra,'<INSTITUTION_ARRAY>') ) {
                  $extra = preg_replace('$<INSTITUTION_ARRAY>(.|\n)*</INSTITUTION_ARRAY>\n$','',$extra);
                  $extra = preg_replace('$<INSTITUTION_ARRAY>(.|\n)*</INSTITUTION_ARRAY>$','',$extra);
               }

               $insert_query = 'INSERT INTO portal SET room_id="99", item_id="'.$portal_id.'", creator_id="'.$root_item_id.'", creation_date=NOW(), modification_date=NOW(), title="'.$row['title'].'", extras="'.addslashes($extra).'", status="1";';
               insert($insert_query);

               if ($success) {
                  $update_query = 'UPDATE campus SET room_id="'.$portal_array[$room_id].'" WHERE item_id="'.$room_id.'";';
                  select($update_query);
               }
            }

            $row = mysql_fetch_assoc($result);
            $room_id = $row['item_id'];
            update_progress_bar($count);
         }

         $query = 'SHOW COLUMNS FROM campus';
         $success = select($query);
         $field_array = array();
         while ($row = mysql_fetch_row($success) ) {
            $field_array[] = $row[0];
         }
         if (in_array('campus_id',$field_array)) {
            $query = 'ALTER TABLE campus DROP campus_id;';
            select($query);
         }
         unset($field_array);

         $query = 'ALTER TABLE `campus` RENAME `community`;';
         select($query);

         $query = 'UPDATE items SET type="community" WHERE type="campus";';
         select($query);

         $query = 'UPDATE community SET status="2" WHERE status="0";';
         select($query);
      }
      unset($count);
      $count = array_shift(mysql_fetch_row(select("SELECT COUNT(rooms.item_id) FROM rooms;")));
      if ($count < 1) {
         echo "<br/>nothing to do.";
      } else {
         init_progress_bar($count);

         $query  = "SELECT item_id, campus_id FROM rooms ORDER BY item_id;";
         $result = select($query);
         $row = mysql_fetch_assoc($result);
         $room_id = $row['item_id'];
         while ($room_id) {

            // first insert item in table items to get item_id
            $query4 = "INSERT INTO items SET campus_id='".$portal_array[$row['campus_id']]."', type='link_item';";
            $new_item_id = insert($query4);

            // second insert link_item
            $query5 = "INSERT INTO link_items SET item_id = '".$new_item_id."', campus_id='".$portal_array[$row['campus_id']]."', creator_id = '".$root_item_id."', creation_date = NOW(), first_item_id = '".$room_id."', first_item_type = 'project', second_item_id = '".$row['campus_id']."', second_item_type = 'community';";
            insert($query5);

            // update project room
            $query = "UPDATE rooms SET room_id='".$portal_array[$row['campus_id']]."' WHERE item_id='".$room_id."';";
            select($query);
            $row = mysql_fetch_assoc($result);
            $room_id = $row['item_id'];
            update_progress_bar($count);
         }

         $query = 'SHOW COLUMNS FROM rooms';
         $success = select($query);
         $field_array = array();
         while ($row = mysql_fetch_row($success) ) {
            $field_array[] = $row[0];
         }
         if (in_array('campus_id',$field_array)) {
            $query = 'ALTER TABLE rooms DROP campus_id;';
            select($query);
         }
         unset($field_array);

         $query = 'ALTER TABLE `rooms` RENAME `project`;';
         select($query);

         $query = 'UPDATE items SET type="project" WHERE type="rooms";';
         select($query);
      }
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
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>