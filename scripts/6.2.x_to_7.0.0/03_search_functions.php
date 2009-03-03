<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, JosÃÂ© Manuel GonzÃÂ¡lez VÃÂ¡zquez
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

echo ('commsy: get all used functions'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT count( * ) FROM files')));

$functions_array = array();
function getFunctions($dir){
   global $functions_array;
   if ($dirHandle = opendir($dir)) {
      while ($file = readdir($dirHandle)) {
         if ($file != '.' and $file != '..') {
            if(is_dir($file)){
               getFunctions($dir . '/' . $file);
            } else {
               $extension=explode(".",$file);
               if($extension[(count($extension)-1)] == 'php'){
                  $file_contents = file_get_contents($dir . '/' . $file);
                  $file_contents_array = explode("\n", $file_contents);
                  for ($index = 0; $index < sizeof($file_contents_array); $index++) {
                     if(preg_match('~\s([^\s]*)(\s)?\(|\(~u', $file_contents_array[$index], $matches)){
                        foreach($matches as $match){
                           $class_function=explode("->",$match);
                           $match = $class_function[(count($class_function)-1)];
                           if(!in_array($match, $functions_array) and !strstr($match, '(')){
                              $functions_array[] = $match;
                              //pr($match);
                           }
                        }
                     }
                  }
               }
            }
         }
      }
   }
}

$old_dir = getcwd();
chdir('../../');
getFunctions(getcwd());
sort($functions_array);
pr($functions_array);
chdir($old_dir);
// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>