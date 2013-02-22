<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Fabian Gebert (Mediabird), Dr. Iver Jackewitz (CommSy),
//                   Frank Wolf (Mediabird)
//
// This file is part of the mediabird plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

if ( isset ($_POST["action"])) {
   $action = $_POST["action"];
   $params = $_POST;
} elseif ( isset($_GET['action']) ) {
   $action = $_GET['action'];
   $params = $_GET;
} else {
   exit (0);
}

// some settings
$plugin_folder = 'plugins';
$plugin_name = '/mediabird';
$plugin_dir = $plugin_folder.$plugin_name;
$system = 'mediabird';

// mediabird: start
include ($plugin_dir.'/server/bootstrap.php');
include ($plugin_dir.'/config/config_default.php');
include ($plugin_dir.'/config/config.php');

//include commsy auth interface
include ($plugin_dir.'/commsy_auth.php');
$auth = new CommsyAuthManager($environment);

include_once($plugin_dir.'/server/db_mysql.php');
$mediabirdDb = new MediabirdDboMySql();

//set database interface 
$auth->setDb($mediabirdDb);

if($mediabirdDb->connect()) {
		
		$controller = new MediabirdController($mediabirdDb,$auth);
		$reply = $controller->dispatch($action, $_POST);
		
		$mediabirdDb->disconnect();
		
		MediabirdUtility::jsonHeader();
		if(!empty($reply)) {
			echo json_encode($reply);
		}
}
else {
	exit;
}
flush();
exit();
?>
