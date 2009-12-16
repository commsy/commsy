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

// Get the translator object
$translator = $environment->getTranslationObject();

if (!$current_user->isModerator()) {
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
   $form = $class_factory->getClass(CONFIGURATION_TAG_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command)
        and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {

      if ( $form->check() ) {

         if ( isset($_POST['buzzword']) and !empty($_POST['buzzword']) and $_POST['buzzword'] == 'yes') {
            $room_item->setWithBuzzwords();
         } else {
           $room_item->setWithoutBuzzwords();
         }
         if ( isset($_POST['buzzword_mandatory']) and !empty($_POST['buzzword_mandatory']) and $_POST['buzzword_mandatory'] == 2 ) {
            $room_item->setBuzzwordMandatory();
         } else {
            $room_item->unsetBuzzwordMandatory();
         }
/*         if ( isset($_POST['buzzword_edit']) and !empty($_POST['buzzword_edit']) and $_POST['buzzword_edit'] == 2 ) {
            $room_item->setBuzzwordEditedByModerator();
         } else {
            $room_item->setBuzzwordEditedByAll();
         } */


         if ( isset($_POST['tags']) and !empty($_POST['tags']) and $_POST['tags'] == 'yes') {
            $room_item->setWithTags();
         } else {
            $room_item->setWithoutTags();
         }


         if ( isset($_POST['tag_mandatory']) and !empty($_POST['tag_mandatory']) and $_POST['tag_mandatory'] == 2 ) {
            $room_item->setTagMandatory();
         } else {
            $room_item->unsetTagMandatory();
         }
         if ( isset($_POST['tag_edit']) and !empty($_POST['tag_edit']) and $_POST['tag_edit'] == 2 ) {
            $room_item->setTagEditedByModerator();
         } else {
            $room_item->setTagEditedByAll();
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