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
echo ('This script cleans up the ontology in the &lt;HOMECONF&gt;'."\n");
$success = true;

$count_project = array_shift(mysql_fetch_row(select("SELECT COUNT(room.item_id) FROM room WHERE extras LIKE '%ontology_%';")));
if ($count_project < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_project);

   // project projects
   $query  = "SELECT room.item_id,extras FROM room WHERE extras LIKE '%ontology_%' ORDER BY room.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $project_id = $row[0];
   $extra = $row[1];
   while ($project_id) {
      $treffer = array();
      $treffer2 = array();
      $home_conf_exist = preg_match('~(<HOMECONF>(.+)</HOMECONF>)~u', $extra, $treffer);
      if ($home_conf_exist == true) {
         if (mb_strpos($extra,'ontology_')!=false){
            $home_conf_string = str_replace(',ontology_short','',$treffer[2]);
            $home_conf_string = str_replace(',ontology_tiny','',$home_conf_string);
            $home_conf_string = str_replace(',ontology_none','',$home_conf_string);
            $home_conf_string = str_replace('ontology_short,','',$home_conf_string);
            $home_conf_string = str_replace('ontology_tiny,','',$home_conf_string);
            $home_conf_string = str_replace('ontology_none,','',$home_conf_string);
            $extra = preg_replace('~(<HOMECONF>.+</HOMECONF>)~u', '<HOMECONF>'.$home_conf_string.'</HOMECONF>', $extra);
            // save project
            $insert_query = 'UPDATE room SET extras="'.addslashes($extra).'" WHERE item_id="'.$project_id.'"';
            select($insert_query);
         }
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
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>