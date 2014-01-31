<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

// new version of the update mechanism
// -----------------------------------
// the following is part of the method "asHTML"
// from the object cs_update_view.php

ini_set("memory_limit","4000M");
set_time_limit(0);

function collectEmptyFolder ( $dir, $file_array ) {
   $dir = str_replace('//','/',$dir);
   $directory_handle  = opendir($dir);
   $counter = 0;
   while ( false !== ($entry = readdir($directory_handle)) ) {
      $counter++;
      if ( $entry != '.'
           and $entry != '..'
           and is_dir($dir.'/'.$entry)
           and is_numeric($entry)
         ) {
         $file_array = collectEmptyFolder($dir.'/'.$entry,$file_array);
      }
   }
   if ( $counter == 2) {
      $file_array[] = str_replace('var/','',$dir);
   }
   return array_unique($file_array);
}

function collectFolder ( $dir, $file_array ) {
   $dir = str_replace('//','/',$dir);
   $directory_handle  = opendir($dir);
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ( $entry != '.'
           and $entry != '..'
           and is_dir($dir.'/'.$entry)
           and is_numeric($entry)
         ) {
         $file_array = collectFolder($dir.'/'.$entry,$file_array);
      } elseif (is_file($dir.'/'.$entry)) {
         $dir_name = basename($dir);
         if ( is_numeric($dir_name) ) {
            $entry = str_replace('var/','',$dir);
            if ( strstr($entry,'/')) {
               $file_array[] = $entry;
            }
         }
      }
   }
   return array_unique($file_array);
}

// init $success
$success = true;

// headline
$this->_flushHeadline('file: reorganize folder structure');
$this->_flushHTML(BRLF);

$disc_manager = $this->_environment->getDiscManager();
$var = $disc_manager->getFilePathBasic();
$folder_array = array();
$folder_array = collectFolder($var,$folder_array);

$folder_array2 = array();
$folder_array2 = collectEmptyFolder($var,$folder_array2);

$folder_array = array_merge($folder_array,$folder_array2);

$room2portal_array = array();
$room_array = array();
foreach ( $folder_array as $folder ) {
   $temp_array = array();
   $temp_array = explode('/',$folder);
   $room2portal_array[$temp_array[1]] = $temp_array[0];
   $room_array[] = $temp_array[1];
}

sort($room_array);
$count = count($room_array);
$this->_initProgressBar($count);
foreach ( $room_array as $room ) {
   $old_dir = $var.$room2portal_array[$room].'/'.$room.'/';
   $new_dir = $var.$room2portal_array[$room].'/'.$disc_manager->_getSecondFolder($room).'/';
   $success = $success and $disc_manager->moveFilesR($old_dir,$new_dir);
   $this->_updateProgressBar($count);
}
$this->_flushHTML(BRLF);
?>