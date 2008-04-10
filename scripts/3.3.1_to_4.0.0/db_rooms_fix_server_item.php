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

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('This script sets the server item to item id 99.'."\n");
$query = "SELECT * FROM items WHERE item_id='100';";
$result = select($query);
if ($result) {
   $row = mysql_fetch_assoc($result);
   if ( !empty($row) and $row['type'] == 'server') {
      $query = "UPDATE items SET type='campus' WHERE item_id='100';";
      $result = select($query);
   }
}

$query = "SELECT * FROM items WHERE item_id='99';";
$result = select($query);
if ($result) {
   $row = mysql_fetch_assoc($result);
   if ( !empty($row) and $row['type'] != 'server') {
      $query = "UPDATE items SET type='server' WHERE item_id='99';";
      $result = select($query);
   } elseif ( empty($row) ) {
      $query = "INSERT INTO items SET type='server', item_id='99';";
      $result = select($query);
   }
}

$query = "UPDATE rooms SET item_id='99' WHERE item_id='100';";
$success = select($query);

if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>