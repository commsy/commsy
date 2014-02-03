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

function countWikis ($directory) {
   $directory_handle  = opendir($directory);
   $sum = 0;
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
         $sum += countWikis($directory.'/'.$entry);
      } elseif (is_file($directory.'/'.$entry) and $entry == 'index.php') {
         $sum++;
      }
   }
   return $sum;
}

function changeWikis ($directory,$count) {
   global $c_commsy_path_file;
   if ( empty($c_commsy_path_file) ) {
      @include_once('../../etc/cs_config.php');
   }
   $str2 = file_get_contents($c_commsy_path_file.'/etc/pmwiki/wiki_config.php');
   $directory_handle  = opendir($directory);
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
         changeWikis($directory.'/'.$entry,$count);
      } elseif (is_file($directory.'/'.$entry) and $entry == 'config.php') {
         $str = file_get_contents($directory.'/'.$entry);
         if ( mb_stristr($str,"include_once('commsy_config.php');") and !empty($str2) ) {
            file_put_contents($directory.'/'.$entry,trim($str2));
         }
         update_progress_bar($count);
      }
   }
}

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('wiki: exchange config.php'."\n");
$success = true;

@include_once('../../etc/commsy/pmwiki.php');
if ( !empty($c_pmwiki_absolute_path_file) ) {
   if ( is_dir($c_pmwiki_absolute_path_file) ) {
      $num = countWikis($c_pmwiki_absolute_path_file.'/wikis');
      init_progress_bar($num);
      changeWikis($c_pmwiki_absolute_path_file.'/wikis',$num);
   }
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>