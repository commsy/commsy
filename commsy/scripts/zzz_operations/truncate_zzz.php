<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

#############################################################
### Usage
### ---------------------
### Truncates all zzz tables in CommSy Database
###
### 1. It is recommended to use this script in simulation
### mode first, this is the standard option. If you want
### to delete the collected files set $_GET['work'] to
### true
#############################################################

chdir('../..');

include_once('etc/cs_config.php');
include_once('functions/misc_functions.php');

$work = false;
if(isset($_GET['work']) && $_GET['work'] === 'true') {
	$work = true;
}

// disable timeout
set_time_limit(0);

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

// get a list of all files, used in zzz-rooms
$db_connector = $environment->getDBConnector();
$qry = '
	SHOW TABLES
	FROM
		commsy
	LIKE
		\'zzz\_%\'
';
$result = $db_connector->performQuery($qry);

foreach($result as $table) {
	$table_name = array_values($table);
	$table_name = $table_name[0];
	
	echo 'truncate table ' . $table_name;
	if($work) {
		$db_connector->performQuery('TRUNCATE TABLE ' . $table_name);
		echo ' ...done';
	}
	echo "<br>\n";
}

?>