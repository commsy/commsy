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

if ( !isset ($_GET["id"]) && !isset ($_GET["did"])){
	exit (0);
}
$id = isset($_GET["id"]) ? $_GET["id"] : $_GET["did"];

// some settings
$plugin_folder = 'plugins';
$plugin_name = '/mediabird';
$plugin_dir = $plugin_folder.$plugin_name;


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


if(isset($id) && $id>0){

$controller = new MediabirdController($mediabirdDb, $auth);
$record = $controller->Files->checkFileAuth($id);

if($record){
	//render and read the image of that page
		if($record->type == MediabirdConstants::fileTypePdf) {
			if(!isset($_GET['page']) || !is_numeric($_GET['page'])) {
				exit (0);
			}
			$page = intval($_GET['page']);
			
			if(!$controller->Files->checkPageNum($record,$page)) {
				exit (0);
			}
			
			$controller->Files->readPdfPage($record->filename,$page,MediabirdConfig::$uploads_folder,$record->password,isset($_GET["thumb"]));
		}
		else {
			$controller->Files->readUpload($record->filename);
		}
	}
	else {
		exit(0);
	}
}
		
$mediabirdDb->disconnect();
exit();
?>
