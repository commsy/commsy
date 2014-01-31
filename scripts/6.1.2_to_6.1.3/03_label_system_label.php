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

// add column "has_html" to table "files"
echo ('db: make system labels'."\n");
$success = true;

// first get all rooms not deleted
$count = array_shift(mysql_fetch_row(select("SELECT COUNT(labels.item_id) FROM labels WHERE labels.deletion_date IS NULL AND labels.deleter_id IS NULL AND labels.name='ALL' AND labels.type='group' AND labels.extras NOT LIKE '%SYSTEM_LABEL%';")));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);
   $query  = "SELECT labels.item_id,labels.extras FROM labels WHERE labels.deletion_date IS NULL AND labels.deleter_id IS NULL AND labels.name='ALL' AND labels.type='group' AND labels.extras NOT LIKE '%SYSTEM_LABEL%';";
   $result = select($query);
   if ( $error = mysql_error() ) {
      echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
   }
   while ( $row = mysql_fetch_assoc($result) ) {
      if ( !empty($row['extras']) ) {
         $extra_array = unserialize($row['extras']);
      } else {
         $extra_array = array();
      }
      $extra_array['SYSTEM_LABEL'] = '1';
      $query = 'UPDATE labels SET extras="'.addslashes(serialize($extra_array)).'" WHERE item_id="'.$row['item_id'].'";';
      select($query);
      update_progress_bar($count);
   }
}

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>