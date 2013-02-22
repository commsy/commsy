<?php
//
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// add column "has_html" to table "files"
echo ('CommSy database, table files: insert file size.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(files_id) FROM files WHERE size="0"')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   $query  = "SELECT * FROM files WHERE size='0'";
   $success = select($query);

   $query  = 'SELECT files_id, files.context_id, filename, room.context_id AS portal_id FROM files INNER JOIN room ON files.context_id=room.item_id WHERE size="0"';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $item_id = $row[0];
   $context_id = $row[1];
   $filename = $row[2];
   $portal_id = $row[3];
   while ($item_id) {
      $filename_on_disc = '../../var/'.$portal_id.'/'.$context_id.'/cid'.$context_id.'_'.$item_id.'_'.$filename;
      if (file_exists($filename_on_disc)) {
         $size = filesize($filename_on_disc);
         if ( !empty($size) ) {
            $query = 'UPDATE files SET size="'.$size.'" WHERE files_id="'.$item_id.'";';
            $succ = select($query);
            $success = $success and $succ;
         }
      }
   
	   $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $context_id = $row[1];
      $filename = $row[2];
      $portal_id = $row[3];
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