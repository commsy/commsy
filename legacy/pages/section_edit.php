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
$with_anchor = false;

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
   $section_item = NULL;
} else {
   $section_manager = $environment->getSectionManager();
   $section_item = $section_manager->getItem($current_iid);
}

// get the material belonging to this section
$material_ref_iid = NULL;
if (!empty($_GET['ref_iid'])) {
   $material_ref_iid = $_GET['ref_iid'];
   $session->setValue('material_ref_iid',$material_ref_iid);
} else {
   if (!empty($_GET['iid']) and $_GET['iid'] != 'NEW') {
      $section_manager = $environment->getSectionManager();
      $section_item = $section_manager->getItem($_GET['iid']);
      $material_ref_iid = $section_item->getLinkedItemID();
   } elseif(!empty($_POST['iid']) AND $_POST['iid'] != 'NEW') {
      $section_manager = $environment->getSectionManager();
      $section_item = $section_manager->getItem($_POST['iid']);
      $material_ref_iid = $section_item->getLinkedItemID();
   } elseif ($session->issetValue('material_ref_iid')) {
      $material_ref_iid = $session->getValue('material_ref_iid');
   } elseif ($session->issetValue('section_multi_upload_post_vars')) {
      $post_vars = $session->getValue('section_multi_upload_post_vars');
      $section_manager = $environment->getSectionManager();
      $section_item = $section_manager->getItem($post_vars['iid']);
      $material_ref_iid = $section_item->getLinkedItemID();
   }
}
$material_manager = $environment->getMaterialManager();
$material_item = $material_manager->getItem($material_ref_iid);

// Check access rights
if ( !( ( $current_iid == 'NEW' and $current_user->isUser() )
          or ( $current_iid != 'NEW' and isset($material_item)
               and $material_item->mayEdit($current_user)  // should be ITEM_ID
             )
        )
   ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('LOGIN_NOT_ALLOWED');
   $errorbox->setText($error_string);
   $page->add($errorbox);
}
// Access granted
else {
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(SECTION_FORM,$class_params);
   unset($class_params);
   $form->setMaterialID($material_ref_iid);

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      cleanup_session($current_iid);
      if (isset($section_item)and !empty($section_item)){
         $link_number = 'anchor'.$section_item->getItemID();
      } else{
         $link_number ='';
      }
      $params = array();
      $params['iid'] = $material_ref_iid;
      redirect($environment->getCurrentContextID(), 'material', 'detail', $params,$link_number);
   }

   // save section or goto attach materials
   else {
      // initialize form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(SECTION_FORM,$class_params);
      unset($class_params);
      $form->setMaterialID($material_ref_iid);

      // files
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

      // Back from attaching material
      elseif ( $backfrom == CS_MATERIAL_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_MATERIAL_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_MATERIAL_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Load form data from database
      elseif ( isset($section_item) ) {
         $form->setItem($section_item);

         // Files
         $file_list = $section_item->getFileList();
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
         include_once('functions/error_functions.php');trigger_error('section_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      if (!empty($command) AND
          (isOption($command,$translator->getMessage('SECTION_SAVE_BUTTON'))
           OR isOption($command,$translator->getMessage('SECTION_CHANGE_BUTTON'))
           OR isOption($command,$translator->getMessage('MATERIAL_VERSION_BUTTON'))) ) {
         $infoBox_forAutoNewVersion = "";

         $correct = $form->check();
         if ($correct) {
            // Create new item
            if ( !isset($section_item) ) {
               $section_manager = $environment->getSectionManager();
               $section_item = $section_manager->getNewItem();
               $section_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $section_item->setCreatorItem($user);
               $section_item->setCreationDate(getCurrentDateTimeInMySQL());
            }

            // new version?
            if ((!empty($command) AND isOption($command,$translator->getMessage('MATERIAL_VERSION_BUTTON')))
                  or ($_POST['material_modification_date'] != $material_item->getModificationDate())) {
                  $version = $material_item->getVersionID()+1;
                  $material_item->save();
                  $material_item = $material_item->cloneCopy();
                  $material_item->setVersionID($version);
                    $infoBox_forAutoNewVersion = "&autoVersion=true";
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $section_item->setModificatorItem($user);
            $section_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if (isset($_POST['title'])) {
               $section_item->setTitle($_POST['title']);
            }
            if (isset($_POST['description'])) {
               $section_item->setDescription($_POST['description']);
            }
            if (isset($_POST['number'])) {
               $section_item->setNumber($_POST['number']);
            }
            if (isset($material_item) ) {
               $section_item->setLinkedItemID($material_item->getItemID());
            }

            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $section_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
            } else {
               $section_item->setMaterialListByID(array());
            }

            // Update the material regarding the latest section informations...
            // (this takes care of saving the section itself, too)
            $user = $environment->getCurrentUserItem();
            $material_item->setModificatorItem($user);
            if (!$material_item->isNotActivated()){
               $material_item->setModificationDate($section_item->getModificationDate());
            }else{
               $material_item->setModificationDate($material_item->getModificationDate());
            }
            $section_list = $material_item->getSectionList();

            // files
            $item_files_upload_to = $section_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            $section_list->set($section_item);
            $material_item->setSectionList($section_list);
            $material_item->setSectionSaveID($section_item->getItemId());
            
            $external_view_array = $material_item->getExternalViewerArray();
            $material_item->setExternalViewerAccounts($external_view_array);
            
            $material_item->save();

            // redirect
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $material_ref_iid;
            if (!empty($infoBox_forAutoNewVersion)) {
               $params['autoVersion'] = 'true';
            }
            redirect($environment->getCurrentContextID(), 'material', 'detail', $params,'anchor'.$section_item->getItemID());
         }
      }

      // display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      if ($with_anchor){
         $form_view->withAnchor();
      }
      if (!mayEditRegular($current_user, $material_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'section','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>