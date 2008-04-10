<?PHP
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
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
	$command = 'error';
} elseif (!$current_user->isModerator()) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
	$command = 'error';
}

if ($command != 'error') {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
	  $command = '';
   }

	// Cancel editing
	if ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
	   redirect($environment->getCurrentContextID(),'configuration','index');
	}

	// Show form and/or save item
    else {
		// Initialize the form
		include_once('classes/cs_configuration_discussion_form.php');
		$form = new cs_configuration_discussion_form($environment);
		// display form
		include_once('classes/cs_configuration_form_view.php');
		$form_view = new cs_configuration_form_view($environment);

		// Load form data from postvars
		if ( !empty($_POST) ) {
		     $form->setFormPost($_POST);
		} else {
		     $form->setItem($context_item);
		}
		$form->prepareForm();
		$form->loadValues();

		// Save item
		if ( !empty($command) and isOption($command, getMessage('PREFERENCES_SAVE_BUTTON') ) ) {

		   $correct = $form->check();
		   if ( $correct ) {

			  // Set attributes
			  if ( isset($_POST['discussion_status']) ) {
			       $context_item->setDiscussionStatus($_POST['discussion_status']);
			  }
			  // Save item
              $context_item->save();
              $form_view->setItemIsSaved();
              $is_saved = true;
			}
		}
		if (isset($context_item) and !$context_item->mayEditRegular($current_user)) {
			$form_view->warnChanger();
                         include_once('classes/cs_errorbox_view.php');
			$errorbox = new cs_errorbox_view($environment, true, 500);
			$errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
			$page->add($errorbox);
		}

		include_once('functions/curl_functions.php');
		$form_view->setAction(curl($environment->getCurrentContextID(),'configuration','discussion',''));
		$form_view->setForm($form);
		if ( $environment->inPortal() or $environment->inServer() ){
			$page->addForm($form_view);
		} else {
			$page->add($form_view);
		}
	}
}
?>