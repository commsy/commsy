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
echo ('This script creates a table to insert reader information of index pages'."\n");
if (mysql_fetch_row(select('SHOW TABLES LIKE "noticed" '))) {
   echo "<br/>nothing to do."."\n";
   flush();
   $success = true;
} else {
   echo ('<br/>creating table'."\n");
   flush();

   $query = "CREATE TABLE `noticed` (
            `item_id` int( 11 ) NOT NULL default '0',
            `version_id` int( 11 ) NOT NULL default '0',
            `user_id` int( 11 ) NOT NULL default '0',
            `read_date` datetime NOT NULL default '0000-00-00 00:00:00',
            PRIMARY KEY ( `item_id` , `version_id` , `user_id` , `read_date` ) ,
            KEY `user_id` ( `user_id` ) ,
            KEY `item_id` ( `item_id` )
            ) ENGINE = MYISAM;";
   $success = select($query);

   // um mysql eine verschnaufpause zu gönnen
   sleep(5);

   $query = "INSERT INTO `noticed`
            SELECT *
            FROM reader";
   $success2 = select($query);
   $success = $success and $success2;
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>