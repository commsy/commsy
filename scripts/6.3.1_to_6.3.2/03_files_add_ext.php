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

$memory_limit2 = 640 * 1024 * 1024;
$memory_limit = ini_get('memory_limit');
if ( !empty($memory_limit) ) {
   if ( strstr($memory_limit,'M') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024 * 1024;
   } elseif ( strstr($memory_limit,'K') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024;
   }
}
if ( $memory_limit < $memory_limit2 ) {
   ini_set('memory_limit',$memory_limit2);
   $memory_limit3 = ini_get('memory_limit');
   if ( $memory_limit3 != $memory_limit2 ) {
      echo('Can not set memory limit. Please try 640M in your php.ini.');
      exit();
   }
}

include_once('../migration.conf.php');
include_once('../db_link.dbi_utf8.php');
include_once('../update_functions.php');

function collectFiles ( $dir, $file_array ) {
   $directory_handle  = opendir($dir);
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ( $entry != '.'
           and $entry != '..'
           and is_dir($dir.'/'.$entry)
           and is_numeric($entry)
         ) {
         $file_array = collectFiles($dir.'/'.$entry,$file_array);
      } elseif (is_file($dir.'/'.$entry)) {
         $dir_name = basename($dir);
         $regex = '[0-9]*';
         if ( is_numeric($dir_name)
              and is_numeric($entry)
            ) {
            $file_array[] = $dir.'/'.$entry;
         }
      }
   }
   return $file_array;
}

// time management for this script
$time_start = getmicrotime();

echo ('commsy: clean files'.LINEBREAK);
$success = true;

echo('collect files from disc');
$dir = $c_commsy_path_file.'/var';
$file_array = array();
$file_array = collectFiles($dir,$file_array);
echo(' ... [done]'.LINEBREAK);

$count = count($file_array);
init_progress_bar($count);
foreach ( $file_array as $file ) {
   $file_id = substr($file,strrpos($file,'/')+1);
   if ( !empty($file_id) ) {
      $sql = 'SELECT filename FROM files WHERE files_id="'.$file_id.'";';
      $result = select($sql);
      unset($sql);
      $row = mysql_fetch_assoc($result);
      mysql_free_result($result);
      if ( !$row ) {
         unlink($file);
      } elseif ( !empty($row['filename']) ) {
         $ext = cs_strtolower(mb_substr(strrchr($row['filename'],'.'),1));
         $filename2 = $file.'.'.$ext;
         if ( file_exists($file)
              and !file_exists($filename2)
            ) {
            rename($file,$filename2);
         }
      }
   }
   update_progress_bar($count);
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>