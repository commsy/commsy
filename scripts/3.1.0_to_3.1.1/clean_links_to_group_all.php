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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

// init database connection
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This script re-insert connections: user 2 group ALL in projectrooms.");

// rename "Alle Mitglieder" and "All members" in "ALL"
$query = 'UPDATE labels SET name="ALL" WHERE name="Alle Mitglieder" or name="All members"';
if ($do_it) {
   $result = mysql_query($query);
   if ($error = mysql_error() ) {
      echo $error.". QUERY: ".$query;
      $success = false;
   }
} else {
   echo ('<br /><br />QUERY: '.$query);
}

// re-insert links user - group ALL
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
$query = "CREATE TEMPORARY TABLE users_not_in_all SELECT user.item_id, user.room_id, user.campus_id, user.modification_date FROM user";
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
echo "<br />".$count_in_all." user in group all";
echo "<br />".$count_not_in_all." user in project rooms";
echo "<br />".$count." user not in group all";
if($count < 1) {
   echo "<br />nothing to do.";
} else {
   echo "<br />get IDs from database. This take a while ...";
   flush();
   $query = "SELECT users_not_in_all.item_id, users_not_in_all.room_id, users_not_in_all.campus_id, users_not_in_all.modification_date FROM users_not_in_all";
   $query .= " LEFT JOIN users_in_all ON users_in_all.item_id=users_not_in_all.item_id";
   $query .= " WHERE users_in_all.item_id IS NULL";
   $result = mysql_query($query);
   echo "<br />Insert missing entries.";
   init_progress_bar($count);
   while($row = mysql_fetch_array($result)) {
      $group_id = NULL;
      $query = "SELECT labels.item_id FROM labels";
      $query .= " WHERE labels.type='group' AND labels.name='ALL'";
      $query .= " AND labels.campus_id='".$row["campus_id"]."' AND labels.room_id='".$row["room_id"]."'";
      $re1 = mysql_query($query);
      if ($re1) {
         $group = mysql_fetch_array($re1);
         if (empty($group)) {
            $problem[] = array("item_id" => $row['item_id'], "room_id" => $row['room_id'], "campus_id" => $row["campus_id"], "modification_date" => $row['modification_date']);
         } else {
            $group_id = $group[0];
         }
      } else {
         $problem[] = array("item_id" => $row['item_id'], "room_id" => $row['room_id'], "campus_id" => $row["campus_id"], "modification_date" => $row['modification_date']);
      }
      if ($error = mysql_error()) {
         echo $error;
         $success = false;
      }
      if ($do_it and !empty($group_id)) {
         $insert_query = 'INSERT INTO items ( item_id , room_id , campus_id , type , deleter_id , deletion_date , modification_date )
                          VALUES ("", '.$row['room_id'].' , '.$row['campus_id'].', "link_item", NULL , NULL, NULL)';
         mysql_query($insert_query);
         if ($error = mysql_error()) {
            echo $error." QUERY: ".$insert_query;
            $success = false;
         }
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
         if ($error = mysql_error()) {
            echo $error." QUERY: ".$insert_query;
            $success = false;
         }
      }
      update_progress_bar($count);
   }
   echo "<br />".count($problem)." entries could not insert!";
   echo "<br />At following entries occurred failiures:";
   echo '<table border="1" summary="Layout"><tr><td>user.item_id</td><td>room_id</td><td>campus_id</td><td>modification_date</td></tr>\n';
   foreach($problem as $ids) {
      echo "<tr><td>".$ids["item_id"]."</td><td>".$ids["room_id"]."</td><td>".$ids["campus_id"]."</td><td>".$ids['modification_date']."</td></tr>\n";
   }
   echo "</table>";
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>