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


// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_material_attach_ids');
   $session->unsetValue($current_iid.'_material_back_module');
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
$with_anchor = false;

// Load item from database
if ( $current_iid == 'NEW' ) {
   $step_item = NULL;
} else {
   $step_manager = $environment->getStepManager();
   $step_item = $step_manager->getItem($current_iid);
}
// Find the todo this steps belongs to
if ( $current_iid != 'NEW' ) {
   $todo_id = $step_item->getTodoID();
} else {
   if ( !empty($_GET['did']) ) {
      $todo_id = $_GET['did'];
   } elseif ( !empty($_POST['todo_id']) ) {
      $todo_id = $_POST['todo_id'];
   } elseif ( !empty($_GET['todo_id']) ) {
      $todo_id = $_GET['todo_id'];
   } else {
      if ( $session->issetValue($current_iid.'_post_vars') ) {
         $session_postvars = $session->getValue($current_iid.'_post_vars');
         if ( isset($session_postvars['todo_id']) ) {
            $todo_id = $session_postvars['todo_id'];
         } else {
            include_once('functions/error_functions.php');trigger_error('A todo id must be given for new todo steps.', E_USER_ERROR);
         }
      } elseif ( $session->issetValue($environment->getCurrentModule().'_multi_upload_post_vars') ) {
         $session_postvars = $session->getValue($environment->getCurrentModule().'_multi_upload_post_vars');
         if ( isset($session_postvars['todo_id']) ) {
            $todo_id = $session_postvars['todo_id'];
         } else {
            include_once('functions/error_functions.php');trigger_error('Lost todo id for todo steps.', E_USER_ERROR);
         }
      } else {
         include_once('functions/error_functions.php');trigger_error('A todo id must be given for new todo steps.', E_USER_ERROR);
      }
   }
   $todo_manager = $environment->getTodoManager();
   $todo = $todo_manager->getItem($todo_id);
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
} elseif ( $current_iid != 'NEW' and !isset($step_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($step_item) and
              $step_item->mayEdit($current_user))) ) {
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
       if (isset($step_item) and !empty($step_item)){
         $step_id = 'anchor'.$step_item->getItemID();
      } else {
         $step_id = '';
      }
      cleanup_session($current_iid);
      if ( $current_iid == 'NEW' and empty($todo_id) ) {
         redirect($environment->getCurrentContextID(), 'todo', 'index', '');
      } else {
         $params = array();
         $params['iid'] = $todo_id;
         redirect($environment->getCurrentContextID(), 'todo', 'detail', $params, $step_id);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(STEP_FORM,$class_params);
      unset($class_params);
      $form->setTodoID($todo_id);
      if (isset($ref_did)){
         $form->setRefDid($ref_did);
      }

      include_once('include/inc_fileupload_edit_page_handling.php');

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $session_post_vars = $_POST;
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
      }

      // Back from multi upload
      elseif ( $from_multiupload ) {
         $session_post_vars = array();
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
      }


      // Load form data from database
      elseif ( isset($step_item) ) {
         $form->setItem($step_item);

         // Files
         $file_list = $step_item->getFileList();
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
         include_once('functions/error_functions.php');trigger_error('step_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('STEP_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('STEP_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct ) {

            // Create new item
            if ( !isset($step_item) ) {
               $step_manager = $environment->getStepManager();
               $step_item = $step_manager->getNewItem();
               $step_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $step_item->setCreatorItem($user);
               $step_item->setCreationDate(getCurrentDateTimeInMySQL());
               $step_item->setTodoID($todo_id);
            }
            $todo_manager = $environment->getTodoManager();
            $todo_item = $todo_manager->getItem($todo_id);
            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $step_item->setModificatorItem($user);
            $step_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if (isset($_POST['subject'])) {
               $step_item->setTitle($_POST['subject']);
            }
            if (isset($_POST['description'])) {
               $step_item->setDescription($_POST['description']);
            }
            if ( isset($_POST['minutes']) ) {
               $minutes = $_POST['minutes'];
               $minutes = str_replace(',','.',$minutes);
               if (isset($_POST['time_type'])){
                  $step_item->setTimeType($_POST['time_type']);
                  switch ($_POST['time_type']){
                     case 2: $minutes = $minutes*60;break;
                     case 3: $minutes = $minutes*60*8;break;
                  }
               }
               $step_item->setMinutes($minutes);
            }

            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $step_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
            } else {
               $step_item->setMaterialListByID(array());
            }

            $item_files_upload_to = $step_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            // Save item
            $step_item->save();
            $status = $todo_item->getStatus();
            if ( $status == $translator->getMessage('TODO_NOT_STARTED')){
               $todo_item->setStatus(2);
            }
            $todo_item->setModificationDate(getCurrentDateTimeInMySQL());
            $todo_item->save();


            // Redirect
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $step_item->getTodoID();
            redirect($environment->getCurrentContextID(),
                     'todo', 'detail', $params,'anchor'.$step_item->getItemID());
         }
      }

      // Display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if ($with_anchor){
        $form_view->withAnchor();
     }
      $todo_manager = $environment->getTodoManager();
      if ( isset($step_item) ){
         $todo_item = $todo_manager->getItem($step_item->getTodoID());
         if (!mayEditRegular($current_user, $step_item)) {
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
      }
      $form_view->setAction(curl($environment->getCurrentContextID(),'step','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}

?>