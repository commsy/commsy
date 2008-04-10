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

$db_tables = array();
$db_tables[] = 'annotations';
$db_tables[] = 'announcement';
$db_tables[] = 'chat';
$db_tables[] = 'chat_session';
$db_tables[] = 'course';
$db_tables[] = 'dates';
$db_tables[] = 'discussionarticles';
$db_tables[] = 'discussions';
$db_tables[] = 'files';
$db_tables[] = 'items';
$db_tables[] = 'labels';
$db_tables[] = 'link_items';
$db_tables[] = 'links';
$db_tables[] = 'materials';
$db_tables[] = 'ontologies';
$db_tables[] = 'portal';
$db_tables[] = 'section';
$db_tables[] = 'server';
$db_tables[] = 'room';
$db_tables[] = 'tasks';
$db_tables[] = 'todos';
$db_tables[] = 'user';

$db_tables2   = array();
$db_tables2[] = 'log';
$db_tables2[] = 'log_archive';
$db_tables2[] = 'log_ads';

echo ('This script changes all occurences of room_id to context_id and rid to cid [take a long time]'."\n");
flush();

foreach ($db_tables as $table) {
   echo('<br/>'.$table);
   flush();
   $query = 'SHOW COLUMNS FROM '.$table;
   $success = select($query);
   $field_array = array();
   while ($row = mysql_fetch_row($success) ) {
      $field_array[] = $row[0];
   }
   if (in_array('room_id',$field_array)) {

     $query = 'ALTER TABLE '.$table.' CHANGE room_id context_id INTEGER(11)';
     $success = select($query);
     if ($success) {
        echo (' done'."\n");
        echo ('<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n");
        flush();
     }
   } else {
      echo(' nothing to do'."\n");
   }
}

foreach ($db_tables2 as $table) {
   echo('<br/>'.$table);
   flush();
   $query = 'SHOW COLUMNS FROM '.$table;
   $success = select($query);
   $field_array = array();
   while ($row = mysql_fetch_row($success) ) {
      $field_array[] = $row[0];
   }
   if (in_array('rid',$field_array)) {
     $query = 'ALTER TABLE '.$table.' CHANGE rid cid INTEGER(11)';
     $success = select($query);
     if ($success) {
        echo(' done'."\n");
     }
   } else {
      echo(' nothing to do'."\n");
   }
}

if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>