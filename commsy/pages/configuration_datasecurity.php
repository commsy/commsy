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
include_once('functions/curl_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
$is_saved = false;

$context_item = $environment->getCurrentContextItem();

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( $context_item->isProjectRoom() and !$context_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit datasecurity

   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(CONFIGURATION_DATASECURITY_FORM,$class_params);
   unset($class_params);
   $form->setItem($context_item);
   // display form
   $params = array();
   $params['environment'] = $environment;
   $form_view = $class_factory->getClass(CONFIGURATION_DATASECURITY_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))) {
      
   	if($context_item->isServer()){
	   	  if(!empty($_POST['log_delete_interval'])){
	   	  	$log_delete_interval = $_POST['log_delete_interval'];
	   	  	
	   	  } else {
	   	  	$log_delete_interval = 1;
	   	  }
	   	  
	      $context_item->setLogDeleteInterval($log_delete_interval);
	
	      if(isset($_POST['portal']) AND $_POST['portal'] != -1){
	      	$portal_manager = $environment->getPortalManager();
	      	$portal = $portal_manager->getItem($_POST['portal']);
	      	
	      	$logarchive_manager = $environment->getLogArchiveManager();
	      	
	      	$room_list = $portal->getRoomList();
	      	 
	      	if ( !$room_list->isEmpty() ) {
	      		$room = $room_list->getFirst();
	      		while ($room) {
	      			if(isset($_POST['ROOM_'.$room->getItemID()]) AND !empty($_POST['ROOM_'.$room->getItemID()])){
	      				if($_POST['ROOM_'.$room->getItemID()] == $room->getItemID()){
	      					// delete log data from rooms
	      					// wenn keine Daten vorhanden, dann nicht die Daten holen. mysql darf keinen Fehler werfen
	      					#$logarchive_manager->setContextLimit($room->getItemID());
	      					pr($logarchive_manager->getLogdataByContextID($room->getItemID()));
	      					#$logarchive_manager->deleteByContextID($room->getItemID());
	      				}
	      			}
	      			$room = $room_list->getNext();
	      		}
	      	}
	      }
	      unset($portal_manager);
	      unset($logarchive_manager);
	      
      } else if($context_item->isPortal()){
      	if(!empty($_POST['hide_accountname'])){
      		$hide_accountname = $_POST['hide_accountname'];
      	} else {
      		$hide_accountname = 1;
      	}
      	
      	if($hide_accountname == 2){
      		$context_item->unsetHideAccountname();
      	} else if($hide_accountname == 1){
      		$context_item->setHideAccountname();
      	}

      }

      // save room_item
      $context_item->save();

      $form_view->setItemIsSaved();
      $is_saved = true;
      if ( !empty($_POST)) {
         $form->setFormPost($_POST);
      } elseif ( isset($context_item) ) {
         $form->setItem($context_item);
      }
   } else{
      // init form, create form and loadValues
      // Load form data from postvars
      if ( !empty($_POST)) {
         $form->setFormPost($_POST);
      } elseif ( isset($context_item) ) {
         $form->setItem($context_item);
      }
   }
   $form->prepareForm();
   $form->loadValues();

   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   }else{
      $page->add($form_view);
   }
}
?>