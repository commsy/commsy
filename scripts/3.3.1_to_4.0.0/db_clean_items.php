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
echo ('This script cleans up the plural forms of rubrics in the item-table and replaces them with the singular form'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(items.item_id) FROM items WHERE items.deletion_date IS NULL;")));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // project projects
   $query  = "SELECT items.item_id,type FROM items WHERE items.deletion_date IS NULL ORDER BY items.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $items_id = $row[0];
   $type = $row[1];
   while ($items_id) {
	   $change = false;
	   if (preg_match('/^(.+)s$/',$type)){
		   $type = preg_replace('/^(.+)s$/','$1',$type);
			$change = true;
		}
		if ($change) { #  pr ($type);  

			// save change
			$insert_query = 'UPDATE items SET type="'.addslashes($type).'" WHERE item_id="'.$items_id.'"';
			if ($test) {
				pr ($insert_query);
			} else {
				select($insert_query);
			}
		}
      $row = mysql_fetch_row($result);
      $items_id = $row[0];
      $type = $row[1];
      update_progress_bar($count);
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