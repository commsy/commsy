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

function countWikis2 ($directory) {
   $directory_handle  = opendir($directory);
   $sum = 0;
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
         $sum += countWikis2($directory.'/'.$entry);
      } elseif (is_file($directory.'/'.$entry) and $entry == 'index.php') {
         $sum++;
      }
   }
   return $sum;
}

function changeWikis2 ($directory,$count) {
   $directory_handle  = opendir($directory);
   while ( false !== ($entry = readdir($directory_handle)) ) {
      if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry) and !mb_stristr($entry, 'uploads')) {
         changeWikis2($directory.'/'.$entry,$count);
      } elseif (is_file($directory.'/'.$entry)) {
         $file_contents = file_get_contents($directory.'/'.$entry);
         $file_contents = iconv('ISO-8859-1', 'UTF-8', $file_contents);
         file_put_contents($directory . '/' . $entry, $file_contents);
      }
   }
}

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('wiki: convert wikis to utf-8'."\n");
$success = true;

@include_once('../../etc/commsy/pmwiki.php');
if ( !empty($c_pmwiki_absolute_path_file) ) {
   if ( is_dir($c_pmwiki_absolute_path_file) ) {
      $num = countWikis2($c_pmwiki_absolute_path_file.'/wikis');
      init_progress_bar($num);
      changeWikis2($c_pmwiki_absolute_path_file.'/wikis',$num);
//      changeWikis2($c_pmwiki_absolute_path_file.'/wiki.d',1);
//      changeWikis2($c_pmwiki_absolute_path_file.'/wikilib.d',1);
//      changeWikis2($c_pmwiki_absolute_path_file.'/cookbook/fox/templates.d',1);
   }
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>