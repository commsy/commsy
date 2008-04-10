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

// Auf TRUE setzen, um die Einträge zu generieren
$time_start = getmicrotime();

$ernstfall = TRUE;

// Verbindungsdaten
include_once('../migration.conf.php');
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script replaces the creators of the groups 'All users' in project rooms. <br />");
echo ("We used to take the id's of the creators from the comunity room- this script replaces them with their counterpart from within the projectroom");

//Gruppen zählen
$count = array_shift(mysql_fetch_row(mysql_query('SELECT COUNT(labels.item_id) FROM labels WHERE labels.type = "group" AND labels.name="ALL"')));
init_progress_bar($count);

//Alle Gruppen 'ALL' raussuchen
$query = 'SELECT item_id, creator_id, room_id FROM labels WHERE labels.type = "group" AND labels.name="ALL"';
$result_group_all = mysql_query($query);
if($error = mysql_error()) echo $error.". QUERY: ".$query;


$group_item = (mysql_fetch_array($result_group_all));
while ($group_item) {
   //usleep(500);
   //Ersteller der Veranstaltung suchen, zu der die Gruppe alle gehört    
   $query = 'SELECT user.item_id, user.user_id FROM courses, user WHERE courses.room_item_id = "'.$group_item['room_id'].'" ';
   $query .= 'AND user.item_id = courses.creator_id';
   $course_creator = mysql_fetch_array(mysql_query($query));
   if($error = mysql_error()) echo $error.". QUERY: ".$query;
   
   ////Die Id des course-creators im entsprechenden Raum ist unser group-all creator
   $query = 'SELECT item_id FROM user WHERE user.room_id = "'.$group_item['room_id'].'" ';
   $query .= 'AND user_id ="'. $course_creator['user_id'].'"';
   $new_group_creator = mysql_fetch_array(mysql_query($query));
   if($error = mysql_error()) echo $error.". QUERY: ".$query;
   
//Alle Daten sind da, loslegen   
   if ($ernstfall == TRUE) {
    //aus irgenwelchen Gründen gibt es manchmal keinen Account der Courses-Creators in den Räumen...
    //dann das alte beibehalten
    if (!empty($new_group_creator['item_id'])) {
       $query = 'UPDATE labels SET creator_id = "'.$new_group_creator['item_id'].'" ';
       $query .='WHERE item_id = "'.$group_item['item_id'].'"';
       $result = mysql_query($query);
       if($error = mysql_error()) echo $error.". QUERY: ".$query;
       
       //neuen creator zu link_modifier hinzufügen
       $query = 'INSERT INTO link_modifier_item (item_id, modifier_id)';
       $query .='VALUES ('.$group_item['item_id'].','.$new_group_creator['item_id'].')';
       $result = mysql_query($query);
       
       //und alten entfernen
       $query = 'DELETE FROM link_modifier_item WHERE modifier_id = "'.$course_creator['item_id'].'" ';
       $query .='AND item_id = "'.$group_item['item_id'].'"';
       $result = mysql_query($query);
       
    }
   } else {
      echo ("grp-id: ".$group_item['item_id']."   creator-id: ".$new_group_creator['item_id']."<br />"); 
   }
   
   
   update_progress_bar($count);      
   $group_item = (mysql_fetch_array($result_group_all));
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br />Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60));






function init_progress_bar($count) {
   echo "<br />total: ".$count;
   echo "<br />....................................................................................................|100%";
   echo "<br />";
   flush();
}

function update_progress_bar($total) {
   static $i = 0;
   static $percent = 0;
   $i++;
   $cur_percent = (int)(($i*100)/($total) );
   if($percent < $cur_percent) {
      $add = $cur_percent-$percent;
      while($add>0) {
         $add--;
          echo ".";
      }
      $percent = $cur_percent;
      flush();
   }
   if($i==$total) {
      $i = 0;
      $percent = 0;
   }
}

function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

?>