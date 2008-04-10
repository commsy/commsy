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

// time management for this script
$time_start = getmicrotime();

// variables for this script
$days = 100;
$timestamp_limit = date('Y-m-d H:i:s',mktime(date('H'),date('m'),date('s'),date('m'),(date('d')-$days),date('Y')));

echo ('This script creates a database table "log_archive" and moves all entries older than '.$days.' Days from the database table log to this new database.<br />'."\n");

// create database log_archive
echo('<br />create database table "log_archive"    '."\n");
$query = "
  CREATE TABLE IF NOT EXISTS `log_archive` (
  `id` int(11) NOT NULL auto_increment,
  `ip` varchar(15) default NULL,
  `agent` varchar(250) default NULL,
  `timestamp` timestamp(14) NOT NULL,
  `request` varchar(250) default NULL,
  `method` varchar(10) default NULL,
  `uid` int(11) default NULL,
  `ulogin` varchar(250) default NULL,
  `cid` int(11) default NULL,
  `rid` int(11) default NULL,
  `mod` varchar(250) default NULL,
  `fct` varchar(250) default NULL,
  `param` varchar(250) default NULL,
  `iid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `cid` (`cid`),
  KEY `rid` (`rid`),
  KEY `timestamp` (`timestamp`)
) TYPE=MyISAM
";
$success = select($query);
if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br />'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br />'."\n");
}

//count entries
$count = array_shift(mysql_fetch_row(select('SELECT COUNT(log.id) FROM log WHERE log.timestamp < "'.$timestamp_limit.'"')));
if ($count > 0 and !$test) {
   init_progress_bar($count);

   // get, save and delete entries one by one
   $query = 'SELECT * FROM log WHERE log.timestamp < "'.$timestamp_limit.'" LIMIT 0,1';
   while ($row = mysql_fetch_assoc(select($query))) {
      $query2 = 'INSERT INTO log_archive SET '.
                     'ip="'.      $row['ip'].'", '.
                     'agent="'.   $row['agent'].'", '.
                     'request="'. $row['request'].'", '.
                     'method="'.  $row['method'].'", '.
                     'uid="'.     $row['uid'].'", '.
                     'ulogin="'.  $row['ulogin'].'", '.
                     'cid="'.     $row['cid'].'", '.
                     'rid="'.     $row['rid'].'", '.
                     'mod="'.     $row['mod'].'", '.
                     'fct="'.     $row['fct'].'", '.
                     'param="'.   $row['param'].'", '.
                     'iid="'.     $row['iid'].'"';
      $success2 = insert($query2);
      if ($success2) {
         $query3 = 'DELETE FROM log WHERE id="'.$row['id'].'"';
         $success3 = select($query3);
         if ($success3) {
            update_progress_bar($count);
         } else {
            $success = false;
         }
      } else {
         $success = false;
      }
   }
} else {
   echo('no entries to move<br />'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br />Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>