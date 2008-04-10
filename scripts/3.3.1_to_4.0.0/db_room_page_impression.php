<?php
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

set_time_limit(0);

// time management for this script
$time_start = getmicrotime();

// move configuration of ads from cs_config to database
echo ('This script updates page impressions of project and community rooms.'."\n");
$success = true;

$pi_array = array();
for ($i=0; $i<100; $i++) {
   $pi_array[$i] = 0;
}

$count_rooms = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM room;')));
$count = $count_rooms;
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   $query = "CREATE TABLE IF NOT EXISTS log_tmp (
`id` int( 11 ) NOT NULL ,
`ip` varchar( 15 ) default NULL ,
`agent` varchar( 250 ) default NULL ,
`timestamp` timestamp( 14 ) NOT NULL ,
`request` varchar( 250 ) default NULL ,
`method` varchar( 10 ) default NULL ,
`uid` int( 11 ) default NULL ,
`ulogin` varchar( 250 ) default NULL ,
`cid` int( 11 ) default NULL ,
`mod` varchar( 250 ) default NULL ,
`fct` varchar( 250 ) default NULL ,
`param` varchar( 250 ) default NULL ,
`iid` int( 11 ) default NULL ,
PRIMARY KEY ( `id` ) ,
KEY `rid` ( `cid` ) ,
KEY `timestamp` ( `timestamp` )
) TYPE = MYISAM ;";
   $result = select($query);

   // rooms
   $query  = 'SELECT room.item_id,extras FROM room ORDER BY item_id;';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {

      $query = 'INSERT INTO log_tmp SELECT * FROM log WHERE cid="'.$room_id.'";';
      $result9 = select($query);
      $query = 'INSERT INTO log_archive SELECT * FROM log WHERE cid="'.$room_id.'";';
      $result12 = select($query, true);
      $query = 'DELETE FROM log WHERE cid="'.$room_id.'";';
      $result10 = select($query);


      // init pi_array in extra field
      $extra_array = xml2array($extra);

      for ($i=0; $i<100; $i++) {
         $first  = 'DATE_SUB(CURRENT_DATE,INTERVAL '.$i.' DAY)';
         $query = 'SELECT count(id) FROM log_tmp WHERE cid = "'.$room_id.'" AND timestamp >= '.$first.';';
         $result2 = select($query);
         $row2 = mysql_fetch_row($result2);
         $second  = 'DATE_SUB(CURRENT_DATE,INTERVAL '.($i+1).' DAY)';
         $query = 'SELECT count(id) FROM log_tmp WHERE cid = "'.$room_id.'" AND timestamp >= '.$second.';';
         $result3 = select($query);
         $row3 = mysql_fetch_row($result3);
         $pi_array[$i] = $row3[0]-$row2[0];
      }

      $extra_array['PAGE_IMPRESSION'] = $pi_array;
      $extra = array2xml($extra_array);
      $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
      $success = select($insert_query);

      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];

      $query = 'DELETE FROM log_tmp;';
      $result11 = select($query);

      update_progress_bar($count);
   }

   $query = 'DROP TABLE log_tmp;';
   $result = select($query);
   $query = 'INSERT INTO log_archive SELECT * FROM log;';
   $result = select($query, true);
   $query = 'DELETE FROM log;';
   $result = select($query);
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