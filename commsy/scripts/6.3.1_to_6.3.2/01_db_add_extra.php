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
include_once('../db_link.dbi_utf8.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

$table_array = array();
$table_array[] = 'section';
$table_array[] = 'announcement';
$table_array[] = 'dates';
$table_array[] = 'discussions';
$table_array[] = 'todos';
$table_array[] = 'discussionarticles';
$table_array[] = 'step';
$table_array[] = 'annotations';

echo ('add extras.'.LINEBREAK);
$success = true;
foreach ($table_array as $table) {
   echo($table.': ');
   $query = "SHOW COLUMNS FROM ".$table;
   $result = select($query);
   $column_array = array();
   while ($row = mysql_fetch_assoc($result) ) {
      $column_array[] = $row['Field'];
   }
   if ( in_array('extras',$column_array) ) {
      echo('nothing to do');
   } else {
      $query = "ALTER TABLE ".$table." ADD extras TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;";
      $result = select($query);
      echo('done');
   }
   echo(LINEBREAK);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo LINEBREAK."Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)).LINEBREAK;
?>