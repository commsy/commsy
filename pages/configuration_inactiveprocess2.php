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
$save = '';
if(isset($_POST['lock_user'])){
	if(empty($_POST['lock_user'])){
		$empty = true;
	}
	$lock_user = $_POST['lock_user'];
	$lock_user = preg_replace('/[^0-9]+/', '', $lock_user);
	
	if(!($lock_user >= 0) and !$empty){
		$params = array();
		$params['environment'] = $environment;
		$params['with_modifying_actions'] = true;
		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
		$errorbox->setText($translator->getMessage('ERROR_VALUE_INACTIVE_LOCK'));
		$page->add($errorbox);
		$save = 'error';
	}
}
if(isset($_POST['email_before_lock'])){
	if(empty($_POST['email_before_lock'])){
		$empty = true;
	}
	$email_before_lock = $_POST['email_before_lock'];
	$email_before_lock = preg_replace('/[^0-9]+/', '', $email_before_lock);
	
	if(!($email_before_lock >= 0) and !$empty){
		$params = array();
		$params['environment'] = $environment;
		$params['with_modifying_actions'] = true;
		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
		$errorbox->setText($translator->getMessage('ERROR_VALUE_INACTIVE_LOCK_MAIL'));
		$page->add($errorbox);
		$save = 'error';
	}
}
if(isset($_POST['delete_user'])){
	if(empty($_POST['delete_user'])){
		$empty = true;
	}
	$delete_user = $_POST['delete_user'];
	$delete_user = preg_replace('/[^0-9]+/', '', $delete_user);
	
	if(!($delete_user >= 0) and !$empty){
		$params = array();
		$params['environment'] = $environment;
		$params['with_modifying_actions'] = true;
		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
		$errorbox->setText($translator->getMessage('ERROR_VALUE_INACTIVE_DELETE'));
		$page->add($errorbox);
		$save = 'error';
	}
}
if(isset($_POST['email_before_delete'])){
	if(empty($_POST['email_before_delete'])){
		$empty = true;
	}
	$email_before_delete = $_POST['email_before_delete'];
	$email_before_delete = preg_replace('/[^0-9]+/', '', $email_before_delete);
	// if(!empty 
	if(!($email_before_delete >= 0) and !$empty){
		$params = array();
		$params['environment'] = $environment;
		$params['with_modifying_actions'] = true;
		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
		$errorbox->setText($translator->getMessage('ERROR_VALUE_INACTIVE_DELETE_MAIL'));
		$page->add($errorbox);
		$save = 'error';
	}
}


if ($command != 'error') { // only if user is allowed to edit inactive

	
	$save_flag = true;
	
	$lock_days 			= $_POST['lock_user'];
	$mail_before_lock 	= $_POST['email_before_lock'];
	$delete_days 		= $_POST['delete_user'];
	$mail_before_delete = $_POST['email_before_delete'];
	
	
	$portal_item = $environment->getCurrentPortalItem();
	$user_manager = $environment->getUserManager();
	
	if(isset($lock_days) and !empty($lock_days)){
		if(isset($mail_before_lock) and !empty($mail_before_lock)){
			$date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL(($lock_days + $mail_before_lock));
		} else {
			$date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($lock_days);
		}
		
	} elseif(isset($delete_days) and !isset($lock_days) and !empty($lock_days)){
		if(isset($mail_before_delete) and !empty($mail_before_delete)){
			$date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days + $mail_before_delete);
		} else {
			$date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL($delete_days);
		}
	}
	#pr($date_lastlogin_do);
	#$date_lastlogin_do = getCurrentDateTimeMinusDaysInMySQL(($portal_item->getInactivitySendMailBeforeLockDays()));
	if(isset($date_lastlogin_do)){
		$user_array = $user_manager->getUserLastLoginLaterAs($date_lastlogin_do,$portal_item->getItemID());
	}
	
	
	if(!empty($user_array)){
		$count_delete = 0;
		$count_lock = 0;
		foreach ($user_array as $user) {
			$start_date = new DateTime(getCurrentDateTimeInMySQL());
			$since_start = $start_date->diff(new DateTime($user->getLastLogin()));
			$days = $since_start->days;
			if($days == 0){
				$days = 1;
			}
	
			if($days >= $delete_days-1){
				$count_delete++;
				continue;
			}
			if($days >= $lock_days-1){
				$count_lock++;
				continue;
			}
	
		}

	}
	
	if(($count_delete != 0 or $count_lock != 0) and $save != 'error'){
		$html = '<br>';
		if($count_delete > 0){
			$html .= $count_delete.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_DELETE');
		}
		if($count_lock > 0){
			$html .= $count_lock.$translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_LOCK');
		}
		$html .= $translator->getMessage('CONFIGURATION_INACTIVITY_ALERT_INFO');
		
		if(isset($_GET['save']) and $_GET['save']){
			$save_flag = true;
		} else {
			$save_flag = false;
		}
		
		
		$params = array();
		$params['environment'] = $environment;
		$params['with_modifying_actions'] = true;
		$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
		$errorbox->setDescription($html);
		$page->add($errorbox);
	} else {
		$save_flag = true;
	}
	
	
	// include form
	$class_params= array();
	$class_params['environment'] = $environment;
	$form = $class_factory->getClass(CONFIGURATION_INACTIVE_FORM,$class_params);
	unset($class_params);
	$form->setItem($context_item);
	// display form
	$params = array();
	$params['environment'] = $environment;
	$form_view = $class_factory->getClass(CONFIGURATION_DATASECURITY_FORM_VIEW,$params);
	unset($params);
	
	#pr($save);pr($command);pr($save_flag);
	#pr($save);
	// Save item
	if ( !empty($command) and $save != 'error' and $save_flag and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))) {
		if($context_item->isPortal()){
			if(isset($_POST['overwrite_content'])){
				$context_item->setInactivityOverwriteContent($_POST['overwrite_content']);
			}
			 
			if(isset($_POST['lock_user'])){
				$lock_user = preg_replace('/[^0-9]+/', '', $lock_user);
				$context_item->setInactivityLockDays($lock_user);
			}
			 
			if (isset($_POST['email_before_lock'])){
				$email_before_lock = preg_replace('/[^0-9]+/', '', $email_before_lock);
				$context_item->setInactivitySendMailBeforeLockDays($email_before_lock);
			}
			 
			if (isset($_POST['delete_user'])){
				$delete_user = preg_replace('/[^0-9]+/', '', $delete_user);
				$context_item->setInactivityDeleteDays($_POST['delete_user']);
			}
			 
			if (isset($_POST['email_before_delete'])){
				$email_before_delete = preg_replace('/[^0-9]+/', '', $email_before_delete);
				$context_item->setInactivitySendMailBeforeDeleteDays($email_before_delete);
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
	$array['save'] = 1;

	//$form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
	$form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),'inactiveprocess',$array));
	$form_view->setForm($form);
	if ( $environment->inPortal() or $environment->inServer() ){
		$page->addForm($form_view);
	}else{
		$page->add($form_view);
	}
}


?>