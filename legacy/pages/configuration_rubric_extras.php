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

$change_id = 0;
$delete_id = 0;
foreach ($_POST as $key => $post_var){
   $iid = mb_substr(strchr($key,'#'),1);
   if (!empty($iid) and mb_stristr($key,'option') ) {
      if ( isOption($post_var, $translator->getMessage('COMMON_DELETE_BUTTON')) ){
         $delete_id = $iid;
      } elseif ( isOption($post_var, $translator->getMessage('CONFIGURATION_TODO_STATUS_CHANGE_BUTTON')) ) {
         $change_id = $iid;
      }
   }
}


$context_item = $environment->getCurrentContextItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$context_item->isOpen() and !$context_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
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
#	if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
#	   redirect($environment->getCurrentContextID(),'configuration','dates');
#	}

   // Show form and/or save item
#    else {
       // Initialize the form
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
      if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON') ) ) {

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
			
			// Bewertungsfunktion
			$current_user = $environment->getCurrentUserItem();
			$context_item->setModificatorItem($current_user);
			$context_item->setModificationDate(getCurrentDateTimeInMySQL());
			if(isset($_POST['assessment']) && !empty($_POST['assessment']) && $_POST['assessment'] == 1) {
				$context_item->setAssessmentActive();
			} else {
				$context_item->setAssessmentInactive();
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

      if ( !empty($delete_id) or !empty($change_id) ){
        if (!empty ($_POST)){
             foreach ($_POST as $key => $post_var){
               $iid = mb_substr(strchr($key,'#'),1);
               if (!empty($iid) and mb_stristr($key,'status') and $iid == $change_id) {
                  $context_item = $environment->getCurrentContextItem();
                  $status_array = $context_item->getExtraToDoStatusArray();
                  $status_array[$iid] = $post_var;
                  $context_item->setExtraToDoStatusArray($status_array);
                  $context_item->save();
               } elseif(!empty($iid) and $iid == $delete_id) {
                  $context_item = $environment->getCurrentContextItem();
                  $status_array = $context_item->getExtraToDoStatusArray();
                  unset($status_array[$iid]);
                  $context_item->setExtraToDoStatusArray($status_array);
                  $context_item->save();
               }
            }
         }

         $params = array();
         if (empty($delete_id)) {
           $params['focus_element_onload'] = $change_id;
         }
         redirect($environment->getCurrentContextID(),'configuration', 'rubric_extras', $params);
      }elseif (!empty($command) and isOption($command, $translator->getMessage('CONFIGURATION_TODO_NEW_STATUS_BUTTON'))){
          if (isset($_POST['new_status']) and !empty($_POST['new_status'])){
             $context_item = $environment->getCurrentContextItem();
             $status_array = $context_item->getExtraToDoStatusArray();
             $status_number = 5;
             foreach ($status_array as $key => $value){
                if ($key >= $status_number){
                  $status_number = $key+1;
                }
             }
             $status_array[$status_number] = $_POST['new_status'];
             $context_item->setExtraToDoStatusArray($status_array);
             $context_item->save();
             $params = array();
             $params['focus_element_onload'] = 'new_status';
             redirect($environment->getCurrentContextID(),
                'configuration', 'rubric_extras', $params);
          }
       }

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
      $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','rubric_extras',''));
      $form_view->setForm($form);
      $page->add($form_view);
#   }
}
?>