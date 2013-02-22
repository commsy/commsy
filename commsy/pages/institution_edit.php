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

if (isset($_GET['return_attach_buzzword_list'])){
   $_POST = $session->getValue('buzzword_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
if (isset($_GET['return_attach_tag_list'])){
   $_POST = $session->getValue('tag_post_vars');
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

// function for page edit
// - to check files for virus
if (isset($c_virus_scan) and $c_virus_scan) {
   include_once('functions/page_edit_functions.php');
}

// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$room_item = $environment->getCurrentContextItem();

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
   $institution_item = NULL;
} else {
   $institution_manager = $environment->getLabelManager();
   $institution_item = $institution_manager->getItem($current_iid);
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $institution_item->getAllLinkedItemIDArray();
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
}

if ( $current_iid != 'NEW' and !isset($institution_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($institution_item) and
              $institution_item->mayEdit($current_user))) ) {
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

   if (!empty($_POST['option'])) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }


   // include form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(INSTITUTION_FORM,$class_params);
      unset($class_params);

   // cancel edit process
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
      cleanup_session($current_iid);
      if ( empty($_POST['iid']) ) {    // cancel new institution item
         redirect($environment->getCurrentContextID(), 'institution', 'index', '');
      } else {                                  // cancel edit institution item
         $params = array();
         $params['iid'] = $_POST['iid'];
         redirect($environment->getCurrentContextID(), 'institution', 'detail', $params);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(INSTITUTION_FORM,$class_params);
      unset($class_params);
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
                  $session_item->setValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_temp_name',$new_temp_name);
                  $session_item->setValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_name',$_FILES['picture_upload']['name']);
               }
            }
            $values = array_merge($session_post_vars,$_FILES);
         } else {
            $values = $session_post_vars;
         }
         $form->setFormPost($values);
      }
      // Load form data from database
      elseif ( isset($institution_item) ) {
         $form->setItem($institution_item);
      }

      // Create data for a new item
      elseif ( $current_iid == 'NEW' ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');trigger_error('institutions_edit was called in an unknown manner', E_USER_ERROR);
      }

      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('INSTITUTION_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('INSTITUTION_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct
              and empty($_FILES['picture_upload']['tmp_name'])
              and !empty($_POST['hidden_picture_upload_name'])
            ) {
            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $_FILES['picture_upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_temp_name');
               $_FILES['picture_upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_temp_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_institution_'.$current_iid.'_picture_name');

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
            if ( !isset($institution_item) ) {
               $institution_manager = $environment->getLabelManager();
               $institution_item = $institution_manager->getNewItem();
               $institution_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $institution_item->setCreatorItem($user);
               $institution_item->setCreationDate(getCurrentDateTimeInMySQL());
               $institution_item->setLabelType(CS_INSTITUTION_TYPE);
               $item_is_new = true;
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $institution_item->setModificatorItem($user);
            $institution_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if (isset($_POST['name'])) {
               $institution_item->setName($_POST['name']);
            }
            if (isset($_POST['description'])) {
               $institution_item->setDescription($_POST['description']);
            }
            if (isset($_POST['public'])) {
               $institution_item->setPublic($_POST['public']);
            }

            if ( (isset($_POST['deletePicture']) or !empty($_FILES['picture_upload']['name'])) and $institution_item->getPicture() ) {
              $disc_manager = $environment->getDiscManager();
               if ( $disc_manager->existsFile($institution_item->getPicture()) ) {
                  $disc_manager->unlinkFile($institution_item->getPicture());
               }
               $institution_item->setPicture('');
            }

            if ( !empty($_FILES['picture_upload']['name']) ) {
               $filename = 'cid'.$environment->getCurrentContextID().'_iid'.$institution_item->getItemID().'_'.$_FILES['picture_upload']['name'];
              $disc_manager = $environment->getDiscManager();
               $disc_manager->copyFile($_FILES['picture_upload']['tmp_name'],$filename,true);
               $institution_item->setPicture($filename);
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $institution_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }
            // Save item
            $institution_item->save();
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
               $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
            }else{
               $id_array =  array();
            }
            if ($item_is_new){
               $id_array[] = $institution_item->getItemID();
               $id_array = array_reverse($id_array);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
           }

            // Redirect
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $institution_item->getItemID();
            redirect($environment->getCurrentContextID(),
                     'institution', 'detail', $params);
         }
      }

      // Display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if (!mayEditRegular($current_user, $institution_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'institution','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}

?>