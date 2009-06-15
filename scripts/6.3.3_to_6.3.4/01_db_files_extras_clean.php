<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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
include_once('../db_link.dbi_utf8.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();
$success = true;

echo ('files: clean extras'.LINEBREAK);
$count = array_shift(mysql_fetch_row(select('SELECT count(files_id) AS count FROM files WHERE extras LIKE \'%\\\\\\\\"%\';')));
if ($count < 1) {
   echo "nothing to do.";
} else {
   init_progress_bar($count);
   $sql = 'SELECT * FROM files WHERE extras LIKE \'%\\\\\\\\"%\';';
   $result = select($sql);
   while ($row = mysql_fetch_assoc($result)) {
      $extra_array = mb_unserialize($row['extras']);
      if ( strlen($row['extras']) > 0
           and !is_array($extra_array)
         ) {
         $new_extra = str_replace('\\"','"',$row['extras']);
         while ( strstr($new_extra,'\\"') ) {
            $new_extra = str_replace('\\"','"',$new_extra);
         }
         if ( is_array(mb_unserialize($new_extra)) ) {
            $sql = 'UPDATE files SET extras="'.addslashes($new_extra).'" WHERE files_id="'.$row['files_id'].'";';
            $success1 = select($sql);
            $sucess = $sucess and $success1;
         }
      }
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo LINEBREAK."Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)).LINEBREAK;
?>