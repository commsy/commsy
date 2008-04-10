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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

echo ("This script expand the field user id from 15 chars to 32 chars in database commsy table user und database auth table auth.<br />"."\n");

// commsy database
$query  = "ALTER TABLE `user` CHANGE `user_id` `user_id` VARCHAR( 32 ) NOT NULL;";
$result = select($query);
if ( !$result ) {
   $success = false;
}

// auth database
$db_auth = mysql_connect($db['auth']['host'],$db['auth']['user'],$db['auth']['password']);
$db_link_auth = mysql_select_db($db['auth']['database'],$db_auth);

$query  = "ALTER TABLE `auth` CHANGE `user_id` `user_id` VARCHAR( 32 ) NOT NULL;";
$result = mysql_query($query);
if ( $error = mysql_error() ) {
   $success = false;
}
mysql_close($db_auth);

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>