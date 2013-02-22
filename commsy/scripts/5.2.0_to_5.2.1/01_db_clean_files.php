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
echo ('This script cleans up the files table'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(files.files_id) FROM files WHERE context_id="0";')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   // get wrong file entries
   $query  = 'SELECT files.files_id FROM files WHERE context_id="0";';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $files_id = $row[0];
   while ($files_id) {
   
      // get item_id
      $query2 = 'SELECT item_iid FROM item_link_file WHERE file_id="'.$files_id.'";';
      $result2 = select($query2);
      $row2 = mysql_fetch_row($result2);
      $item_id = $row2[0];
      
      // get context_id
      if ( !empty($item_id) ) {      
         $query3 = 'SELECT context_id FROM items WHERE item_id="'.$item_id.'";';
         $result3 = select($query3);
         $row3 = mysql_fetch_row($result3);
         $context_id = $row3[0];
      
         if ( !empty($context_id) ) {
            // correct wrong file entries
            $query4 = 'UPDATE files SET context_id="'.$context_id.'" WHERE files_id="'.$files_id.'"';
            $result4 = insert($query4);
         } else {
            $success = false;
         }
      } else {
         $success = false;
      }
      
	   $row = mysql_fetch_row($result);
      $files_id = $row[0];
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
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>