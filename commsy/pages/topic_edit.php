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
}

// Get the current user and room
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
   $topic_item = NULL;
} else {
   $topic_manager = $environment->getTopicManager();
   $topic_item = $topic_manager->getItem($current_iid);
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $topic_item->getAllLinkedItemIDArray();
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
}

// Check access rights
if ( $environment->inProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( $current_iid != 'NEW' and !isset($topic_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($topic_item) and
              $topic_item->mayEdit($current_user))) ) {
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
         redirect($environment->getCurrentContextID(), CS_TOPIC_TYPE, 'index', '');
      } else {
         $params = array();
         $params['iid'] = $current_iid;
         redirect($environment->getCurrentContextID(),CS_TOPIC_TYPE, 'detail', $params);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(TOPIC_FORM,$class_params);
      unset($class_params);

      // files
      include_once('include/inc_fileupload_edit_page_handling.php');
      include_once('include/inc_right_boxes_handling.php');

      // PATH
      if($current_iid == 'NEW' and $context_item->withPath()){
         $path_items = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
         if(!empty($path_items)){
            $form->setPathItems($path_items);
         }
      }
      if ( isOption($command, $translator->getMessage('TOPIC_ACTIVATE_PATH')) ) {
         $form->activatePath();
         $_POST['path_active'] = 1;
      } elseif ( isOption($command, $translator->getMessage('TOPIC_DEACTIVATE_PATH')) ) {
         $form->deactivatePath();
         $_POST['path_active'] = -1;
      } elseif ( !empty($_POST)
                and !empty($_POST['path_active'])
                and $_POST['path_active'] == 1 ) {
         $form->activatePath();
      } elseif ( isset($topic_item)
                and $topic_item->isPathActive() ) {
         $form->activatePath();
      }
      if ( isOption($command, $translator->getMessage('COMMON_ITEM_NEW_ATTACH')) ) {
         $form->resetPathItems();
      }

      // Back from multi upload
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
      }
      elseif ( $from_multiupload ) {
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
      }

      include_once('include/inc_right_boxes_handling.php');

      // Load form data from postvars
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
         $form->setFormPost($session_post_vars);
      }
      // Load form data from database
      elseif ( isset($topic_item) ) {
         $form->setItem($topic_item);
         // Files
         $file_list = $topic_item->getFileList();
         if ( !$file_list->isEmpty() ) {
            $file_array = array();
            $file_item = $file_list->getFirst();
            while ( $file_item ) {
               $temp_array = array();
               $temp_array['name'] = $file_item->getDisplayName();
               $temp_array['file_id'] = (int)$file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
            if ( !empty($file_array)) {
               $session->setValue($environment->getCurrentModule().'_add_files', $file_array);
            }
         }
      }

      // Create data for a new item
      elseif ( $current_iid == 'NEW' ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');trigger_error('topic_edit was called in an unknown manner', E_USER_ERROR);
      }
      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }

      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
           and ( isOption($command, $translator->getMessage('TOPIC_SAVE_BUTTON'))
                 or isOption($command, $translator->getMessage('TOPIC_CHANGE_BUTTON'))
               )
         ) {

         $correct = $form->check();
         if ( $correct ) {

            // Create new item
            $item_is_new = false;
            if ( !isset($topic_item) ) {
               $topic_manager = $environment->getTopicManager();
               $topic_item = $topic_manager->getNewItem();
               $topic_item->setContextID($context_item->getItemID());
               $user = $environment->getCurrentUserItem();
               $topic_item->setCreatorItem($user);
               $topic_item->setCreationDate(getCurrentDateTimeInMySQL());
               $item_is_new = true;
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $topic_item->setModificatorItem($user);
            $topic_item->setModificationDate(getCurrentDateTimeInMySQL());

            // files
            $item_files_upload_to = $topic_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            // Set attributes
            if (isset($_POST['name'])) {
               $topic_item->setName($_POST['name']);
            }
            if (isset($_POST['description'])) {
               $topic_item->setDescription($_POST['description']);
            }

            if(isset($_POST['public'])) {
               if($topic_item->isPublic() != $_POST['public']) {
                  $topic_item->setPublic($_POST['public']);
               }
            } else {
               if(isset($_POST['private_editing'])) {
                  $topic_item->setPrivateEditing('0');
               } else {
                  $topic_item->setPrivateEditing('1');
               }
            }
            
            if ( isset($_POST['hide']) ) {
                // variables for datetime-format of end and beginning
                $dt_hiding_time = '00:00:00';
                $dt_hiding_date = '9999-00-00';
                $dt_hiding_datetime = '';
                $converted_day_start = convertDateFromInput($_POST['dayStart'],$environment->getSelectedLanguage());
                if ($converted_day_start['conforms'] == TRUE) {
                   $dt_hiding_datetime = $converted_day_start['datetime'].' ';
                   $converted_time_start = convertTimeFromInput($_POST['timeStart']);
                   if ($converted_time_start['conforms'] == TRUE) {
                      $dt_hiding_datetime .= $converted_time_start['datetime'];
                   }else{
                      $dt_hiding_datetime .= $dt_hiding_time;
                   }
                }else{
                   $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
                }
                $topic_item->setModificationDate($dt_hiding_datetime);
            } else {
               if($topic_item->isNotActivated()){
                  $topic_item->setModificationDate(getCurrentDateTimeInMySQL());
               }
            }
            
            if ( isset($_POST['path_active']) and $_POST['path_active'] == 1 ) {
               $topic_item->activatePath();
            } elseif ( isset($_POST['path_active']) and $_POST['path_active'] == -1 ) {
               $topic_item->deactivatePath();
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $topic_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }
            // Save item
            $topic_item->save();
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
               $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
            }else{
               $id_array =  array();
            }
            if ($item_is_new){
               $id_array[] = $topic_item->getItemID();
               $id_array = array_reverse($id_array);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
           }

            // PATH
            if ( isset($_POST['path_active'])
                 and !empty($_POST['path_active'])
                 and $_POST['path_active'] == 1
                 and isset($_POST['sorting'])
                 and !empty($_POST['sorting'])
               ) {
               $item_place_array = array();
               foreach ($_POST['sorting'] as $place => $item_id) {
                  $temp_array = array();
                  $temp_array['place'] = $place+1;
                  if ( !empty($_POST['path_new_id_array'])
                       and in_array($item_id,$_POST['path_new_id_array'])
                     ) {
                     $link_manager = $environment->getLinkItemManager();
                     $link_item = $link_manager->getItemByFirstAndSecondID($item_id,$topic_item->getItemID());
                     if ( !empty($link_item) ) {
                        $item_id = $link_item->getItemID();
                     } else {
                        $item_id = '';
                     }
                  }
                  if ( !empty($item_id) ) {
                     $temp_array['item_id'] = $item_id;
                     $item_place_array[] = $temp_array;
                  }
               }
               $link_item_manager = $environment->getLinkItemManager();
               $link_item_manager->cleanSortingPlaces($topic_item);
               $link_item_manager->saveSortingPlaces($item_place_array);
            } elseif ( isset($_POST['path_active'])
                       and !empty($_POST['path_active'])
                       and $_POST['path_active'] == -1
                     ) {
               $link_item_manager = $environment->getLinkItemManager();
               $link_item_manager->cleanSortingPlaces($topic_item);
            } elseif ( isset($_POST['path_active'])
                       and !empty($_POST['path_active'])
                       and $_POST['path_active'] == 1
                       and empty($_POST['sorting'])
                     ) {
               $link_item_manager = $environment->getLinkItemManager();
               $link_item_manager->cleanSortingPlaces($topic_item);
            }

            // Redirect
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $topic_item->getItemID();
            redirect($environment->getCurrentContextID(),
                     CS_TOPIC_TYPE, 'detail', $params);
         }
      }

      // Display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if (!mayEditRegular($current_user, $topic_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),CS_TOPIC_TYPE,'edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>