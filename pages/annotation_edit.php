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

// Get the translator object
$translator = $environment->getTranslationObject();
// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);
   if ( isset($_POST[$rubric_type]) ) {
      $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
   } else {
      $session->setValue($current_iid.$infix.'_attach_ids', array());
   }
   $session->setValue($current_iid.$infix.'_back_module', 'annotation');
   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   redirect($environment->getCurrentContextID(), 'material', 'index', $params);
}

function attach_return ($rubric_type, $current_iid) {
   global $session;
   $infix = '_'.$rubric_type;
   $attach_ids = $session->getValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.$infix.'_back_module');
   return $attach_ids;
}

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_material_attach_ids');
   $session->unsetValue($current_iid.'_material_back_module');
   $session->unsetValue('annotation_history_context');
   $session->unsetValue('annotation_history_module');
   $session->unsetValue('annotation_history_function');
   $session->unsetValue('annotation_history_parameter');
}


// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$room_item = $environment->getCurrentContextItem();

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}
$with_anchor = false;

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   $annotation_item = NULL;
} else {
   $annotation_manager = $environment->getAnnotationManager();
   $annotation_item = $annotation_manager->getItem($current_iid);
}

// since we will need the history in many cases we will get it now
$history = $session->getValue('history');


// we have to save the history
if (isset($_GET['mode'])){
   if ($_GET['mode'] == 'annotate' ){
      if ( $history[0]['module'] != 'annotation') {
         $session->setValue('annotation_history_context',$history[0]['context']);
         $session->setValue('annotation_history_module',$history[0]['module']);
         $session->setValue('annotation_history_function',$history[0]['function']);
         $session->setValue('annotation_history_parameter',$history[0]['parameter']);
      }
   }
}
// Check access rights
$item_manager = $environment->getItemManager();
if ( $current_iid != 'NEW' and !isset($annotation_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($annotation_item) and $annotation_item->mayEdit($current_user)) or
             ($current_iid == 'NEW' and isset($_GET['ref_iid']) and $item_manager->getExternalViewerForItem($_GET['ref_iid'],$current_user->getUserID()))
             or true
             ) ) {
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
      $context = $session->getValue('annotation_history_context');
      $module = $session->getValue('annotation_history_module');
      $function = $session->getValue('annotation_history_function');
      $param = $session->getValue('annotation_history_parameter');

      if (isset($annotation_item) and !empty($annotation_item)){
        $anchor= 'anchor'.$annotation_item->getItemID();
     }else{
        $anchor ='';
     }
     cleanup_session($current_iid);
      redirect($context,$module,$function, $param, $anchor);
   }

   // Delete item
   elseif ( isOption($command, $translator->getMessage('ANNOTATION_DELETE_BUTTON')) ) {
      // go back to the origin
      $context = $session->getValue('annotation_history_context');
      $module = $session->getValue('annotation_history_module');
      $function = $session->getValue('annotation_history_function');
      $param = $session->getValue('annotation_history_parameter');

      cleanup_session($current_iid);
      $annotation_item->delete();
      redirect($context,$module,$function, $param);
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(ANNOTATION_FORM,$class_params);
      unset($class_params);

      if ( !empty($_GET['mode'])
           and $_GET['mode'] == 'annotate'
           and !empty($_POST)
         ) {
         $form->setDetailMode(1);
      }

      // files
      include_once('include/inc_fileupload_edit_page_handling.php');

      // Define rubric connections
      $rubric_connection = array();
      $rubric_connection[] = CS_MATERIAL_TYPE;
      $form->setRubricConnections($rubric_connection);

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
      elseif ( isset($annotation_item) ) {
         $form->setItem($annotation_item);

         // Files
         $file_list = $annotation_item->getFileList();
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
         $form->setRefID($_GET['ref_iid']);
         if ( !empty($_GET['version']) ) {
            $form->setVersion($_GET['version']);
         }
      }

      else {
         include_once('functions/error_functions.php');
         trigger_error('annotation_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('ANNOTATION_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('ANNOTATION_CHANGE_BUTTON'))
            or isOption($command, $translator->getMessage('ANNOTATION_ADD_NEW_BUTTON'))
            )
         ) {
         $correct = $form->check();
         if ( $correct ) {
            // Create new item
            if ( !isset($annotation_item) ) {
               $annotation_manager = $environment->getAnnotationManager();
               $annotation_item = $annotation_manager->getNewItem();
               $annotation_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $annotation_item->setCreatorItem($user);
               $annotation_item->setCreationDate(getCurrentDateTimeInMySQL());
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $annotation_item->setModificatorItem($user);
            $annotation_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if ( isset($_POST['title']) ) {
               $annotation_item->setTitle($_POST['title']);
            }elseif ( isset($_POST['annotation_title']) ) {
               $annotation_item->setTitle($_POST['annotation_title']);
            }
            if ( isset($_POST['description']) ) {
               $annotation_item->setDescription($_POST['description']);
            }elseif ( isset($_POST['annotation_description']) ) {
               $annotation_item->setDescription($_POST['annotation_description']);
            }
            if ( !empty($_POST['ref_iid']) ) {
                  $annotation_item->setLinkedItemID($_POST['ref_iid']);
            }
            if ( !empty($_POST['version']) ) {
                  $annotation_item->setLinkedVersionID($_POST['version']);
            }

            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $annotation_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
            } else {
               $annotation_item->setMaterialListByID(array());
            }

            // files
            $item_files_upload_to = $annotation_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            // Save item
            $annotation_item->save();

            //Add modifier to all users who ever edited this item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($annotation_item->getItemID());


            // Reset id array
            $session->setValue('cid'.$environment->getCurrentContextID().'_annotation_index_ids',
                               array($annotation_item->getItemID()));

            $context = $session->getValue('annotation_history_context');
            $module = $session->getValue('annotation_history_module');
            $function = $session->getValue('annotation_history_function');
            $param = $session->getValue('annotation_history_parameter');
            if (isset($annotation_item) and !empty($annotation_item)){
             $anchor = 'anchor'.$annotation_item->getItemID();
           }else{
             $anchor ='';
           }

            // Redirect
            cleanup_session($current_iid);
            redirect($context,$module,$function, $param, $anchor);
         } elseif ( $form->isDetailModeActive() ) {
            $form->reset();
            if ( !empty($_POST) ) {
               $form->setFormPost($_POST);
            }
            $form->prepareForm();
            $form->loadValues();
            $form->check();
         }
      }

      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$params);
      unset($params);
      if ($with_anchor){
         $form_view->withAnchor();
      }
      if (!mayEditRegular($current_user, $annotation_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'annotation','edit',''));
      $form_view->setForm($form);
      $form_view->setRubricConnections($rubric_connection);
      $page->add($form_view);
   }
}
?>