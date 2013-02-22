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

// for parsing the PHP syntax check output
define('OK_LINE_START','No syntax errors detected in');
define('ON_LINE_PATTERN','~on line <b>([0-9]*)<\\/b>~u');
define('PHP_CMD',"C:\\xampp\php\php-cgi.exe"); // path to command line version of PHP on this system
#define('PHP_CMD',"/usr/bin/php"); // path to command line version of PHP on this system

function check_syntax ($file) {
   global $c_commsy_path_file;
   $retour = array();
   $output = '';
   $result = '';

   $file_to_check = $c_commsy_path_file.'/'.$file;

   $cmd = PHP_CMD . " -l ".$file_to_check;

   exec($cmd,$output,$result);
   $len = count($output);
   if ( $len <= 0 ) {
      echo "Sorry! internal error, no syntax check output :-(";
      exit(1);
   }

   // finally parse output of syntax check

   if ( mb_substr($output[0],0,mb_strlen(OK_LINE_START)) == OK_LINE_START ) {
       $syntax_OK = true;
       flush();
    } else {
       $error_array = array();
       $syntax_OK = false;
       $filtered_output = array();
       if ( $len > 0 && rtrim($output[0]) == "<br />" )  {
          array_shift($output);
          $len--;
       }
       if ( $len > 0 && rtrim($output[$len-1]) == "Errors parsing " . $file )  {
           $len--;   // N.B. skip last line
       }
       for ( $i=0; $i < $len; $i++ ) {
           $line = $output[$i];
           $filtered_output[] = $line;
           if ( preg_match(ON_LINE_PATTERN, $line, $matches) ) {
              $error_array[] = $line;
           }
       }
       $retour = $error_array;
   }
   global $count_files;
   update_progress_bar($count_files);
   return $retour;
}

function runFilesInDir ($directory) {
   $directory_handle  = opendir($directory);
   $error = array();

   while (false !== ($entry = readdir($directory_handle))) {
      if ( $entry != '.'
           and $entry != '..'
           and !mb_stristr($entry,'CVSROOT')
           and !mb_stristr($entry,'lib')
           and !mb_stristr($entry,'TestSource')
           and !mb_stristr($entry,'var')
           and !mb_stristr($entry,'CVS')
           and is_dir($directory.'/'.$entry)
         ) {
         $error = array_merge($error,runFilesInDir($directory.'/'.$entry));
      } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
         $error = array_merge($error,check_syntax($directory.'/'.$entry));
      }
   }
   return $error;
}

function countFilesInDir ($directory) {
   $directory_handle  = opendir($directory);
   $count = 0;

   while (false !== ($entry = readdir($directory_handle))) {
      if ( $entry != '.'
           and $entry != '..'
           and !stristr($entry,'CVSROOT')
           and !stristr($entry,'lib')
           and !stristr($entry,'TestSource')
           and !stristr($entry,'var')
           and !stristr($entry,'CVS')
           and is_dir($directory.'/'.$entry)
         ) {
         $count = $count + countFilesInDir($directory.'/'.$entry);
      } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~',$entry)) {
         $count++;
      }
   }
   return $count;
}


chdir('..');

include_once('../update_functions.php');

chdir('../..');

include_once('etc/cs_config.php');

echo('this script checks the php syntax<hr/>');
flush();

echo('count files: ');
flush();

$count_files = countFilesInDir('.');
echo($count_files);
echo('<br/>');
flush();

init_progress_bar($count_files);

$result_array = runFilesInDir('.');
if ( empty($result_array) ) {
   echo('<br/>'.'keinen Fehler gefunden');
} else {
   echo('<br/>'.'Fehler gefunden:');
   foreach ($result_array as $error) {
      echo('<br/>'.$error);
   }
}
?>