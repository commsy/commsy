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
$is_saved = false;

// get iid
if ( !empty($_GET['iid']) ) {
	$current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
	$current_iid = $_POST['iid'];
} else {
	include_once('functions/error_functions.php');trigger_error('item id lost',E_USER_ERROR);
}

// hier muss auf den aktuellen Kontext referenziert werden,
// da sonst später diese Einstellung wieder überschrieben wird
// in der commsy.php beim Speichern der Aktivität
$current_context_item = $environment->getCurrentContextItem();
if ($current_iid == $current_context_item->getItemID()) {
	$item = $current_context_item;
} else {
	if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
	   $room_manager = $environment->getRoomManager();
	} elseif ($environment->inPortal()) {
	   $room_manager = $environment->getPortalManager();
	}
	$item = $room_manager->getItem($current_iid);
}

// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
         include_once('classes/cs_errorbox_view.php');
	$errorbox = new cs_errorbox_view($environment, true);
	$errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
	$page->add($errorbox);
}

elseif (isset($item) and !$item->withChatLink()) {
         include_once('classes/cs_errorbox_view.php');
	$errorbox = new cs_errorbox_view($environment, true);
	$errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
	$page->add($errorbox);
}

// Access granted
else {

	// Find out what to do
	if ( isset($_POST['option']) ) {
		$command = $_POST['option'];
	} else {
		$command = '';
	}

	// Initialize the form
	include_once('classes/cs_configuration_chat_form.php');
	$form = new cs_configuration_chat_form($environment);
	// display form
	include_once('classes/cs_configuration_form_view.php');
	$form_view = new cs_configuration_form_view($environment);

	// Load form data from postvars
	if ( !empty($_POST) ) {
		$form->setFormPost($_POST);
	}

    // Load form data from database
	elseif ( isset($item) ) {
		$form->setItem($item);
	}

	$form->prepareForm();
	$form->loadValues();

	// Save item
	if ( !empty($command) and
		  (isOption($command, getMessage('COMMON_SAVE_BUTTON'))
			or isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) ) {

		if ( $form->check() ) {

			// Set modificator and modification date
			$current_user = $environment->getCurrentUserItem();
			$item->setModificatorItem($current_user);
			$item->setModificationDate(getCurrentDateTimeInMySQL());

			// chat link
			if ( isset($_POST['chatlink']) and !empty($_POST['chatlink']) and $_POST['chatlink'] == 1) {
				$item->setChatLinkActive();
			} else {
				$item->setChatLinkInactive();
			}

			// Save item
            $item->save();
            $form_view->setItemIsSaved();
            $is_saved = true;
		}
	}

	if (isset($item) and !$item->mayEditRegular($current_user)) {
		$form_view->warnChanger();
                 include_once('classes/cs_errorbox_view.php');
		$errorbox = new cs_errorbox_view($environment, true, 500);
		$errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
		$page->add($errorbox);
	}

	include_once('functions/curl_functions.php');
	$form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
	$form_view->setForm($form);
    if ( $environment->inPortal() or $environment->inServer() ){
       $page->addForm($form_view);
    } else {
       $page->add($form_view);
    }
}
?>