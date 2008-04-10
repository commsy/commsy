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
echo ('This script cleans up the plural forms of rubrics in the &lt;HOMECONF&gt; and replaces them with the singular form'."\n");
$success = true;

$count_project = array_shift(mysql_fetch_row(select("SELECT COUNT(project.item_id) FROM project;")));
if ($count_project < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_project);

   // project projects
   $query  = "SELECT project.item_id,extras FROM project ORDER BY project.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $project_id = $row[0];
   $extra = $row[1];
   while ($project_id) {
	   $treffer = array();
      $home_conf_exist = preg_match('§(<HOMECONF>(.+)</HOMECONF>)§', $extra, $treffer);
		if ($home_conf_exist == true) {
		   $home_conf_string = str_replace('dates', 'date', $treffer[2]);   
			$home_conf_string = str_replace('topics', 'topic', $home_conf_string);   
			$home_conf_string = str_replace('announcements', 'announcement', $home_conf_string); 
         $extra = preg_replace('§(<HOMECONF>.+</HOMECONF>)§', '<HOMECONF>'.$home_conf_string.'</HOMECONF>', $extra);
		} 

      // save project
      $insert_query = 'UPDATE project SET extras="'.addslashes($extra).'" WHERE item_id="'.$project_id.'"';
		if ($test) {
         pr ($insert_query);
		} else {
		   select($insert_query);
		}
      $row = mysql_fetch_row($result);
      $project_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_project);
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