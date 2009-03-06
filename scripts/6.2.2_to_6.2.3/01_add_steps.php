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
echo ('CommSy database, table steps will be added.'."\n");
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

if ( in_array('step',$db_tables) ) {
   echo('<br/>nothning to do: table exists'."\n");
}  else {
   echo('<br/>create table');
   $query  = "CREATE TABLE `step` (
`item_id` int( 11 ) NOT NULL default '0',
`context_id` int( 11 ) default NULL ,
`creator_id` int( 11 ) NOT NULL default '0',
`modifier_id` int( 11 ) default NULL ,
`creation_date` datetime default '0000-00-00 00:00:00',
`deleter_id` int( 11 ) default NULL ,
`deletion_date` datetime default NULL ,
`modification_date` datetime default NULL ,
`title` varchar( 255 ) NOT NULL default '',
`description` text,
`minutes` float( 11 ) NOT NULL default '0',
`time_type` smallint( 6 ) NOT NULL default '1',
`todo_item_id` int( 11 ) NOT NULL,
PRIMARY KEY ( `item_id` ) ,
KEY `room_id` ( `context_id` ) ,
KEY `creator_id` ( `creator_id` ) ,
KEY `todo_item_id` ( `todo_item_id` )
) ENGINE = MYISAM DEFAULT CHARSET = latin1;
";
   $success = select($query);

   if ($success) {
      echo(' [ <font color="#00ff00">done</font> ]<br/>'."\n");
   } else {
      echo(' [ <font color="#ff0000">failed</font> ]<br/>'."\n");
   }
}
flush();
sleep(1);

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>