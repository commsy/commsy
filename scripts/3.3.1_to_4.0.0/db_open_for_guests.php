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
echo ('This script creates and sets the is_open_for_guests column on the room, portal and server tables'."\n");
$success = true;

echo ('room table:'."\n");
if (mysql_fetch_row(select('SHOW COLUMNS FROM room LIKE "is_open_for_guests" '))) {
   echo "<br/>nothing to do.";
   flush();
} else {
	echo ('creating is_open_for_guests column:'."\n");
	$query = 'ALTER TABLE room ADD is_open_for_guests TINYINT DEFAULT 0 NOT NULL';
	$success = select($query);
	if ($success) {
		echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");	
		
		echo ('setting collumn values:'."\n");
   	$query = 'UPDATE room SET is_open_for_guests = 1 WHERE type LIKE "community"';
   	$success = select($query);
		
		if ($success) {
			echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
		} else {
			echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
		}
	} else {
		echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
	}
}

echo ('portal table:'."\n");
if (mysql_fetch_row(select('SHOW COLUMNS FROM portal LIKE "is_open_for_guests" '))) {
   echo "<br/>nothing to do.";
   flush();
} else {
	echo ('creating is_open_for_guests column:'."\n");
	$query = 'ALTER TABLE portal ADD is_open_for_guests TINYINT DEFAULT 1 NOT NULL';
	$success = select($query);
	if ($success) {
		echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");	
	} else {
		echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
	}
}

echo ('server table:'."\n");
if (mysql_fetch_row(select('SHOW COLUMNS FROM server LIKE "is_open_for_guests" '))) {
   echo "<br/>nothing to do.";
   flush();
} else {
	echo ('creating is_open_for_guests column:'."\n");
	$query = 'ALTER TABLE server ADD is_open_for_guests TINYINT DEFAULT 1 NOT NULL';
	$success = select($query);
	if ($success) {
		echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");	
	} else {
		echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
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