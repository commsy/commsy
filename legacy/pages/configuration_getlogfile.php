<?PHP
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

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$current_context = $environment->getServerItem();

// Get the translator object
$translator = $environment->getTranslationObject();

if (!$current_user->isRoot() and !$current_context->mayEdit($current_user)) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->addWarning($errorbox);
} else {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   if( !empty($_GET['id'])){
	   $id = $_GET['id'];
   
	   // get log file of a room
	   $log_archive_manager = $environment->getLogArchiveManager();
	   
	   $data1 = $log_archive_manager->getLogdataByContextID($id);
	   
	   $log_manager = $environment->getLogManager();
	   
	   $data2 = $log_manager->getLogdataByContextID($id);

	   if(!empty($data1) or !empty($data2)){
		   
		   #######################
		   
		   // get export temp folder
		   global $symfonyContainer;
      	   $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
		   if (!isset($export_temp_folder)) {
		   	$export_temp_folder = "var/temp/zip_export";
		   }
		   $exportTempFolder = $export_temp_folder;
		   	
		   // create directory structure if needed
		   $directorySplit = explode("/", $exportTempFolder);
		   $doneDir = "./";
		   	
		   foreach ($directorySplit as $dir) {
		   	if (!is_dir($doneDir . "/" . $dir)) {
		   		mkdir($doneDir . "/" . $dir, 0777);
		   	}
		   
		   	$doneDir .= "/" . $dir;
		   }
		   	
		   $directory = "./" . $exportTempFolder . "/" . uniqid("", true);
		   mkdir($directory, 0777);
		   
		  ###########################################################
		   
		   
	
		   	$output = fopen($directory.'/log_room'.$id.'.csv', 'w');
		   	
		   	fputcsv($output, array('id','ip','agent','timestamp','request','post_content','method','ulogin','cid','module','fct','param','iid','queries','time'));
		   	
		   	$user = array();
		   	// Datenschutz
		   	if ( !empty($data1) ) {
			   	foreach ($data1 as $log) {
			   		$remote_adress_array = explode('.', $log['ip']);
			   		$array['remote_addr']	   = $remote_adress_array['0'].'.'.$remote_adress_array['1'].'.'.$remote_adress_array['2'].'.XXX';
			   		$userkey = '';
			   		if(array_key_exists($log['ulogin'],$user)){
			   			$userkey = $user[$log['ulogin']];
			   		} else {
			   			$uniqid = uniqid();
			   			$user[$log['ulogin']] = $uniqid;
			   			$userkey = $uniqid;
			   		}
			   		fputcsv($output, array($log['id'],$array['remote_addr'],$log['agent'],$log['timestamp'],$log['request'],$log['post_content'],
			   								$log['method'],$userkey,$log['cid'],$log['module'],$log['fct'],$log['param'],
			   								$log['iid'],$log['queries'],$log['time']));
			   	}
		   	}
		   	if ( !empty($data2) ) {
			   	foreach ($data2 as $log) {
			   		$remote_adress_array = explode('.', $log['ip']);
			   		$array['remote_addr']	   = $remote_adress_array['0'].'.'.$remote_adress_array['1'].'.'.$remote_adress_array['2'].'.XXX';
			   		$userkey = '';
			   		if(array_key_exists($log['ulogin'],$user)){
			   			$userkey = $user[$log['ulogin']];
			   		} else {
			   			$uniqid = uniqid();
			   			$user[$log['ulogin']] = $uniqid;
			   			$userkey = $uniqid;
			   		}
			   		fputcsv($output, array($log['id'],$array['remote_addr'],$log['agent'],$log['timestamp'],$log['request'],$log['post_content'],
			   		$log['method'],$userkey,$log['cid'],$log['module'],$log['fct'],$log['param'],
			   		$log['iid'],$log['queries'],$log['time']));
			   	}
		   	}
		   	fclose($output);
		   	
		   	// ZIP csv #####################################################################
		   	 
		   	$zipFile = $exportTempFolder . DIRECTORY_SEPARATOR . "log_room_" . $id . ".zip";
		   	
		   	if (file_exists(realpath($zipFile))) unlink($zipFile);
		   	
		   	if (class_exists("ZipArchive")) {
		   		include_once('functions/misc_functions.php');
		   		 
		   		$zipArchive = new ZipArchive();
		   		 
		   		if ($zipArchive->open($zipFile, ZIPARCHIVE::CREATE) !== TRUE) {
		   			include_once('functions/error_functions.php');
		   			trigger_error('can not open zip-file ' . $zipFile, E_USER_WARNING);
		   		}
		   		 
		   		$tempDir = getcwd();
		   		chdir($directory);
		   		$zipArchive = addFolderToZip(".", $zipArchive);
		   		chdir($tempDir);
		   		 
		   		$zipArchive->close();
		   	} else {
		   		include_once('functions/error_functions.php');
		   		trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNING);
		   	}
		   	
		   	// send zipfile by header
		   	 

		   	
		   	
		   	
		   	 
		   	##################################################################################
		   	
		   	header('Content-type: application/zip');
		   	header('Content-Disposition: attachment; filename=log_room'.$id.'.zip');
		   	readfile($zipFile);
		    exit;
	   } else {
	   	include_once('functions/error_functions.php');
	   	redirect('99', 'configuration', 'datasecurity');
// 	   	commSyErrorHandler();
// 	   	trigger_error("get log file: File is empty no log data available
//              <br />environment reports context id ".$environment->getCurrentContextID()."");
	   	
	   }
	   unset($log_manager);
   }  

}

?>