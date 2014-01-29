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

// move configuration of ads from cs_config to database
echo ('This script fills the auth_source field of all users at the server.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(server.item_id) FROM server WHERE server.deletion_date IS NULL;")));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);

   $query  = "SELECT server.item_id, server.extras FROM server WHERE server.deletion_date IS NULL;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $item_id = $row[0];
   $extra = $row[1];
   while ($item_id) {
      $extra_array = xml2Array($extra);
      $default_auth_source_id = $extra_array['DEFAULT_AUTH'];

      $update_query = 'UPDATE user SET auth_source="'.$default_auth_source_id.'" WHERE context_id="'.$item_id.'"';
      $success2 = select($update_query);
      $success = $success and $success2;

      $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>