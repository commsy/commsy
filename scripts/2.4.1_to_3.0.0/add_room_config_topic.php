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

function addTopic2ProjectRoom () {
   global $test, $success, $error;

   $query = 'SELECT * from rooms';
   $result = select($query);
   $number = mysql_num_rows($result);
   echo "<br />adding topics to room configuration";
   init_progress_bar($number);
   while($row = mysql_fetch_assoc($result)) {
      update_progress_bar($number);
      preg_match("/<HOMECONF>(.*)<\/HOMECONF>/", $row["extras"], $match);
      if (!empty($match[1])) {
         $array = explode(",", $match[1]);
         if (count($array) == 7) {
            $array[] = "topics_none";
            $homeconf = implode(",", $array);
            $extras = preg_replace("/(<HOMECONF>).*(<\/HOMECONF>)/", "\\1$homeconf\\2", $row['extras']);
            $query = 'UPDATE rooms SET extras="'.addslashes($extras).'" WHERE item_id="'.$row["item_id"].'"';
            if (!$test) {
               $success = select($query);
              if(!$success) {
                  echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
                  return;
               }
            }
         }
      }
   }
   if (!$result) {
      echo "<br /><b>mysql complains:</b> ".$error;
   }
   if (empty($error)) {
      $success = TRUE;
   }
}

addTopic2ProjectRoom();
?>