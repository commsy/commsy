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
   $directory_handle  = opendir($directory);
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
         changeWikis($directory.'/'.$entry,$count);
      } elseif (is_file($directory.'/'.$entry) and $entry == 'commsy_config.php') {
         $str = file_get_contents($directory.'/'.$entry);
         if ( mb_stristr($str,'authuser.php') and !mb_stristr($str,'authusercommsy.php') ) {
            $str = str_replace('include_once("$FarmD/scripts/authuser.php");','include_once("$FarmD/cookbook/authusercommsy.php");'.LF.'include_once("$FarmD/scripts/authuser.php");',$str);
            file_put_contents($directory.'/'.$entry,$str);
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

echo ('wiki: change commsy_config: add authusercommsy.php'."\n");
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