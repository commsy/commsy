<?PHP
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

function addChat2ProjectRoom () {
   global $test;

   echo "<br />add Chat to Projectrooms<br />";

   if (!$test) {
      $query = 'SELECT * from rooms';
      $result = select($query);
      $rowcount = 0;
      $changecount = 0;
      while($row = mysql_fetch_assoc($result)) {
         $rowcount++;
         echo "<br />-- ".$row['title']."<br />";
         preg_match("/<HOMECONF>(.*)<\/HOMECONF>/", $row["extras"], $match);
         if (!empty($match[1])) {
            echo $match[1]."<br />";
            $array = explode(",", $match[1]);
            if (count($array) == 6) {
               $changecount++;
               $array[] = "chat_none";
               $homeconf = implode(",", $array);
               echo "change to:<br />";
               echo $homeconf."<br />";
               $extras = preg_replace("/(<HOMECONF>).*(<\/HOMECONF>)/", "\\1$homeconf\\2", $row['extras']);
               if (!$test) {
                  $query = 'UPDATE rooms SET extras="'.addslashes($extras).'" WHERE item_id="'.$row["item_id"].'"';
                  $success = select($query);
                  if (!$success) {
                     echo "error: ".$query." ".mysql_error();
                     break;
                  }
               }
            } else {
               echo "<b>no change</b><br />";
            }
         } else {
            echo('no project configuration for rubriks found<br />'."\n");
         }
         echo "--<br />";
      }
      if (!$result) {
         $error = mysql_error();
      }

      if (!empty($error)) {
         echo $error;
      } else {
         echo "<b>$changecount of $rowcount entries changed.</b>";
      }
   } else {
      echo "<br />testing";
   }
}

addChat2ProjectRoom();
?>