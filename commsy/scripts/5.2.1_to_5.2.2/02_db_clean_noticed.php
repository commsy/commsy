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
echo ('This script cleans up the table: noticed'."\n");
$success = true;

$query  = 'DROP TABLE IF EXISTS noticed2;';
$result = select($query);

$query  = 'CREATE TABLE IF NOT EXISTS noticed2 (
  item_id int(11) NOT NULL default "0",
  version_id int(11) NOT NULL default "0",
  user_id int(11) NOT NULL default "0",
  read_date datetime NOT NULL default "0000-00-00 00:00:00",
  PRIMARY KEY  (item_id,version_id,user_id,read_date)
) ENGINE=MyISAM;
';
$result = select($query);

$query  = 'INSERT INTO noticed2 SELECT item_id, version_id, user_id, MAX(read_date) as read_date FROM noticed GROUP BY item_id,version_id,user_id;';
$result = select($query);

$query  = 'TRUNCATE TABLE noticed';
$result = select($query);

$query  = 'INSERT INTO noticed SELECT item_id, version_id, user_id, read_date FROM noticed2;';
$result = select($query);

$query  = 'DROP TABLE noticed2';
$result = select($query);

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