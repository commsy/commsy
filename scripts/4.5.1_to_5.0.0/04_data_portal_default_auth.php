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
echo ('This script defines a default auth source for each portal'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select("SELECT COUNT(portal.item_id) FROM portal WHERE portal.deletion_date IS NULL AND portal.extras NOT LIKE '%<DEFAULT_AUTH>%';")));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);

   $query  = "SELECT portal.item_id, portal.extras, portal.creator_id FROM portal WHERE portal.deletion_date IS NULL AND portal.extras NOT LIKE '%<DEFAULT_AUTH>%';";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $item_id = $row[0];
   $extra = $row[1];
   $creator_id = $row[2];
   while ($item_id) {

      $insert_auth_source_item = 'INSERT INTO items SET '.
                                 'context_id="'.$item_id.'",'.
                                 'modification_date= NOW(),'.
                                 'type="auth_source"';
      $new_id = insert($insert_auth_source_item);

      $extra_string = '<COMMSY_DEFAULT>1</COMMSY_DEFAULT>
	              <SOURCE>MYSQL</SOURCE>
	              <CONFIGURATION>
	                 <ADD_ACCOUNT>1</ADD_ACCOUNT>
	                 <CHANGE_USERID>1</CHANGE_USERID>
	                 <DELETE_ACCOUNT>1</DELETE_ACCOUNT>
	                 <CHANGE_USERDATA>1</CHANGE_USERDATA>
	                 <CHANGE_PASSWORD>1</CHANGE_PASSWORD>
	              </CONFIGURATION>
	              <SHOW>1</SHOW>';

      $insert_auth_source_item2 = 'INSERT INTO auth_source SET '.
                                  'item_id="'.$new_id.'",'.
                                  'context_id="'.$item_id.'",'.
                                  'creator_id="'.$creator_id.'",'.
                                  'creation_date=NOW(),'.
                                  'modifier_id="'.$creator_id.'",'.
                                  'modification_date=NOW(),'.
                                  'title="CommSy",'.
                                  'extras="'.addslashes($extra_string).'"';
      insert($insert_auth_source_item2);

      $extra .= '<DEFAULT_AUTH>'.$new_id.'</DEFAULT_AUTH>';

      $update_portal_item = 'UPDATE portal SET extras="'.addslashes($extra).'" WHERE portal.item_id="'.$item_id.'";';
      select($update_portal_item);

      $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $extra = $row[1];
      $creator_id = $row[2];
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>