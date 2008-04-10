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

echo ('This script adds a table "server" to the db moves the server item from project into this table.'."\n");

$root_item_id = array_shift(mysql_fetch_row(select("SELECT item_id FROM user WHERE user_id='root';")));

$query = "CREATE TABLE IF NOT EXISTS `server` (
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
   $query = 'SELECT * FROM project WHERE item_id = "99";';
   $result = select($query);
   $row = mysql_fetch_assoc($result);
   if ( !empty($row['item_id']) ) {
      $query = 'INSERT INTO server SET item_id="99", room_id="0", creator_id="'.$root_item_id.'", modifier_id="'.$root_item_id.'", creation_date=NOW(), modification_date=NOW(), extras="'.addslashes($row['extras']).'", activity="'.$row['activity'].'", status="1";';
      insert($query);
      if ($success) {
         $query = 'DELETE FROM project WHERE item_id="99";';
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