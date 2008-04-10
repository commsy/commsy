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
echo ('This script deletes entrys in the "links" table with "-2" in the "to_item" field. (Bug 1454510)'."\n");
$success = true;
flush();
$count = array_shift(mysql_fetch_row(select("SELECT COUNT(links.to_item_id) FROM links WHERE to_item_id='-2' AND link_type LIKE 'buzzword_for';")));
if ($count < 1) {
   echo "\n".'<br/>Nothing to do.'."\n";
} else {
   echo "\n".'<br/>'.$count.' errors to delete'."\n";
	$query  = "DELETE FROM links WHERE to_item_id='-2' AND link_type LIKE 'buzzword_for';";
	$success = select($query);   
}

if ($success) {
   echo("\n".'<br/>'.'[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo("\n".'<br/>'.'[ <font color="#ff0000">failed</font> ]<br/>'."\n");	
}
 
// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>