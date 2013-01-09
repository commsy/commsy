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

// Get the translator object
$translator = $environment->getTranslationObject();

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;
if ( !empty($_GET['reload']) ) {
   $is_saved = true;
}

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
   $form = $class_factory->getClass(CONFIGURATION_ARCHIVE_FORM,array('environment' => $environment));
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
         // status
         if ( isset($_POST['status']) ) {
            if ($_POST['status'] == '') {
               $room_item->open();
               $room_item->setNotTemplate();
            } elseif ($_POST['status'] == 2 and !$room_item->isTemplate()) {
               $room_item->moveToArchive();
            }
         }else{
            $room_item->open();
            if ($room_item->isProjectRoom()  or $room_item->isCommunityRoom() ) {
               $room_item->setNotTemplate();
            }
         }
         // template
         if ( isset($_POST['template'])
            and !empty($_POST['template'])
         ) {
            if ( $_POST['template'] == 1 and $room_item->isClosed() ) {
               $room_item->setTemplate();
            } else {
               $room_item->setNotTemplate();
            }
         } elseif ($room_item->isProjectRoom()  or $room_item->isCommunityRoom() ) {
            $room_item->setNotTemplate();
         }
         if ( isset($_POST['template_availability'])){
            if ( $room_item->isCommunityRoom() ){
               $room_item->setCommunityTemplateAvailability($_POST['template_availability']);
            }else{
               $room_item->setTemplateAvailability($_POST['template_availability']);
            }
         }
         if ( isset($_POST['description'])){
            $room_item->setTemplateDescription($_POST['description']);
         }
         // Save item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;

      }
      if ( !isset($_GET['option']) ) {
         $params = array();
         $params['option'] = $_POST['option'];
         $params['reload'] = 'yes';
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
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

   if ($is_saved){
      $form_view->setItemIsSaved();
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