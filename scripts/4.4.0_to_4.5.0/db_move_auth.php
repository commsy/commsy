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
echo ('This script moves the auth table from the auth database to the commsy database'."\n");

$done = false;
$link_test = mysql_pconnect ($AUTH_Hostname, $AUTH_Username, $AUTH_Password);
$db_link_test = @mysql_select_db($AUTH_Name,$link_test) or $done = true;

if ($done) {
	$success = true;
	echo('<br/>nothing to do.');
} else {
	// create table
	$query = "CREATE TABLE IF NOT EXISTS `auth` (
	  `commsy_id` int(11) NOT NULL default '0',
	  `user_id` varchar(32) NOT NULL default '',
	  `password_md5` varchar(32) NOT NULL default '',
	  `firstname` varchar(50) NOT NULL default '',
	  `lastname` varchar(50) NOT NULL default '',
	  `email` varchar(100) NOT NULL default '',
	  `language` char(3) NOT NULL default '',
	  PRIMARY KEY  (`user_id`,`commsy_id`)
	) TYPE=MyISAM;";
	$success = select($query);
	
	// move data
	$count = array_shift(mysql_fetch_row(select_auth("SELECT COUNT(user_id) FROM auth;")));
	if ($count < 1) {
		echo "<br/>nothing to do.";
	} else {
		init_progress_bar($count_user);
	
		$query = 'SELECT * FROM auth';
		$auth_result = select_auth($query);
		while ($row = mysql_fetch_assoc($auth_result)) {
			$insert_query = 'INSERT INTO auth SET ';
			$first = true;
			foreach ($row as $key => $value) {
				if ($first) {
					$first = false;
				} else {
					$insert_query .= ',';
				}
				$insert_query .= $key.'="'.addslashes($value).'"';
			}
			$success = $success and insert($insert_query);
			update_progress_bar($count);
		}
	}
	
	// delete table auth
	$query = "DROP TABLE `auth`;";
	$success = $success and select_auth($query);
	
	// delete table auth
	$query = "DROP DATABASE ".$AUTH_Name.";";
	$success = $success and select_auth($query);
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