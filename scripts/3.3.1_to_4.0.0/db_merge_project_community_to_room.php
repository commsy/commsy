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

echo ('This script merge the table project and community to room.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

#init_progress_bar('1');

   // rename project
   $query  = "ALTER TABLE project RENAME room;";
   $result = select($query);
#   update_progress_bar('1');

   // add column type
   $query  = "ALTER TABLE room ADD type VARCHAR( 10 ) DEFAULT 'project' NOT NULL;";
   $result = select($query);
#   update_progress_bar('1');

   $query  = "SELECT * FROM community";
   $result = select($query);
   $row = mysql_fetch_assoc($result);
   $item_id = $row['item_id'];
   while ($item_id) {
      $insert_query = 'INSERT INTO room SET item_id="'.$row['item_id'].'",
                                   room_id="'.$row['room_id'].'",
                                   creator_id="'.$row['creator_id'].'",
                                   modifier_id="'.$row['modifier_id'].'",
                                   creation_date="'.$row['creation_date'].'",
                                   modification_date="'.$row['modification_date'].'",
                                   title="'.addslashes($row['title']).'",
                                   extras="'.addslashes($row['extras']).'",
                                   status="'.$row['status'].'",
                                   activity="'.$row['activity'].'",
                                   type="community"';
      if ( !empty($row['deletion_date']) and $row['deletion_date'] != '0000-00-00 00:00:00' ) {
         $insert_query .=          ',deletion_date="'.$row['deletion_date'].'"';
      }
      if ( !empty($row['deletion_date']) and $row['deletion_date'] != '0' ) {
         $insert_query .=          ',deleter_id="'.$row['deleter_id'].'"';
      }
      $new_id = insert($insert_query);
      if (!empty($new_id)) {
         $success = true;
      }
      $row = mysql_fetch_assoc($result);
      $item_id = $row['item_id'];
   }

   // add column type
   $query  = "DROP TABLE community;";
   $result = select($query);

   // add column type
   $query  = "ALTER TABLE server ADD type VARCHAR( 10 ) DEFAULT 'server' NOT NULL;";
   $result = select($query);

      // add column type
   $query  = "ALTER TABLE portal ADD type VARCHAR( 10 ) DEFAULT 'portal' NOT NULL;";
   $result = select($query);

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