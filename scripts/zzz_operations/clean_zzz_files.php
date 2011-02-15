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

// chdir('..');
chdir('../..');

include_once('etc/cs_config.php');
include_once('functions/misc_functions.php');

$time_start = getmicrotime();

// disable timeout
set_time_limit(0);

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

// get a list of all files, used in zzz-rooms
$db_connector = $environment->getDBConnector();
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

foreach($result as $file) {
	// create path
	$path = './var/' . $file['portalid'] . '/' . $file['roomid'] . '_/';
	
	// extract file extension from filename
	$file_extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
	$file_path = $path . $file['fileid'] . '.' . $file_extension;
	
	// unlink file
	if(file_exists($file_path)) {
		echo "unlinking " . $file_path . "<br>\n";
		//unlink($filepath);
		
		// maybe there is some thumb-file
		if(file_exists($file_path . '_thumb')) {
			echo "unlinking " . $file_path . "_thumb<br>\n";
			//unlink($filepath . '_thumb');
		}
	}
}

?>