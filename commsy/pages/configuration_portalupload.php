<?PHP
//
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

// get portal item and current user
$portal_item = $environment->getCurrentPortalItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

if(!$current_user->isModerator() || !$environment->inPortal()) {
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
   } else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_PORTALUPLOAD_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   
   if(isset($_POST['configuration_data_upload_room_select_confirm'])) {
      if($_POST['configuration_data_upload_room_select'] != -1) {
         $form->setRoomSelection($_POST['configuration_data_upload_room_select']);
      }
   }

   // Save item
   if ( !empty($command)
        and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {

      if ( $form->check() ) {
         // save portal item
         if(   isset($_POST['use_portal_value']) &&
               $_POST['use_portal_value'] == '1' &&
               isset($_POST['portal_value']) &&
               !empty($_POST['portal_value'])) {
            $portal_item->setMaxUploadSizeInBytes($_POST['portal_value']);
         } else {
            $portal_item->setMaxUploadSizeInBytes('');
         }
         $portal_item->save();
         
         // save room item
         if(   isset($_POST['configuration_data_upload_room_select']) &&
               !empty($_POST['configuration_data_upload_room_select']) &&
               $_POST['configuration_data_upload_room_select'] != -1) {
            $room_manager = $environment->getRoomManager();
            $room_item = $room_manager->getItem($_POST['configuration_data_upload_room_select']);
            
            if(   isset($_POST['configuration_data_upload_room_value']) &&
                  !empty($_POST['configuration_data_upload_room_value'])) {
               $room_item->setMaxUploadSizeInBytes($_POST['configuration_data_upload_room_value']);
            } else {
               $room_item->setMaxUploadSizeInBytes('');
            }
            
            $room_item->save();
         }
         
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }

   // Load form data from postvars
//   if ( !empty($_POST) and !$is_saved) {
//      $form->setFormPost($_POST);
//   }

   // Load form data from database
   elseif ( isset($room_item) ) {
      $form->setItem($room_item);
   }

   $form->prepareForm();
   $form->loadValues();


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