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


if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

$context_item = $environment->getCurrentContextItem();
$is_saved = false;

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( $context_item->isProjectRoom() and !$context_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
     $command = '';
   }

   // Cancel editing
#	if ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
#	   redirect($environment->getCurrentContextID(),'configuration','dates');
#	}

   // Show form and/or save item
#    else {
       // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(CONFIGURATION_RUBRIC_EXTRAS_FORM,$class_params);
      unset($class_params);
      $class_params= array();
      $class_params['environment'] = $environment;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$class_params);
      unset($class_params);

      // Save item
      if ( !empty($command) and isOption($command, getMessage('PREFERENCES_SAVE_BUTTON') ) ) {

         $correct = $form->check();
         if ( $correct ) {

            // Terminoptionen
            if ( isset($_POST['dates_status']) ) {

               $context_item->setDatesPresentationStatus($_POST['dates_status']);
            }

            // Diskussionsoptionen
            if ( isset($_POST['discussion_status']) ) {
               $context_item->setDiscussionStatus($_POST['discussion_status']);
            }

            // Todooptionen
            if ( isset($_POST['todo_management']) ) {
               $context_item->setTodoManagmentStatus($_POST['todo_management']);
            }

            if ($environment->inProjectRoom()){
               //Gruppenoptionen
               $current_user = $environment->getCurrentUserItem();
               $context_item->setModificatorItem($current_user);
               $context_item->setModificationDate(getCurrentDateTimeInMySQL());
               if ( isset($_POST['grouproom']) and !empty($_POST['grouproom']) and $_POST['grouproom'] == 1 ) {
                   $context_item->setGrouproomActive();
               } else {
                   $context_item->setGrouproomInactive();
               }
            }

            // Save item
            $context_item->save();
            $form_view->setItemIsSaved();
            $is_saved = true;
         }
      }	// Load form data from postvars
      if ( !empty($_POST) and !$is_saved) {
         $form->setFormPost($_POST);
      }

      $form->setItem($context_item);
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
         $errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->add($errorbox);
      }

      include_once('functions/curl_functions.php');
      $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','rubric_extras',''));
      $form_view->setForm($form);
      $page->add($form_view);
#   }
}
?>