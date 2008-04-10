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

// Auf TRUE setzen, um die Einträge zu generieren
$ernstfall = TRUE;

// Verbindungsdaten
include_once('../migration.conf.php');
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

$time_start = getmicrotime();

$query = "CREATE TEMPORARY TABLE users_in_all SELECT user.item_id FROM user";
$query .= " INNER JOIN link_items ON link_items.second_item_id=user.item_id";
$query .= " INNER JOIN labels ON link_items.first_item_id=labels.item_id";
$query .= " WHERE labels.type='group' AND labels.name='ALL' AND user.room_id IS NOT NULL AND user.deletion_date IS NULL";
$query .= " AND (link_items.second_item_id=user.item_id AND link_items.first_item_id=labels.item_id)";
$queries[] = $query;
$query = "INSERT INTO users_in_all SELECT user.item_id AS user_item_id FROM user";
$query .= " INNER JOIN link_items ON link_items.first_item_id=user.item_id";
$query .= " INNER JOIN labels ON link_items.second_item_id=labels.item_id";
$query .= " WHERE labels.type='group' AND labels.name='ALL' AND user.room_id IS NOT NULL AND user.deletion_date IS NULL";
$query .= " AND (link_items.first_item_id=user.item_id AND link_items.second_item_id=labels.item_id)";
$queries[] = $query;
$query = "CREATE TEMPORARY TABLE users_not_in_all SELECT user.item_id, user.room_id, user.campus_id  FROM user";
$query .= " WHERE user.room_id IS NOT NULL AND user.deletion_date IS NULL";
$queries[] = $query;
foreach($queries as $query) {
   $result = mysql_query($query);
   if($error = mysql_error()) echo $error.". QUERY: ".$query;
}
$count_not_in_all = array_shift(mysql_fetch_row(mysql_query("SELECT COUNT(DISTINCT users_not_in_all.item_id) FROM users_not_in_all")));
$count_in_all = array_shift(mysql_fetch_row(mysql_query("SELECT COUNT(DISTINCT users_in_all.item_id) FROM users_in_all")));
echo "<br />".mysql_error();
$count = $count_not_in_all-$count_in_all;
echo "<br />".$count_in_all." User sind  in der Gruppe ALLE";
echo "<br />".$count_not_in_all." User gesamt in Projekträumen";
echo "<br />".$count." User sind nicht in der Gruppe ALLE";
if($count < 1) {
   echo "<br />Nichts zu tun. Ich verabschiede mich jetzt.";
   exit();
}
echo "<br />Frage die Datenbank nach den IDs. Das kann etwas dauern.";
flush();
$query = "SELECT users_not_in_all.item_id, users_not_in_all.room_id, users_not_in_all.campus_id FROM users_not_in_all";
$query .= " LEFT JOIN users_in_all ON users_in_all.item_id=users_not_in_all.item_id";
$query .= " WHERE users_in_all.item_id IS NULL";
$result = mysql_query($query);
echo "<br />Erstelle die fehlenden Einträge";
init_progress_bar($count);
while($row = mysql_fetch_array($result)) {
   $group_id = NULL;
   $query = "SELECT labels.item_id FROM labels";
   $query .= " WHERE labels.type='group' AND labels.name='ALL'";
   $query .= " AND labels.campus_id='".$row["campus_id"]."' AND labels.room_id='".$row["room_id"]."'";
   $re1 = mysql_query($query);
   if($re1) {
      $group = mysql_fetch_array($re1);
      if(empty($group)) {
         $problem[] = array("item_id" => $row['item_id'], "room_id" => $row['room_id'], "campus_id" => $row["campus_id"]);
      } else {
         $group_id = $group[0];
      }
   } else {
      $problem[] = array("item_id" => $row['item_id'], "room_id" => $row['room_id'], "campus_id" => $row["campus_id"]);
   }
   if($error = mysql_error()) echo $error;
   if($ernstfall and !empty($group_id)) {
         update_progress_bar($count);
        $insert_query =
        'INSERT INTO items ( item_id , room_id , campus_id , type , deleter_id , deletion_date , modification_date )
                      VALUES ("", '.$row['room_id'].' , '.$row['campus_id'].', "link_item", NULL , NULL, NULL)';
         mysql_query($insert_query);
         if($error = mysql_error()) echo $error." QUERY: ".$insert_query;
        $select_query = "SELECT MAX(items.item_id) AS IID FROM items WHERE items.type = 'link_item'";
        $item = mysql_fetch_array(mysql_query($select_query));
        $iid = $item['IID'];
        $insert_query = 'INSERT INTO link_items ( item_id , room_id , campus_id , creator_id , deleter_id ,
                                  creation_date , modification_date , deletion_date , first_item_id ,
                                  first_item_type , second_item_id , second_item_type )
                          VALUES ("'.$iid.'", "'.$row['room_id'].'","'.$row['campus_id'].'", "'.$row['item_id'].'", NULL , "'
                              .date("Y-m-d H:i:s").'", NULL, NULL , "'.$row['item_id'].'", '
                              .'"user", "'.$group_id.'", '
                              .'"group")';
        mysql_query($insert_query);
         if($error = mysql_error()) echo $error." QUERY: ".$insert_query;
   }
}
echo "<br />".count($problem)." Einträge konnten NICHT erstellt werden!";
echo "<br />Bei folgenden Einträgen gab es Probleme:";
echo '<table border="1" summary="Layout"><tr><td>user.item_id</td><td>room_id</td><td>campus_id</td></tr>'.LF;
foreach($problem as $ids) {
   echo "<tr><td>".$ids["item_id"]."</td><td>".$ids["room_id"]."</td><td>".$ids["campus_id"]."</td></tr>\n";
}
echo "</table>";
// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br />".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60));



function init_progress_bar($count) {
   echo "<br />total: ".$count;
   echo "<br />....................................................................................................|100%";
   echo "<br />";
   flush();
}

function update_progress_bar($total) {
   static $i = 0;
   static $percent = 0;
   $i++;
   $cur_percent = (int)(($i*100)/($total) );
   if($percent < $cur_percent) {
      $add = $cur_percent-$percent;
      while($add>0) {
         $add--;
          echo ".";
      }
      $percent = $cur_percent;
      flush();
   }
   if($i==$total) {
      $i = 0;
      $percent = 0;
   }
}


function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

?>