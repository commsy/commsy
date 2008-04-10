<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
echo ('This script creates tables for the optional tool homepage'."\n");
$success = true;

$query = "CREATE TABLE IF NOT EXISTS `homepage_page` (
  `item_id` int(11) NOT NULL default '0',
  `context_id` int(11) default NULL,
  `creator_id` int(11) NOT NULL default '0',
  `modifier_id` int(11) default NULL,
  `deleter_id` int(11) default NULL,
  `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `modification_date` datetime default NULL,
  `deletion_date` datetime default NULL,
  `title` varchar(255) NOT NULL default '',
  `description` text,
  `public` tinyint(11) NOT NULL default '0',
  `page_type` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`item_id`),
  KEY `room_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) TYPE=MyISAM";
$success = select($query);

$query = "CREATE TABLE IF NOT EXISTS `homepage_link_page_page` (
  `link_id` int(11) NOT NULL auto_increment,
  `from_item_id` int(11) NOT NULL default '0',
  `to_item_id` int(11) NOT NULL default '0',
  `context_id` int(11) NOT NULL default '0',
  `creator_id` int(11) NOT NULL default '0',
  `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifier_id` int(11) NOT NULL default '0',
  `modification_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `deleter_id` int(11) default NULL,
  `deletion_date` datetime default NULL,
  `sorting_place` tinyint(4) default NULL,
  PRIMARY KEY  (`link_id`),
  KEY `context_id` (`context_id`),
  KEY `form_item_id` (`from_item_id`)
) TYPE=MyISAM";
$success = $success and select($query);

$query = "CREATE TABLE IF NOT EXISTS `homepage_page_link_file` (
  `homepage_page_iid` int(11) NOT NULL default '0',
  `homepage_page_vid` int(11) NOT NULL default '0',
  `file_id` int(11) NOT NULL default '0',
  `deleter_id` int(11) default NULL,
  `deletion_date` datetime default NULL,
  PRIMARY KEY  (`homepage_page_iid`,`homepage_page_vid`,`file_id`)
) TYPE=MyISAM";
$success = $success and select($query);

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