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
echo ('This script merge all link_file tables to a new table item_link_file.'."\n");
if (!mysql_fetch_row(select('SHOW TABLES LIKE "material_link_file" '))) {
   echo "<br/>nothing to do."."\n";
   flush();
   $success = true;
} else {
   echo ('<br/>creating table'."\n");

   $query = "RENAME TABLE material_link_file TO item_link_file;";
   $success = select($query);
   $query = "ALTER TABLE item_link_file CHANGE material_iid item_iid INT( 11 ) NOT NULL DEFAULT '0';";
   $success &= select($query);
   $query = "ALTER TABLE item_link_file CHANGE material_vid item_vid INT( 11 ) NOT NULL DEFAULT '0';";
   $success &= select($query);
}

if ( mysql_fetch_row(select('SHOW TABLES LIKE "homepage_page_link_file" ')) ) {
   $query = "INSERT INTO item_link_file SELECT * FROM homepage_page_link_file;";
   $success &= select($query);
   $query = "DROP TABLE homepage_page_link_file;";
   $success &= select($query);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>