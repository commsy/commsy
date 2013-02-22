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
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('database: insert server item in item-table.'."\n");
$success = true;

$sql = 'select count(*) as count from items where item_id="99";';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( empty($row)
     or empty($row['count'])
     or $row['count'] == 0
   ) {
   $sql  = 'insert into items set item_id="99", type="server"';
   if ( !empty($server_row[0]) ) {
      $sql .= ', modification_date="'.$server_row[0].'"';
   } else {
      $sql .= ', modification_date=now()';
   }
   $sql .= ';';
   $result = select($sql);
   echo('<br/>done');
} else {
   echo('<br/>nothing to do');
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>