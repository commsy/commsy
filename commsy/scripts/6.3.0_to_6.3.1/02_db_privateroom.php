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
set_time_limit(0);

include_once('../migration.conf.php');
include_once('../db_link.dbi_utf8.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('database: add table room_privat'.LINEBREAK);
$sql = 'SHOW TABLES;';
$result = select($sql);
unset($sql);
$table_array = array();
while ( $row = mysql_fetch_assoc($result) ) {
   $table_array[] = $row['Tables_in_'.$db['normal']['database']];
}
mysql_free_result($result);
if ( !in_array('room_privat',$table_array) ) {

   $sql = " CREATE  TABLE  `room_privat` (  `item_id` int( 11  )  NOT  NULL default  '0',
 `context_id` int( 11  )  default NULL ,
 `creator_id` int( 11  )  NOT  NULL default  '0',
 `modifier_id` int( 11  )  default NULL ,
 `deleter_id` int( 11  )  default NULL ,
 `creation_date` datetime NOT  NULL default  '0000-00-00 00:00:00',
 `modification_date` datetime NOT  NULL default  '0000-00-00 00:00:00',
 `deletion_date` datetime  default NULL ,
 `title` varchar( 255  )  NOT  NULL ,
 `extras` text,
 `status` varchar( 20  )  NOT  NULL ,
 `activity` int( 11  )  NOT  NULL default  '0',
 `type` varchar( 20  )  NOT  NULL default  'privateroom',
 `public` tinyint( 11  )  NOT  NULL default  '0',
 `is_open_for_guests` tinyint( 4  )  NOT  NULL default  '0',
 `continuous` tinyint( 4  )  NOT  NULL default  '-1',
 `template` tinyint( 4  )  NOT  NULL default  '-1',
 PRIMARY  KEY (  `item_id`  ) ,
 KEY  `context_id` (  `context_id`  ) ,
 KEY  `creator_id` (  `creator_id`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8;";
   select($sql);
   unset($sql);

   $sql = 'INSERT INTO room_privat SELECT * FROM room;';
   select($sql);
   unset($sql);

   $sql = "DELETE  FROM `room_privat` WHERE `type` != 'privateroom';";
   select($sql);
   unset($sql);

   $sql = "DELETE  FROM `room` WHERE `type` = 'privateroom';";
   select($sql);
   unset($sql);

   echo('done');
} else {
   echo('nothing to do');
}

$success = true;

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>