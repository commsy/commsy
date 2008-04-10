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
echo ('This script creates private rooms for user in CommSy'."\n");
$success = true;

$query = 'ALTER TABLE room CHANGE type type VARCHAR( 20 ) NOT NULL DEFAULT "project"';
$success = select($query);
if ($success) {
   echo('[ <font color="#00ff00">DB change done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">DB change failed</font> ]<br/>'."\n");
}

$count_private_rooms = array_shift(mysql_fetch_row(select("SELECT COUNT(DISTINCT room.item_id) FROM room WHERE room.type ='privateroom';")));
if ($count_private_rooms >0 ){
   echo("Script already used.");
}else{
$count_user = array_shift(mysql_fetch_row(select("SELECT COUNT(DISTINCT user.user_id) FROM user WHERE user.deletion_date IS NULL;")));
if ($count_user < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_user);
   $query ='SELECT user.user_id, user.context_id, user.firstname, user.lastname, user.email FROM user INNER JOIN portal WHERE user.context_id = portal.item_id AND user.deletion_date IS NULL AND user.user_id !=""';
   $result = select($query);
   $row = mysql_fetch_row($result);
   $user_id = $row[0];
   $context_id = $row[1];
   $firstname = $row[2];
   $lastname = $row[3];
   $email = $row[4];
   while ($user_id) {
      $insert_query =  'INSERT INTO items ( item_id , context_id , type , deleter_id , deletion_date , modification_date ) VALUES ("", '.$context_id.' , "privateroom", NULL , NULL, NOW())';
      $room_iid = insert($insert_query);
      if(!$room_iid) {
         echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
      } else {
         $insert_query2 = 'INSERT INTO room ( item_id , context_id , creator_id , modifier_id , deleter_id , creation_date , modification_date , deletion_date ,title , extras, status, activity, type, public, is_open_for_guests, continuous, template)';
         $insert_query2 .= ' VALUES ('.$room_iid.', '.$context_id.',"-5", "-5", NULL , NOW(), NOW(), NULL , "PRIVATEROOM",NULL, "1","0" , "privateroom", "0","0","1","-1")';
         $success2 = select($insert_query2);
         if(!$success2) {
            echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
         } else {
            $insert_query2 =  'INSERT INTO items ( item_id , context_id , type , deleter_id , deletion_date , modification_date ) VALUES ("", '.$room_iid.' , "user", NULL , NULL, NOW())';
            $user_item_id = insert($insert_query2);
            if(!$user_item_id) {
               echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
            } else {
               $insert_query3 = 'INSERT INTO user ( item_id , context_id , creator_id , modifier_id , deleter_id , creation_date , modification_date , deletion_date ,user_id , status, is_contact, firstname, lastname, email, city, lastlogin, visible, extras)';
               $insert_query3 .= ' VALUES ('.$user_item_id.', '.$room_iid.','.$user_item_id.', '.$user_item_id.', NULL , NOW(), NOW(), NULL , "'.addslashes($user_id).'", "3", "0", "'.addslashes($firstname).'", "'.addslashes($lastname).'" , "'.addslashes($email).'","",NULL,"1",NULL)';
               $success3 = select($insert_query3);
               if(!$success3) {
                  echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
               } else {
                  $update_query =  'UPDATE room SET creator_id="'.$user_item_id.'" , modifier_id="'.$user_item_id.'" WHERE room.item_id = "'.$room_iid.'"';
                  $success4 = select($update_query);
                  if(!$success4) {
                      echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
                  }
               }
            }
         }
      }
      $row = mysql_fetch_row($result);
      $user_id = $row[0];
      $context_id = $row[1];
      $firstname = $row[2];
      $lastname = $row[3];
      $email = $row[4];
      update_progress_bar($count_user);
   }
}
}
// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>