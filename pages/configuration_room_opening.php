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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

if (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
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
   $form = $class_factory->getClass(CONFIGURATION_ROOM_OPENING_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command)
        and ( isOption($command, getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {

      if ( $form->check() ) {

         if ( isset($_POST['community_room_opening']) and !empty($_POST['community_room_opening']) and $_POST['community_room_opening'] == 2 ) {
            $room_item->setCommunityRoomCreationStatus('moderator');
         } else {
            $room_item->setCommunityRoomCreationStatus('all');
         }

         if ( isset($_POST['project_room_link']) and !empty($_POST['project_room_link']) and $_POST['project_room_link'] == 2 ) {
            $room_item->setProjectRoomLinkStatus('mandatory');
         } else {
            $room_item->setProjectRoomLinkStatus('optional');
         }

         if ( isset($_POST['project_room_opening']) and !empty($_POST['project_room_opening']) and $_POST['project_room_opening'] == 2 ) {
            $room_item->setProjectRoomCreationStatus('communityroom');
         } else {
            $room_item->setProjectRoomCreationStatus('portal');
         }

         if ( !empty($_POST['template_select']) ) {
            $room_item->setDefaultProjectTemplateID($_POST['template_select']);
         }
         if ( !empty($_POST['template_select_community']) ) {
            $room_item->setDefaultCommunityTemplateID($_POST['template_select_community']);
         }

         if ( !empty($_POST['private_room_link']) ) {
            if ($_POST['private_room_link'] == 1) {
               $room_item->setShowAllwaysPrivateRoomLink();
            } else {
               $room_item->unsetShowAllwaysPrivateRoomLink();
            }
         }

         // Save item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;

      }
   }

   // Load form data from postvars
   if ( !empty($_POST) and !$is_saved) {
      $form->setFormPost($_POST);
   }

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