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



function clean_links ($suc,$test) {
   // time management for this script
   $time_start = getmicrotime();

   echo ('This script changes plurals in links to their singular form'."\n");
   flush();
   $rubric_array = array();
   $rubric_array[] = 'annotations';
   $rubric_array[] = 'announcements';
   $rubric_array[] = 'courses';
   $rubric_array[] = 'dates';
   $rubric_array[] = 'discussionarticles';
   $rubric_array[] = 'labels';
   $rubric_array[] = 'materials';
   $rubric_array[] = 'section';
   $rubric_array[] = 'todos';
   $rubric_array[] = 'ontologies';
   $rubric_array[] = 'ont_categories';

	$count = 0;
	foreach ($rubric_array as $rubric) {
	   $rubric_id = "item_id";
      $where_deletion_string = " WHERE ".$rubric.".deletion_date IS NULL";
	   if ($rubric == 'ont_categories') {
		   $rubric_id = "category_id";
			$where_deletion_string = "";
		}
		$query = "SELECT COUNT(".$rubric.".".$rubric_id.") FROM ".$rubric.$where_deletion_string.";";
	   $count += array_shift(mysql_fetch_row(select($query)));

	}

	if ($count < 1) {
   echo "<br/>nothing to do.";
   } else {
      init_progress_bar($count);
		foreach ($rubric_array as $rubric) {
         $rubric_id = "item_id";
         $where_deletion_string =  " WHERE ".$rubric.".deletion_date IS NULL ";
		   if ($rubric == 'ont_categories') {
			   $rubric_id = "category_id";
				$where_deletion_string = " ";
			}

			$query  = 'SELECT '.$rubric.'.'.$rubric_id.',description FROM '.$rubric.$where_deletion_string.'ORDER BY '.$rubric.'.'.$rubric_id.';';
			$result = select($query);
         $row = mysql_fetch_row($result);
			$id = $row[0];
         $description = $row[1];
			while ($id) {
            if (strstr($description,'&mod=dates') OR strstr($description,'&mod=courses') OR strstr($description,'&mod=announcements') OR strstr($description,'&mod=topics')) {
               $description = str_replace("&mod=dates","&mod=date",$description);
					$description = str_replace("&mod=courses","&mod=course",$description);
					$description = str_replace("&mod=announcements","&mod=announcement",$description);
					$description = str_replace("&mod=topics","&mod=topic",$description);
					$insert_query = 'UPDATE '.$rubric.' SET description="'.addslashes($description).'" WHERE '.$rubric_id.'="'.$id.'"';
					if ($test) {
						pr ($insert_query);
					} else {
						select($insert_query);
					}
            }

			   $row = mysql_fetch_row($result);
				$id = $row[0];
            $description= $row[1];
			   update_progress_bar($count);
		   }
		}
	}
	if ($suc) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

	// end of execution time
	$time_end = getmicrotime();
	$time = round($time_end - $time_start,3);
	echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
}

$success = true;
clean_links($success,$test);


?>