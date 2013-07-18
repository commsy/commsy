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
	   
	   if(!empty($data1) and !empty($data2)){

		   $data = array_merge($data1,$data2);
		   
		   if(!empty($data)){
		   	header('Content-Type: text/csv; charset=utf-8');
		   	header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		   	header('Content-Disposition: attachment; filename=log_room'.$id.'.csv');
		   	header('Pragma: no-cache');
	
		   	$output = fopen('php://output', 'w');
		   	
		   	fputcsv($output, array('id','ip','agent','timestamp','request','post_content','method','ulogin','cid','module','fct','param','iid','queries','time'));
		   	
		   	$user = array();
		   	// Datenschutz
		   	foreach ($data as $log) {
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
		    exit;
		   }
	   } else {
	   	include_once('functions/error_functions.php');
	   	commSyErrorHandler();
// 	   	trigger_error("get log file: File is empty no log data available
//             <br />environment reports context id ".$environment->getCurrentContextID()."");
	   	
	   }
	   unset($log_manager);
   }  

}

?>