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
### This script needs some GET-Parameters to work
###
### 1. To collect a list of all files you must specify some
### MySQL information. Expecting that this script uses
### the same login like CommSy, only the database can differ
### for expample when collection information from a backup
### database:
### $_GET['database'] = 'commsy' or a backup database
###
###
### 2. It is recommended to use this script in simulation
### mode first, this is the standard option. If you want
### to delete the collected files set $_GET['delete'] to
### true
###
###
### 3. This script deletes the collected files but does also
### scan for filepath_thumb to get the thumb images too.
###
###
### 4. For deleting files the php function unlink is used.
### Unlinking is not the same as a remove command on the
### shell, see php.net for more information
###
###
### 5. Please ensure correct rights for the apache owner.
### Locally tested, seems not to work under windows???
#############################################################

chdir('../..');

include_once('etc/cs_config.php');
include_once('functions/misc_functions.php');
include_once('classes/db_mysql_connector.php');

if(!isset($_GET['database']) || empty($_GET['database'])) {
	die("You must specify a database to work on: \$_GET['database']");
}

$delete = false;
if(isset($_GET['delete']) && $_GET['delete'] === 'true') {
	$delete = true;
}

// disable timeout
set_time_limit(0);

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

// get a list of all files, used in zzz-rooms
global $db;
$db_connector = new db_mysql_connector(array(	"host"		=>	$db['normal']['host'],
												"user"		=>	$db['normal']['user'],
												"password"	=>	$db['normal']['password'],
												"database"	=>	$_GET['database']));
$qry = '
	SELECT
		zzz_files.files_id as fileid,
		zzz_files.filename as filename,
		zzz_files.context_id as roomid,
		zzz_room.context_id as portalid
	FROM
		zzz_files
	LEFT JOIN
		zzz_room
	ON
		zzz_files.context_id = zzz_room.item_id
';
$result = $db_connector->performQuery($qry);

if(sizeof($result) > 0) {
	foreach($result as $file) {
		// create path
		$path = './var/' . $file['portalid'] . '/' . $file['roomid'] . '_/';
		
		// extract file extension from filename
		$file_extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
		$file_path = $path . $file['fileid'] . '.' . $file_extension;
		
		// unlink file
		if(file_exists($file_path)) {
			echo "unlinking " . $file_path;
			if($delete) {
				unlink($filepath);
				echo ' ...done';
			}
			echo "<br>\n";
			
			// maybe there is some thumb-file
			if(file_exists($file_path . '_thumb')) {
				echo "unlinking " . $file_path . "_thumb";
				if($delete) {
					unlink($filepath . '_thumb');
					echo ' ...done';
				}
				echo "<br>\n";
			}
		}
	}
} else {
	echo "no entries found";
}

?>