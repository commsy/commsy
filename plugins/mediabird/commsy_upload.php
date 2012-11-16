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

if ( !isset ($_POST["action"]) || (($_POST['action']!="imageUpload" && !isset($_POST['topic'])) && $_POST['action']!="pdfUpload")){
	exit (0);
}


// some settings
$plugin_folder = 'plugins';
$plugin_name = '/mediabird';
$plugin_dir = $plugin_folder.$plugin_name;
$system = 'mediabird';


include ($plugin_dir."/config/config_default.php");
include ($plugin_dir."/config/config.php");
include ($plugin_dir."/server/utility.php");
include ($plugin_dir."/server/bootstrap.php");

//include commsy auth interface
include ($plugin_dir.'/commsy_auth.php');
$auth = new CommsyAuthManager($environment);

include_once($plugin_dir.'/server/db_mysql.php');
$mediabirdDb = new MediabirdDboMySql();

if ( !$mediabirdDb->connect() ) {
   include_once('function/error_function.php');
   trigger_error('cannot open database - aborting execution',E_USER_ERROR);
   exit();
}

$controller = new MediabirdController($mediabirdDb,$auth);

$action = $_POST['action'];


if($action=="imageUpload") {
	echo $controller->Files->handleUpload("file",MediabirdConstants::fileTypeImage);
}
else if($action=="pdfUpload") {
	echo $controller->Files->handleUpload("file",MediabirdConstants::fileTypePdf);
}
		
$mediabirdDb->disconnect();
exit();
?>
