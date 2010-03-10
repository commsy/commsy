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

// Linked item from "NEW" dropdown-menu
if(isset($_GET['linked_item'])){
   $entry_new_array = array();
   $entry_new_array[] = $_GET['linked_item'];
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$entry_new_array);
}

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
// Function used for redirecting to connected rubrics
if (isset($_GET['return_attach_item_list'])){
   $_POST = $session->getValue('linked_items_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_discussion_notification');
}

// function for page edit
// - to check files for virus
if (isset($c_virus_scan) and $c_virus_scan) {
   include_once('functions/page_edit_functions.php');
}

// Get the current user and context
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}
// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   $group_item = NULL;
} else {
   $group_manager = $environment->getGroupManager();
   $group_item = $group_manager->getItem($current_iid);
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $group_item->getAllLinkedItemIDArray();
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
}

// Check access rights
if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( $current_iid != 'NEW' and !isset($group_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($group_item) and
              $group_item->mayEdit($current_user))) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
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

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
      cleanup_session($current_iid);
      if ( $current_iid == 'NEW' ) {
         redirect($environment->getCurrentContextID(), 'group', 'index', '');
      } else {
         $params = array();
         $params['iid'] = $current_iid;
         redirect($environment->getCurrentContextID(), 'group', 'detail', $params);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(GROUP_FORM,$class_params);
      unset($class_params);

      // Foren:
      if ( isOption($command, $translator->getMessage('PREFERENCES_ADD_DISCUSSION_NOTIFICATION_BUTTON')) ) {
         $focus_element_onload = 'discussion_notification';
         $post_discussion_notification_array = array();

         if ( $session->issetValue($current_iid.'_discussion_notification') ) {
            $discussion_notification_array = $session->getValue($current_iid.'_discussion_notification');
         } else {
            $discussion_notification_array = array();
         }
         if ( !empty($_POST['discussion_notification']) and $_POST['discussion_notification']!=-1 and !in_array($_POST['discussion_notification'],$discussion_notification_array) ) {
            $discussion_notification_array[] = $_POST['discussion_notification'];
         }

         if ( count($discussion_notification_array) > 0 ) {
            $session->setValue($current_iid.'_discussion_notification', $discussion_notification_array);
         } else {
            $session->unsetValue($current_iid.'_discussion_notification');
         }
         $post_discussion_notification_array = $discussion_notification_array; //array_merge($post_discussion_notification_array, $new_discussion_notification_array);
      }

     include_once('include/inc_right_boxes_handling.php');
      // Load form data from postvars
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
         if ( !empty($_FILES) ) {
            if ( !empty($_FILES['picture_upload']['tmp_name']) ) {
               $new_temp_name = $_FILES['picture_upload']['tmp_name'].'_TEMP_'.$_FILES['picture_upload']['name'];
               move_uploaded_file($_FILES['picture_upload']['tmp_name'],$new_temp_name);
               $_FILES['picture_upload']['tmp_name'] = $new_temp_name;
               $session_item = $environment->getSessionItem();
               if ( isset($session_item) ) {
                  $session_item->setValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_temp_name',$new_temp_name);
                  $session_item->setValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_name',$_FILES['picture_upload']['name']);
               }
            }
            $values = array_merge($session_post_vars,$_FILES);
         } else {
            $values = $session_post_vars;
         }
         // Foren:
         if ( isset($post_discussion_notification_array) AND !empty($post_discussion_notification_array) ) {
            $values['discussion_notification_list'] = $post_discussion_notification_array;
         }
         $form->setFormPost($values);
      }
      // Load form data from database
      elseif ( isset($group_item) ) {
         $form->setItem($group_item);
         // Foren:
         $discussion_notification_array = $group_item->getDiscussionNotificationArray();
         if ( isset($discussion_notification_array[0])) {
            $session->setValue($current_iid.'_discussion_notification', $discussion_notification_array);
         }
      }

      // Create data for a new item
      elseif ( $current_iid == 'NEW' ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');
         trigger_error('group_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($current_iid.'_discussion_notification')) {
         $form->setSessionDiscussionNotificationArray($session->getValue($current_iid.'_discussion_notification'));
      }

      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('GROUP_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('GROUP_CHANGE_BUTTON')))
         ) {
         $correct = $form->check();
         if ( $correct
              and empty($_FILES['picture_upload']['tmp_name'])
              and !empty($_POST['hidden_picture_upload_name'])
            ) {
            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $_FILES['picture_upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_temp_name');
               $_FILES['picture_upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_temp_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_group_'.$current_iid.'_picture_name');
            }
         }
         if ( $correct
              and ( !isset($c_virus_scan)
                    or !$c_virus_scan
                    or page_edit_virusscan_isClean($_FILES['picture_upload']['tmp_name'],$_FILES['picture_upload']['name'])
                  )
            ) {
            // Create new item
            $item_is_new = false;
            if ( !isset($group_item) ) {
               $group_manager = $environment->getGroupManager();
               $group_item = $group_manager->getNewItem();
               $group_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $group_item->setCreatorItem($user);
               $group_item->setCreationDate(getCurrentDateTimeInMySQL());
               $group_item->setLabelType(CS_GROUP_TYPE);
               $item_is_new = true;
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $group_item->setModificatorItem($user);
            $group_item->setModificationDate(getCurrentDateTimeInMySQL());


            // Set attributes
            if (isset($_POST['name'])) {
               $group_item->setName($_POST['name']);
            }
            if (isset($_POST['description'])) {
               $group_item->setDescription($_POST['description']);
            }
            if (isset($_POST['public'])) {
               $group_item->setPublic($_POST['public']);
            }


            // Set picture
            if ( $group_item->getPicture()
                 and (isset($_POST['deletePicture'])
                      or !empty($_FILES['picture_upload']['name'])) ) {
              $disc_manager = $environment->getDiscManager();
               if ( $disc_manager->existsFile($group_item->getPicture()) ) {
                  $disc_manager->unlinkFile($group_item->getPicture());
               }
               $group_item->setPicture('');
            }
            if ( !empty($_FILES['picture_upload']['name']) ) {
               $filename = 'cid'.$environment->getCurrentContextID().
                           '_iid'.$group_item->getItemID().'_'
                           .$_FILES['picture_upload']['name'];
               $disc_manager = $environment->getDiscManager();
               $disc_manager->copyFile($_FILES['picture_upload']['tmp_name'],$filename,true);
               $group_item->setPicture($filename);
            }
            if ( !empty($_POST['group_room_activate']) ) {
               $group_item->setGroupRoomActive();
            }
            // Foren:
            $discussion_notification_array = array();
            if ( isset($_POST['discussion_notification_list']) ) {
               $discussion_notification_array = $_POST['discussion_notification_list'];
            }
            if ( isset($_POST['discussion_notification'])
                 and !in_array($_POST['discussion_notification'],$discussion_notification_array)
                 and ($_POST['discussion_notification'] != -1)
                 and ($_POST['discussion_notification'] != 'disabled')
               ) {
               $discussion_notification_array[] = $_POST['discussion_notification'];
            }

            $group_item->setDiscussionNotificationArray($discussion_notification_array);
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $group_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }

            // Save item
            $group_item->save();
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
               $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
            }else{
               $id_array =  array();
            }
            if ($item_is_new){
               $id_array[] = $group_item->getItemID();
               $id_array = array_reverse($id_array);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
           }


           // Redirect
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $group_item->getItemID();
            redirect($environment->getCurrentContextID(),
                     'group', 'detail', $params);
         }
      }

      // Display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if (!mayEditRegular($current_user, $group_item) and !$group_item->isSystemLabel()) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'group','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>