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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ($current_user->isGuest()) {
   redirect($room_item->getItemID(),'home','index','');
} elseif ( $room_item->isPortal() and !$room_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif ( ($room_item->isPortal() and !$current_user->isModerator())
           or ($room_item->isServer() and !$current_user->isRoot())
         ) {
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
   $form = $class_factory->getClass(CONFIGURATION_IMS_FORM,array('environment' => $environment));
   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   } else {
      $form->setItem($room_item);
   }
   $form->prepareForm();
   $form->loadValues();

   if ( !empty($command) and ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) ) {
      if ( $form->check() ) {
         $auth_object = $environment->getAuthenticationObject();
         $auth_manager = $auth_object->getCommSyAuthManager();
         if (!empty($_POST['exist'])) {
            if ($auth_manager->exists($_POST['user_id'])) {
               //set new PW
               $auth_manager->changePassword($_POST['user_id'],$_POST['password1']);
            } else {
               //create user
               $auth_item = $auth_manager->getNewItem();
               $auth_item->setPortalID(99);
               $auth_item->setUserId($_POST['user_id']);
               $auth_item->setPassword($_POST['password1']);
               $auth_item->setAuthSourceID($auth_manager->getAuthSourceItemID());
               $auth_object->save($auth_item,false);
               $user = $auth_object->getUserItem();
               $user->makeUser();
               $user->save();
            }
         } else {
            if ($auth_manager->exists($_POST['user_id'])) {
               //delete ims user
               $current_context = $environment->getCurrentContextItem();
               $auth_object->deleteByUserId($_POST['user_id'],$current_context->getAuthDefault());
            }
         }
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }

   // upload ims paket
   elseif ( !empty($command) and ( isOption($command, $translator->getMessage('COMMON_UPLOADFILE_BUTTON')) ) ) {
      if ( !empty($_FILES['upload']['tmp_name']) ) {
         $ims = file_get_contents($_FILES['upload']['tmp_name']);
         include_once('classes/cs_connection_soap_ims.php');
         $ims_connect = new cs_connection_soap_ims($environment);
         $session_item = $environment->getSessionItem();
         pr_xml($ims_connect->ims($session->getSessionID(),$ims));
      }
   }
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>