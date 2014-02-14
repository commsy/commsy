<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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
set_time_limit(0);

global $c_media_integration;
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Check access rights
if ( !$current_user->isRoot()
     and !$current_user->isModerator()
   ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// check context rights
else if( !$environment->inCommunityRoom() ||
         !isset($c_media_integration) ||
         is_array($c_media_integration) && !in_array($environment->getCurrentContextID(), $c_media_integration) ||
         !is_array($c_media_integration) && $c_media_integration === false) {
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
   $form = $class_factory->getClass(CONFIGURATION_MEDIAINTEGRATION_FORM,array('environment' => $environment));

   if ( isset($_POST) and !empty($_POST) ) {
      $post_vars = $_POST;
   } else {
      $post_vars = array();
   }

   // Load form data from postvars
   if ( !empty($post_vars) ) {
      $form->setFormPost($post_vars);
   }
   $form->prepareForm();
   $form->loadValues();

   // Save item
   if ( !empty($command)
        and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
      ) {
      $correct = $form->check();
      if ( $correct ) {
         $current_context_item = $environment->getCurrentContextItem();
         
         // mdo active
         if(isset($_POST['mdo_active']) && $_POST['mdo_active'] === '1') {
           $current_context_item->setMDOActive(true);
         } else {
           $current_context_item->setMDOActive(false);
         }
         
         // mdo key
         if(isset($_POST['mdo_key']) && !empty($_POST['mdo_key'])) {
           $current_context_item->setMDOKey($_POST['mdo_key']);
         } else {
           $current_context_item->setMDOKey('');
         }
         
         $current_context_item->save();
         $is_saved = true;
      }
   }

   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ($is_saved) {
      $form_view->setItemIsSaved();
   }
   if ( $environment->inPortal() or $environment->inServer() ) {
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>