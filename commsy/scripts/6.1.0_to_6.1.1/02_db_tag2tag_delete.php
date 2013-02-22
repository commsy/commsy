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

echo ('db: delete tag2tag links from deleted tags'."\n");
$success = true;

// get grouproom ids
$count = array_shift(mysql_fetch_row(select('SELECT count(link_id) as count FROM `tag2tag` LEFT JOIN tag ON tag2tag.from_item_id = tag.item_id WHERE (tag2tag.deletion_date IS NULL OR tag2tag.deleter_id IS NULL) AND (tag.item_id IS NULL OR tag.deletion_date IS NOT NULL);')));
if ($count < 1) {
   echo "<br />nothing to do.";
} else {
   init_progress_bar($count);
   $sql = 'SELECT tag2tag.link_id FROM `tag2tag` LEFT JOIN tag ON tag2tag.from_item_id = tag.item_id WHERE (tag2tag.deletion_date IS NULL OR tag2tag.deleter_id IS NULL) AND (tag.item_id IS NULL OR tag.deletion_date IS NOT NULL);';
   $result = select($sql);
   while ($row = mysql_fetch_assoc($result)) {
      $sql = 'UPDATE tag2tag SET deletion_date=NOW(), deleter_id=99 WHERE link_id="'.$row['link_id'].'";';
      select($sql);
      update_progress_bar($count);
   }
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>