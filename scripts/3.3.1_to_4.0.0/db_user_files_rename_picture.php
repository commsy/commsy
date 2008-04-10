<?php
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
echo ('This script updates file names to the new structure in user items'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM user;')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   $query  = 'SELECT item_id,extras FROM user;';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $id = $row[0];
   $extra = $row[1];
   while ($id) {
      if (strstr($extra,'<USERPICTURE>')) {
         $change_extra = false;
         $extra_array = xml2array($extra);
         $name = $extra_array['USERPICTURE'];
         if (is_array($name)) {
            $name = $name['NAME'];
         }
         preg_match('/cid(.+?)_rid(.+?)_(.+)/', $name, $matches);
         if (!empty($matches)) {
  	   if ($matches[2] == '0') {
  	      $new_name = 'cid'.$matches[1].'_'.$matches[3];
  	   } else {
  	      $new_name = 'cid'.$matches[2].'_'.$matches[3];
  	   }
            $change_extra = true;
            $name = $new_name;
         }
         if ($change_extra) {
            $extra_array['USERPICTURE'] = $name;
            $extra = array2xml($extra_array);
            $insert_query = 'UPDATE user SET extras="'.addslashes($extra).'" WHERE item_id="'.$id.'"';
            $success = select($insert_query);
         } else {
            $success = true;
         }
      }

      $row = mysql_fetch_row($result);
      $id = $row[0];
      $extra = $row[1];
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