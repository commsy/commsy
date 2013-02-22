<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
echo ('links add x and y.'.LINEBREAK);
$success = true;
$query = "SHOW COLUMNS FROM links";
$result = select($query);
$column_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   $column_array[] = $row['Field'];
}
if ( in_array('x',$column_array) ) {
   echo('nothing to do for x');
} else {
   $query = "ALTER TABLE links ADD x INT NULL;";
   $result = select($query);
   echo('x done');
}
echo(LINEBREAK);
if ( in_array('y',$column_array) ) {
   echo('nothing to do for y');
} else {
   $query = "ALTER TABLE links ADD y INT NULL;";
   $result = select($query);
   echo('y done');
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo LINEBREAK."Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)).LINEBREAK;
?>