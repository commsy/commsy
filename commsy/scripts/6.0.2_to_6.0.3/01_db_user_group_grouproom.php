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

echo ('db: fix links between user and groups in grouprooms'."\n");
$success = true;

// get grouproom ids
$count = array_shift(mysql_fetch_row(select('SELECT count(*) as count FROM room WHERE type="grouproom" and deletion_date IS NULL and deleter_id IS NULL;')));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);
   $sql = 'SELECT item_id, creator_id, extras FROM room WHERE type="grouproom" and deletion_date IS NULL and deleter_id IS NULL;';
   $result = select($sql);
   while ($row = mysql_fetch_assoc($result)) {
      $extras = unserialize($row['extras']);
      if ( !empty($extras['GROUP_ITEM_ID']) ) {
         $group_room_id = $row['item_id'];
         $group_room_creator_id = $row['creator_id'];
         $group_room_group_id = $extras['GROUP_ITEM_ID'];

         $sql = 'SELECT context_id FROM items WHERE item_id="'.$group_room_group_id.'";';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);
         $group_room_group_context_id = $row2['context_id'];

         $sql = 'SELECT user_id, auth_source FROM user WHERE item_id="'.$group_room_creator_id.'";';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);
         $sql = 'SELECT item_id FROM user WHERE context_id="'.$group_room_group_context_id.'" and user_id="'.$row2['user_id'].'" and auth_source="'.$row2['auth_source'].'";';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);
         $group_member_item_id = $row2['item_id'];

         $sql = 'SELECT count(*) AS count FROM link_items WHERE (first_item_id="'.$group_member_item_id.'" and second_item_id="'.$group_room_group_id.'") or (first_item_id="'.$group_room_group_id.'" and second_item_id="'.$group_member_item_id.'");';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);

         if ( empty($row2['count']) ) {
            $sql = 'INSERT INTO items SET context_id="'.$group_room_group_context_id.'", type="link_item", modification_date=now();';
            $new_item_id = insert($sql);
            $sql = 'INSERT INTO link_items SET item_id="'.$new_item_id.'", context_id="'.$group_room_group_context_id.'", creator_id="'.$group_member_item_id.'", creation_date=now(), modification_date=now(), first_item_id="'.$group_room_group_id.'", first_item_type="group", second_item_id="'.$group_member_item_id.'", second_item_type="user";';
            insert($sql);
         }
      }
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>