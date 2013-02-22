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

// move configuration of ads from cs_config to database
echo ('This script creates a table to insert information about auth sources'."\n");
if (mysql_fetch_row(select('SHOW TABLES LIKE "auth_source" '))) {
   echo "<br/>nothing to do."."\n";
   flush();
   $success = true;
} else {
   echo ('<br/>creating table'."\n");

   $query = "CREATE TABLE `auth_source` (
   `item_id` int( 11 ) NOT NULL default '0',
   `context_id` int( 11 ) default NULL ,
   `creator_id` int( 11 ) NOT NULL default '0',
   `modifier_id` int( 11 ) default NULL ,
   `deleter_id` int( 11 ) default NULL ,
   `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
   `modification_date` datetime NOT NULL default '0000-00-00 00:00:00',
   `deletion_date` datetime default NULL ,
   `title` varchar( 255 ) NOT NULL default '',
   `extras` text default NULL,
   PRIMARY KEY ( `item_id` ) ,
   KEY `room_id` ( `context_id` ) ,
   KEY `creator_id` ( `creator_id` )
   ) ENGINE = MYISAM";
   $success = select($query);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>