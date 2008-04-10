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
   } else {
      $command = '';
   }

   // Initialize the form
   include_once('classes/cs_configuration_portal_home_form.php');
   $form = new cs_configuration_portal_home_form($environment);
   include_once('classes/cs_configuration_form_view.php');
   $form_view = new cs_configuration_form_view($environment);

   // Save item
   if ( !empty($command)
        and ( isOption($command, getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {

      if ( $form->check() ) {

         if ( isset($_POST['announcement']) and !empty($_POST['announcement']) and $_POST['announcement'] == 2 ) {
            $room_item->setShowNoAnnouncementsOnHome();
         } else {
	   $room_item->setShowAnnouncementsOnHome();
	}

         if ( isset($_POST['room_sort']) and !empty($_POST['room_sort']) and $_POST['room_sort'] == 2 ) {
            $room_item->setSortRoomsByTitleOnHome();
         } else {
            $room_item->setSortRoomsByActivityOnHome();
	}
         if ( isset($_POST['preselection']) and !empty($_POST['preselection']) and $_POST['preselection'] == 3 ) {
            $room_item->setShowRoomsOnHome('onlycommunityrooms');
         } elseif ( isset($_POST['preselection']) and !empty($_POST['preselection']) and $_POST['preselection'] == 2 ) {
            $room_item->setShowRoomsOnHome('preselectcommunityrooms');
         }else {
            $room_item->setShowRoomsOnHome('normal');
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