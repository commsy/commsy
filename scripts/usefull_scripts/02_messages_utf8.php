<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, JosÃ© Manuel GonzÃ¡lez VÃ¡zquez
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

echo ('commsy: convert source to utf8'."\n");

//$path = "/home/johannes/workspace/commsy";
//$dir_handle = @opendir($path) or die("Unable to open $path");
//
//encode_utf8($dir_handle,$path);
//
//function encode_utf8($dir_handle,$path){
//    while (false !== ($file = readdir($dir_handle))){
//        $dir =$path.'/'.$file;
//        if(is_dir($dir) && $file != '.' && $file !='..' ) {
//            $handle = @opendir($dir) or die("undable to open file $file");
//            encode_utf8($handle, $dir);
//        } elseif ($file != '.' && $file !='..'){
//            $extension=explode(".",$file);
//            $file_extension=$extension[(count($extension)-1)];
//            if($file_extension == 'php' or
//               $file_extension == 'txt' or
//               $file_extension == 'html' or
//               $file_extension == 'htm' or
//               $file_extension == 'php-dist' or
//               $file_extension == 'css' or
//               $file_extension == 'dat'){
//               if(!file_exists($path . '/' . '_latin1')){
//                  mkdir($path . '/' . '_latin1');
//               }
//               copy($path . '/' . $file, $path . '/' . '/_latin1/' . $file);
//               $file_contents = file_get_contents($path . '/' . $file);
//               $file_contents = iconv("ISO-8859-1", "UTF-8", $file_contents);
//               file_put_contents($path . '/' . $file, $file_contents);
//            }
//        }
//    }
//    closedir($dir_handle);
//}

$path = '/home/johannes/workspace/commsy_wiki/cookbook';
$file = 'authusercommsy.php';
copy($path . '/' . $file, $path . '/' . '_latin1/' . $file);
$file_contents = file_get_contents($path . '/' . $file);
$file_contents = iconv("ISO-8859-1", "UTF-8", $file_contents);
file_put_contents($path . '/' . $file, $file_contents);

echo(getProcessedTimeInHTML($time_start));
?>