<?PHP
// $Id $
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

// Get the translator object
$translator = $environment->getTranslationObject();

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;
// Check access rights
if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$room_item->isOpen() and !$room_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}
// Access granted
else {
   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   }  else {
      $command = '';
   }
   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $is_saved = true;
   }
   // Show form and/or save item
   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_INFORMATIONBOX_FORM,array('environment' => $environment));

   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Save item
      $room_item = $environment->getCurrentContextItem();

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }

   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct ) {
         $info_array = array();
         if (is_array($room_item->_getExtra('INFORMATIONBOX'))) {
            $info_array = $room_item->_getExtra('INFORMATIONBOX');
         }
         if (!empty($_POST['item_id'])) {
            $room_item->setInformationBoxEntryID($_POST['item_id']);
         }

         if ($_POST['show_information_box']== '1') {
            $room_item->setwithInformationBox('yes');
         }else{
            $room_item->setwithInformationBox('no');
         }

         // Save item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }


   $form->setItem($room_item);
   $form->prepareForm();
   $form->loadValues();
   if (isset($context_item) and !$context_item->mayEditRegular($current_user)) {
       $form_view->warnChanger();
       $params = array();
       $params['environment'] = $environment;
       $params['with_modifying_actions'] = true;
       $params['width'] = 500;
       $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
       unset($params);
       $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
       $page->add($errorbox);
    }
    include_once('functions/curl_functions.php');
    $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','informationbox',''));
    $form_view->setForm($form);
    $page->add($form_view);
}

?>
