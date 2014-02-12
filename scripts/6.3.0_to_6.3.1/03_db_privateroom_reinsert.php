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

include_once('../migration.conf.php');
include_once('../db_link.dbi_utf8.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

echo ('database: reinsert room_privat'.LINEBREAK);
$sql = 'SELECT item_id FROM portal WHERE deletion_date IS NULL AND deleter_id is NULL;';
$result = select($sql);
unset($sql);
$portal_id_array = array();
while ( $row = mysql_fetch_assoc($result) ) {
   $portal_id_array[] = $row['item_id'];
}
mysql_free_result($result);

if ( !empty($portal_id_array) ) {
   foreach ( $portal_id_array as $portal_id ) {
      $sql  = 'SELECT count(*) as count FROM user';
      $sql .= ' WHERE 1';
      $sql .= ' AND user.context_id = "'.$portal_id.'" AND user.status >= 2 AND user.deletion_date IS NULL AND user.deleter_id is NULL;';
      $result = select($sql);
      unset($sql);
      $row = mysql_fetch_assoc($result);
      $count = 0;
      $count = $row['count'];
      unset($row);
      mysql_free_result($result);

      init_progress_bar($count);

      $sql  = 'SELECT user.item_id, user.user_id, user.auth_source, user.email, user.firstname, user.lastname FROM user';
      $sql .= ' WHERE 1';
      $sql .= ' AND user.context_id = "'.$portal_id.'" AND user.status >= 2 AND user.deletion_date IS NULL AND user.deleter_id is NULL;';
      $result = select($sql);
      unset($sql);

      while ( $row = mysql_fetch_assoc($result) ) {
         $item_id = $row['item_id'];
         $user_id = $row['user_id'];
         $auth_source = $row['auth_source'];
         $firstname = $row['firstname'];
         $lastname = $row['lastname'];
         $email = $row['email'];

         if ( !empty($user_id)
              and !empty($auth_source)
            ) {

            $sql = 'SELECT room_privat.item_id FROM room_privat';
            $sql .= ' LEFT JOIN user ON user.context_id=room_privat.item_id';
            $sql .= ' WHERE 1';
            $sql .= ' AND user.user_id="'.addslashes($user_id).'" AND user.auth_source="'.$auth_source.'" AND user.status="3" AND user.deletion_date IS NULL AND user.deleter_id is NULL';
            $sql .= ' AND room_privat.deletion_date IS NULL AND room_privat.deleter_id is NULL';

            $result2 = select($sql);
            unset($sql);

            $row2 = mysql_fetch_assoc($result2);
            if ( empty($row2) ) {
               $item_id2 = (int)$item_id+1;
               $sql = 'SELECT type FROM items WHERE item_id="'.$item_id2.'";';
               $result3 = select($sql);
               unset($sql);
               $row3 = mysql_fetch_assoc($result3);
               if ( strtolower($row3['type']) == 'privateroom' ) {
                  $sql = 'UPDATE items SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id2.'";';
                  $result4 = select($sql);
                  unset($sql);
                  $sql = 'SELECT item_id FROM room_privat WHERE item_id="'.$item_id2.'";';
                  $result4 = select($sql);
                  unset($sql);
                  $row4 = mysql_fetch_assoc($result4);
                  mysql_free_result($result4);
                  if ( !empty($row4) ) {
                     $sql = 'UPDATE room_privat SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id2.'";';
                     $result4 = select($sql);
                     unset($sql);
                  } else {
                     $sql = 'INSERT INTO room_privat SET item_id="'.$item_id2.'", type="privateroom", context_id="'.$portal_id.'", title="PRIVATE_ROOM", creation_date=NOW(), modification_date=NOW(), status=1, continuous=1, creator_id="'.$item_id.'";';
                     $result4 = select($sql);
                     unset($sql);
                     mysql_free_result($result4);
                  }
                  $sql  = 'SELECT item_id, user_id FROM user WHERE context_id="'.$item_id2.'"';
                  $sql .= ' AND (email="'.$email.'" OR (firstname="'.$firstname.'" AND lastname="'.$lastname.'"))';
                  $result4 = select($sql);
                  unset($sql);
                  $row4 = mysql_fetch_assoc($result4);
                  mysql_free_result($result4);
                  if ( empty($row4) ) {
                     $item_id3 = $item_id2+1;
                     $sql = 'SELECT type FROM items WHERE item_id="'.$item_id3.'"';
                     $result5 = select($sql);
                     unset($sql);
                     $row5 = mysql_fetch_assoc($result5);
                     mysql_free_result($result5);
                     $save_user = true;
                     if ( empty($row5) ) {
                        $sql = 'INSERT INTO items SET item_id="'.$item_id3.'", type="user", context_id="'.$item_id2.'", modification_date=NOW();';
                        $result6 = select($sql);
                        unset($sql);
                     } elseif ( $row5['type'] == 'user' ) {
                        $sql = 'UPDATE items SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id3.'";';
                        $result6 = select($sql);
                        unset($sql);
                     } else {
                        echo(LINEBREAK.$user_id.': keinen user im privaten raum gefunden, statt dessen ein '.$row5['type'].LINEBREAK);
                        echo($user_id.': konnte nichts machen'.LINEBREAK);
                        $save_user = false;
                     }
                     if ( $save_user ) {
                        $sql = 'SELECT item_id FROM user WHERE item_id="'.$item_id3.'" and context_id="'.$item_id2.'";';
                        $result5 = select($sql);
                        unset($sql);
                        $row5 = mysql_fetch_assoc($result5);
                        mysql_free_result($result5);
                        if ( empty($row5) ) {
                           $sql = 'INSERT INTO user SET item_id="'.$item_id3.'", user_id="'.addslashes($user_id).'", context_id="'.$item_id2.'", auth_source="'.$auth_source.'", firstname="'.addslashes($firstname).'", lastname="'.addslashes($lastname).'", status="3", creation_date=NOW(), modification_date=NOW(), creator_id="'.$item_id.'", email="'.addslashes($email).'";';
                           $result6 = select($sql);
                           unset($sql);
                        } elseif ( $row5['type'] == 'user' ) {
                           $sql = 'UPDATE user SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id3.'";';
                           $result6 = select($sql);
                           unset($sql);
                        }
                     }
                  } else {
                     $sql = 'UPDATE user SET user_id="'.$user_id.'" WHERE item_id="'.$row4['item_id'].', deletion_date=NULL, deleter_id=NULL";';
                     $result5 = select($sql);
                     unset($sql);
                  }
               } elseif ( empty($row3) ) {
                  $sql = 'INSERT INTO items SET item_id="'.$item_id2.'", type="privateroom", context_id="'.$portal_id.'", modification_date=NOW();';
                  $result4 = select($sql);
                  unset($sql);
                  $sql = 'SELECT item_id FROM room_privat WHERE item_id="'.$item_id2.'";';
                  $result4 = select($sql);
                  unset($sql);
                  $row4 = mysql_fetch_assoc($result4);
                  mysql_free_result($result4);
                  if ( !empty($row4) ) {
                     $sql = 'UPDATE room_privat SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id2.'";';
                     $result4 = select($sql);
                     unset($sql);
                  } else {
                     $sql = 'INSERT INTO room_privat SET item_id="'.$item_id2.'", type="privateroom", context_id="'.$portal_id.'", title="PRIVATE_ROOM", creation_date=NOW(), modification_date=NOW(), status=1, continuous=1, creator_id="'.$item_id.'";';
                     $result4 = select($sql);
                     unset($sql);
                  }
                  $sql  = 'SELECT item_id, user_id FROM user WHERE context_id="'.$item_id2.'"';
                  $sql .= ' AND (email="'.$email.'" OR (firstname="'.$firstname.'" AND lastname="'.$lastname.'"))';
                  $result4 = select($sql);
                  unset($sql);
                  $row4 = mysql_fetch_assoc($result4);
                  mysql_free_result($result4);
                  if ( empty($row4) ) {
                     $item_id3 = $item_id2+1;
                     $sql = 'SELECT type FROM items WHERE item_id="'.$item_id3.'"';
                     $result5 = select($sql);
                     unset($sql);
                     $row5 = mysql_fetch_assoc($result5);
                     mysql_free_result($result5);
                     $save_user = true;
                     if ( empty($row5) ) {
                        $sql = 'INSERT INTO items SET item_id="'.$item_id3.'", type="user", context_id="'.$item_id2.'", modification_date=NOW();';
                        $result6 = select($sql);
                        unset($sql);
                     } elseif ( $row5['type'] == 'user' ) {
                        $sql = 'UPDATE items SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id3.'";';
                        $result6 = select($sql);
                        unset($sql);
                     } else {
                        echo(LINEBREAK.$user_id.': keinen user im privaten raum gefunden, statt dessen ein '.$row5['type'].LINEBREAK);
                        echo($user_id.': konnte nichts machen'.LINEBREAK);
                        $save_user = false;
                     }
                     if ( $save_user ) {
                        $sql = 'SELECT item_id FROM user WHERE item_id="'.$item_id3.'" and context_id="'.$item_id2.'";';
                        $result5 = select($sql);
                        unset($sql);
                        $row5 = mysql_fetch_assoc($result5);
                        mysql_free_result($result5);
                        if ( empty($row5) ) {
                           $sql = 'INSERT INTO user SET item_id="'.$item_id3.'", user_id="'.addslashes($user_id).'", context_id="'.$item_id2.'", auth_source="'.$auth_source.'", firstname="'.addslashes($firstname).'", lastname="'.addslashes($lastname).'", status="3", creation_date=NOW(), modification_date=NOW(), creator_id="'.$item_id.'", email="'.addslashes($email).'";';
                           $result6 = select($sql);
                           unset($sql);
                        } elseif ( $row5['type'] == 'user' ) {
                           $sql = 'UPDATE user SET deletion_date=NULL, deleter_id=NULL WHERE item_id="'.$item_id3.'";';
                           $result6 = select($sql);
                           unset($sql);
                        }
                     }
                  } else {
                     $sql = 'UPDATE user SET user_id="'.$user_id.'" WHERE item_id="'.$row4['item_id'].'", deletion_date=NULL, deleter_id=NULL;';
                     $result5 = select($sql);
                     unset($sql);
                  }
               } else {
                  echo(LINEBREAK.$user_id.': keinen privaten raum gefunden, statt dessen ein '.$row3['type'].LINEBREAK);
                  echo($user_id.': konnte nichts machen'.LINEBREAK);
               }
            }

            mysql_free_result($result2);
         }

         update_progress_bar($count);
      }
      mysql_free_result($result);
   }
} else {
   echo('nothing to do'.LINEBREAK);
}

$success = true;
echo(LINEBREAK.'done');

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>