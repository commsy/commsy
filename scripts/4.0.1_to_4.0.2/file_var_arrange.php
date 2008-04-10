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

function countFilesInDir ($dir) {
   $counter = 0;
   $folder = '../../var/'.$dir;
   $directory_handle = opendir($folder);
   while (false !== ($entry = readdir($directory_handle)) ) {
      if (stristr($entry,'cid')) {
         $counter++;
      }
   }
   return $counter;
}

function getFileNameInDir ($dir) {
   $retour = '';
   $folder = '../../var/'.$dir;
   $directory_handle = opendir($folder);
   $stop = false;
   while (false !== ($entry = readdir($directory_handle)) and !$stop) {
      if (stristr($entry,'cid')) {
         $retour = $entry;
         $stop = true;
      }
   }
   return $retour;
}

function copyFile ($file_name, $dir, $portal_id, $context_id) {
   $retour = false;
   $folder = '../../var/';
   $first_new = @opendir($folder.$portal_id);
   if (!$first_new) {
      mkdir($folder.$portal_id);
   }
   $second_new = @opendir($folder.$portal_id.'/'.$context_id);
   if (!$second_new) {
      mkdir($folder.$portal_id.'/'.$context_id);
   }
   $retour = copy($folder.$dir.'/'.$file_name,$folder.$portal_id.'/'.$context_id.'/'.$file_name);
   if ($retour) {
      unlink($folder.$dir.'/'.$file_name);
   }
}

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

echo ("This script rearrange the CommSy var directory.");

// pictures
$dir = 'pictures';
$count = countFilesInDir($dir);
init_progress_bar($count);
while ($file_name = getFileNameInDir($dir)) {
   $file_array = explode('_',$file_name);
   $context_id = substr($file_array[0],3);
   if ( empty($context_id) or $context_id == 0 or $context_id == 99 ) {
      $portal_id = 99;
   } else {
      $query = 'SELECT type FROM items WHERE item_id="'.$context_id.'";';
      $result = select($query);
      $row = mysql_fetch_row($result);
      $context_type = $row[0];
      if ($context_type == 'portal') {
         $portal_id = $context_id;
      } else {
         $query = 'SELECT context_id FROM room WHERE item_id="'.$context_id.'";';
         $result = select($query);
         $row = mysql_fetch_row($result);
         $portal_id = $row[0];
      }
   }
   copyFile($file_name,$dir,$portal_id,$context_id);
   update_progress_bar($count);
}

// files
$dir = 'files';
$count = countFilesInDir($dir);
init_progress_bar($count);
while ($file_name = getFileNameInDir($dir)) {
   $file_array = explode('_',$file_name);
   $context_id = substr($file_array[0],3);
   if ( empty($context_id) or $context_id == 0 or $context_id == 99 ) {
      $portal_id = 99;
   } else {
      $query = 'SELECT type FROM items WHERE item_id="'.$context_id.'";';
      $result = select($query);
      $row = mysql_fetch_row($result);
      $context_type = $row[0];
      if ($context_type == 'portal') {
         $portal_id = $context_id;
      } else {
         $query = 'SELECT context_id FROM room WHERE item_id="'.$context_id.'";';
         $result = select($query);
         $row = mysql_fetch_row($result);
         $portal_id = $row[0];
      }
   }
   copyFile($file_name,$dir,$portal_id,$context_id);
   update_progress_bar($count);
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>