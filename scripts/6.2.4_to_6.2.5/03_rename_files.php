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
set_time_limit(0);

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('commsy: rename files to fileID'.LINEBREAK);
$success = true;

// get file ids
$count = array_shift(mysql_fetch_row(select('SELECT count( * ) FROM files')));
if ($count < 1) {
   echo "nothing to do.";
} else {
   init_progress_bar($count);
   $sql = 'SELECT files.files_id, files.context_id, files.filename FROM files';
   $result = select($sql);
   while ($row = mysql_fetch_assoc($result)) {
      // get portal id
      if ( $row['context_id'] != 99 ) {
         $sql2 = 'SELECT context_id FROM items WHERE item_id="'.$row['context_id'].'";';
         $result2 = select($sql2);
         $row2 = mysql_fetch_assoc($result2);
         if ($row2['context_id'] == 99) {
            $first_folder = $row['context_id'];
         } else {
            $first_folder = $row2['context_id'];
         }
      } else {
         $first_folder = '99';
      }
      $row['first_folder'] = $first_folder;
      $row['second_folder'] = $row['context_id'];
      $row['disc_filename'] = 'cid'.$row['second_folder'].'_'.$row['files_id'].'_'.$row['filename'];
      $row['full_disc_filename'] = '../../var/'.$row['first_folder'].'/'.$row['second_folder'].'/'.$row['disc_filename'];
      $row['new_full_disc_filename'] = '../../var/'.$row['first_folder'].'/'.$row['second_folder'].'/'.$row['files_id'];

      // rename file
      if ( file_exists($row['full_disc_filename']) ) {
         echo ($row['full_disc_filename'] . ' -> ' . $row['new_full_disc_filename'] . "\n");
         rename($row['full_disc_filename'], $row['new_full_disc_filename']);
      }

      // clean filename
      if ( $row['filename'] != rawurldecode($row['filename'])) {
         $sql = 'UPDATE files SET filename="'.addslashes(rawurldecode($row['filename'])).'" WHERE files_id="'.$row['files_id'].'";';
         select($sql);
      }

      update_progress_bar($count);
   }
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>