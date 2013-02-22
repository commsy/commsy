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
$current_context = $environment->getServerItem();

// Get the translator object
$translator = $environment->getTranslationObject();

if (!$current_user->isRoot() and !$current_context->mayEdit($current_user)) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->addWarning($errorbox);
} else {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session = $environment->getSessionItem();
      $history = $session->getValue('history');
      if ( !isset($history[1]['function']) ) {
         redirect($environment->getCurrentContextID(),'home','index',array());
      } elseif ($history[1]['function'] != $environment->getCurrentFunction()) {
         redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
      } else {
         redirect($history[2]['context'],$history[2]['module'],$history[2]['function'],$history[2]['parameter']);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_OUTOFSERVICE_FORM,array('environment' => $environment));
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $values = $_POST;
         $form->setFormPost($values);
      } else {
         $form->setItem($current_context);
      }

      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
           and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
                 or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
               )
         ) {
         if ( $form->check() ) {

            // Set modificator and modification date
            $current_context->setModificatorItem($environment->getCurrentUserItem());
            $current_context->setModificationDate(getCurrentDateTimeInMySQL());

            // outofservice
            $languages = $environment->getAvailableLanguageArray();
            $description = $current_context->getOutOfServiceArray();
            foreach ($languages as $language) {
               if (!empty($_POST['oos_'.$language])) {
                  $description[mb_strtoupper($language, 'UTF-8')] = $_POST['oos_'.$language];
               } else {
                  $description[mb_strtoupper($language, 'UTF-8')] = '';
               }
            }
            $current_context->setOutOfServiceArray($description);

            if ( !empty($_POST['oos_show']) ) {
               if ( $_POST['oos_show'] == 1 ) {
                  $current_context->setShowOutOfService();
               } else {
                  $current_context->setDontShowOutOfService();
               }
            } else {
               $current_context->setDontShowOutOfService();
            }

            // Save item
            $current_context->save();
            $form_view->setItemIsSaved();
         }
      }

      // display form
      if (isset($current_context) and !$current_context->mayEditRegular($current_user)) {
         $form_view->warnChanger();
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $params['width'] = 500;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->addWarning($errorbox);
      }

      include_once('functions/curl_functions.php');
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      $page->addForm($form_view);
   }
}
// room list on the left side
include_once('classes/cs_guide_room_list_page.php');
$guide_room_list_page = new cs_guide_room_list_page($environment,true);
$page->addRoomList($guide_room_list_page->getViewObject());
?>