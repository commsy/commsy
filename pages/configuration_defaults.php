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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
	} else {
      $params = array() ;
		$params['cid'] = $room_item->getItemId();
	   redirect($environment->getCurrentPortalId(),'home','index',$params);
	}
} elseif ( $room_item->isProjectRoom() and !$room_item->isOpen() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator()) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}
// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   }  elseif ( isset($_GET['option']) ) {
      $command = $_GET['option'];
   }else {
      $command = '';
   }


   // Initialize the form
   include_once('classes/cs_configuration_defaults_form.php');
   $form = new cs_configuration_defaults_form($environment);
   // Display form
   include_once('classes/cs_configuration_form_view.php');
   $form_view = new cs_configuration_form_view($environment);

	// Save item
   if ( !empty($command) and isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct and isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) {
	      if ( isset($_POST['context'])) {
	         $old_context = $room_item->getRoomContext();
	         if ($old_context != $_POST['context']){
	            $room_item->setRoomContext($_POST['context']);
	         }
	      }elseif ( isset($_GET['context'])) {
	         $old_context = $room_item->getRoomContext();
	         if ($old_context != $_GET['context']){
	            $room_item->setRoomContext($_GET['context']);
	         }
	      }

	      // save room_item
	      $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
         if (!isset($_GET['option'])){
            $params = $_POST;
            redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
         }
      }
   }


   // Load form data from postvars
   if ( !empty($_POST) and !$is_saved) {
      $form->setFormPost($_POST);
   } elseif ( isset($room_item) ) {
      $form->setItem($room_item);
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