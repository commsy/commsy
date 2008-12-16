<?php
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

set_time_limit(0); // ???

/*** Neue Schlagwörter und Tags***/
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
if (isset($_GET['return_attach_item_list'])){
   $_POST = $session->getValue('linked_items_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
/*** Neue Schlagwörter und Tags***/

// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);

    if ( $rubric_type=='material_bib' ) {
       $params = array();
       $params['ref_iid'] = $current_iid;
       redirect($environment->getCurrentContextID(), 'material_bib', 'edit',
          $params);
    }else {
       if ( isset($_POST[$rubric_type]) ) {
          $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
       } else {
          $session->setValue($current_iid.$infix.'_attach_ids', array());
       }
       $session->setValue($current_iid.$infix.'_back_module', 'material');
       $params = array();
       $params['ref_iid'] = $current_iid;
       $params['mode'] = 'formattach';
       redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params);
    }
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
   global $session, $environment;
   $session->unsetValue('material_add_files');
   $session->unsetValue('material_add_buzzwords');
   $session->unsetValue('material_add_tags');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_group_attach_ids');
   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_institution_attach_ids');
   $session->unsetValue($current_iid.'_group_back_module');
   $session->unsetValue($current_iid.'_topic_back_module');
   $session->unsetValue($current_iid.'_project_back_module');
   $session->unsetValue($current_iid.'_institution_back_module');
}

// Get the current user
$current_user = $environment->getCurrentUserItem();

// Get the current room
$context_item = $environment->getCurrentContextItem();
$with_anchor = false;

// Get material to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}

// Coming back from attaching items
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load material from database
if ( $current_iid == 'NEW' ) {
   $material_item = NULL;
} else {
   $buzzword_array = array();
   $tag_array = array();
   $material_manager = $environment->getMaterialManager();
   $material_item = $material_manager->getItem($current_iid);
/*** Neue Schlagwörter und Tags***/
   if(empty($_POST)){
      $buzzword_array = array();
      $buzzwords = $material_item->getBuzzwordList();
      $buzzword = $buzzwords->getFirst();
      while($buzzword){
         $buzzword_array[] = $buzzword->getItemID();
         $buzzword = $buzzwords->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids',$buzzword_array);
   }
   if(empty($_POST)){
      $tag_array = array();
      $tags = $material_item->getTagList();
      $tag = $tags->getFirst();
      while($tag){
         $tag_array[] = $tag->getItemID();
         $tag = $tags->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids',$tag_array);
   }
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $material_item->getAllLinkedItemIDArray();
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
/*** Neue Schlagwörter und Tags***/
}

// Check access rights
if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( $current_iid != 'NEW' and !isset($material_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($material_item) and
              $material_item->mayEdit($current_user))) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   }elseif ( isset($_GET['option']) ) {
      $command = $_GET['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
      cleanup_session($current_iid);
/*** Neue Schlagwörter und Tags***/
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
      $session->unsetValue('buzzword_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
      $session->unsetValue('tag_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
/*** Neue Schlagwörter und Tags***/
      if ( $current_iid == 'NEW' ) {
         redirect($environment->getCurrentContextID(), 'material', 'index', '');
      } else {
         $params = array();
         $params['iid'] = $current_iid;
         redirect($environment->getCurrentContextID(), 'material', 'detail', $params);
      }
   }

   // Show form and/or save material
   else {

      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(MATERIAL_FORM,$class_params);
      unset($class_params);

      include_once('include/inc_fileupload_edit_page_handling.php');

      // Redirect to attach material
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_MATERIAL_BUTTON')) ) {
         attach_redirect(CS_MATERIAL_TYPE, $current_iid);
      }

      // Redirect to attach TODO
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_TODO_BUTTON')) ) {
         attach_redirect(CS_TODO_TYPE, $current_iid);
      }

      // Redirect to attach DATE
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_DATE_BUTTON')) ) {
         attach_redirect(CS_DATE_TYPE, $current_iid);
      }

      // Redirect to attach ANNOUNCEMENT
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_ANNOUNCEMENT_BUTTON')) ) {
         attach_redirect(CS_ANNOUNCEMENT_TYPE, $current_iid);
      }

      // Redirect to attach DISCUSSION
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_DISCUSSION_BUTTON')) ) {
         attach_redirect(CS_DISCUSSION_TYPE, $current_iid);
      }

      // Redirect to attach groups
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_GROUP_BUTTON')) ) {
         attach_redirect(CS_GROUP_TYPE, $current_iid);
      }

      // Redirect to attach projects
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_PROJECT_BUTTON')) ) {
         attach_redirect(CS_PROJECT_TYPE, $current_iid);
      }

      // Redirect to attach topics
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON')) ) {
         attach_redirect(CS_TOPIC_TYPE, $current_iid);
      }

      // Redirect to attach institutions
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_INSTITUTION_BUTTON')) ) {
         attach_redirect(CS_INSTITUTION_TYPE, $current_iid);
      }

/*** Neue Schlagwörter und Tags***/
      include_once('include/inc_right_boxes_handling.php');
/*** Neue Schlagwörter und Tags***/

      // Add a new buzzword
      if ( isOption($command, getMessage('COMMON_ADD_BUZZWORD_BUTTON')) or isOption($command, getMessage('COMMON_NEW_BUZZWORD_BUTTON')) ) {
         $focus_element_onload = 'buzzword';
         $post_buzzword_ids = array();
         $new_buzzword_ids = array();
         if ( isset($_POST['buzzwordlist']) ) {
            $post_buzzword_ids = $_POST['buzzwordlist'];
         }
         if ( $session->issetValue('material_add_buzzwords') ) {
            $buzzword_array = $session->getValue('material_add_buzzwords');
         } else {
            $buzzword_array = array();
         }
         if ( !empty($_POST['buzzword']) and $_POST['buzzword']!=-1 and $_POST['buzzword']!=-2 and !in_array($_POST['buzzword'],$post_buzzword_ids) ) {
            $temp_array = array();
            $buzzword_manager = $environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_item = $buzzword_manager->getItem($_POST['buzzword']);

            $temp_array['name'] = $buzzword_item->getTitle();
            $temp_array['id'] = $buzzword_item->getItemID();
            $buzzword_array[] = $temp_array;
            $new_buzzword_ids[] = $temp_array['id'];
         }
         if ( !empty($_POST['new_buzzword']) and isOption($command, getMessage('COMMON_NEW_BUZZWORD_BUTTON')) ) {
            $focus_element_onload  = 'new_buzzword';
            $buzzword_manager = $environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_manager->setContextLimit($environment->getCurrentContextID());
            $buzzword_manager->setTypeLimit('buzzword');
            $buzzword_manager->select();
            $buzzword_list = $buzzword_manager->get();
            $exist = NULL;
            if ( !empty($buzzword_list) ){
               $buzzword = $buzzword_list->getFirst();
               while ( $buzzword ){
                  if ( strcmp($buzzword->getName(), ltrim($_POST['new_buzzword'])) == 0 ){
                     $exist = $buzzword->getItemID();
                  }
                  $buzzword = $buzzword_list->getNext();
               }
            }
            if ( !isset($exist) ) {
               $temp_array = array();
               $buzzword_manager = $environment->getLabelManager();
               $buzzword_manager->reset();
               $buzzword_item = $buzzword_manager->getNewItem();
               $buzzword_item->setLabelType('buzzword');
               $buzzword_item->setTitle(ltrim($_POST['new_buzzword']));
               $buzzword_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $buzzword_item->setCreatorItem($user);
               $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
               $buzzword_item->save();
               $temp_array['name'] = $buzzword_item->getTitle();
               $temp_array['id'] = $buzzword_item->getItemID();
               $buzzword_array[] = $temp_array;
               $new_buzzword_ids[] = $temp_array['id'];
            } elseif ( isset($exist) and !in_array($exist,$post_buzzword_ids) ) {
               $temp_array = array();
               $buzzword_manager = $environment->getLabelManager();
               $buzzword_manager->reset();
               $buzzword_item = $buzzword_manager->getItem($exist);
               $temp_array['name'] = $buzzword_item->getTitle();
               $temp_array['id'] = $buzzword_item->getItemID();
               $buzzword_array[] = $temp_array;
               $new_buzzword_ids[] = $temp_array['id'];
            }
         }
         if ( count($buzzword_array) > 0 ) {
            $session->setValue('material_add_buzzwords', $buzzword_array);
         } else {
            $session->unsetValue('material_add_buzzwords');
         }
         $post_buzzword_ids = array_merge($post_buzzword_ids, $new_buzzword_ids);
      }


      // Add a new tag
      if ( isOption($command, getMessage('COMMON_ADD_TAG_BUTTON')) ) {
         $focus_element_onload = 'tag';
         $new_tag_ids = array();
         $post_tag_ids = array();
         if ( isset($_POST['taglist']) ) {
            $post_tag_ids = $_POST['taglist'];
         }
         if ( $session->issetValue('material_add_tags') ) {
            $tag_array = $session->getValue('material_add_tags');
         } else {
            $tag_array = array();
         }
         if ( !empty($_POST['tag']) and $_POST['tag']!=-1 and $_POST['tag']!=-2 and !in_array($_POST['tag'],$post_tag_ids) ) {
            $temp_array = array();
            $tag_manager = $environment->getTagManager();
            $tag_manager->reset();
            $tag_item = $tag_manager->getItem($_POST['tag']);

            $temp_array['name'] = $tag_item->getTitle();
            $temp_array['id'] = $tag_item->getItemID();
            $tag_array[] = $temp_array;
            $new_tag_ids[] = $temp_array['id'];
         }
         if ( count($tag_array) > 0 ) {
            $session->setValue('material_add_tags', $tag_array);
         } else {
            $session->unsetValue('material_add_tags');
         }
         $post_tag_ids = array_merge($post_tag_ids, $new_tag_ids);
      }

      // Load form data from postvars
      if ( !empty($_POST) ) {
/*** Neue Schlagwörter und Tags***/
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
/*** Neue Schlagwörter und Tags***/
         if ( !empty($command) and isOption($command, getMessage('COMMON_NEW_BUZZWORD_BUTTON')) ){
            $session_post_vars['new_buzzword']='';
         }


         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         if ( isset($post_buzzword_ids) AND !empty($post_buzzword_ids) ) {
            $session_post_vars['buzzwordlist'] = $post_buzzword_ids;
         }
         if ( isset($post_tag_ids) AND !empty($post_tag_ids) ) {
            $session_post_vars['taglist'] = $post_tag_ids;
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

      // Back from adding detailed bibliographic data
      elseif ( $backfrom == 'bib' ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars');
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching groups
      elseif ( $backfrom == CS_GROUP_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_GROUP_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_GROUP_TYPE] = $attach_ids;
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

      // Back from attaching PROJECT
      elseif ( $backfrom == CS_PROJECT_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_PROJECT_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_PROJECT_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching DISCUSSION
      elseif ( $backfrom == CS_DISCUSSION_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_DISCUSSION_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_DISCUSSION_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching TODO
      elseif ( $backfrom == CS_TODO_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_TODO_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_TODO_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching DATE
      elseif ( $backfrom == CS_DATE_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_DATE_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_DATE_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching ANNOUNCEMENT
      elseif ( $backfrom == CS_ANNOUNCEMENT_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_ANNOUNCEMENT_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_ANNOUNCEMENT_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }



      // Back from attaching topics
      elseif ( $backfrom == CS_TOPIC_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_TOPIC_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_TOPIC_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching institutions
      elseif ( $backfrom == CS_INSTITUTION_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_INSTITUTION_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_INSTITUTION_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Load form data from database
      elseif ( isset($material_item) ) {

         $form->setItem($material_item);

         // Files
         $file_list = $material_item->getFileList();
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
               $session->setValue('material_add_files', $file_array);
            }
         }

         // Buzzwords
         $buzzword_list = $material_item->getBuzzwordList();
         $buzzword_list->sortby('title');
         if ( !$buzzword_list->isEmpty() ) {
            $buzzword_array = array();
            $buzzword_item = $buzzword_list->getFirst();
            while ( $buzzword_item ) {
               $temp_array = array();
               $temp_array['name'] = $buzzword_item->getTitle();
               $temp_array['id'] = (int)$buzzword_item->getItemID();
               $buzzword_array[] = $temp_array;
               $buzzword_item = $buzzword_list->getNext();
            }
            if ( !empty($buzzword_array)) {
               $session->setValue('material_add_buzzwords', $buzzword_array);
            }
         }
         // Tags
         $tag_list = $material_item->getTagList();
         if ( !$tag_list->isEmpty() ) {
            $tag_array = array();
            $tag_item = $tag_list->getFirst();
            while ( $tag_item ) {
               $temp_array = array();
               $temp_array['name'] = $tag_item->getTitle();
               $temp_array['id'] = (int)$tag_item->getItemID();
               $tag_array[] = $temp_array;
               $tag_item = $tag_list->getNext();
            }
            if ( !empty($tag_array)) {
               $session->setValue('material_add_tags', $tag_array);
            }
         }
      }

      // Create data for a new material
      elseif ( $current_iid == 'NEW' and !$from_multiupload ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');
         trigger_error('material_edit was called in an unknown manner', E_USER_ERROR);
      }

      // Init form, create form and load values
      if ($session->issetValue('material_add_files')) {
         $form->setSessionFileArray($session->getValue('material_add_files'));
      }
      if ($session->issetValue('material_add_buzzwords')) {
         $form->setSessionBuzzwordArray($session->getValue('material_add_buzzwords'));
      }
      if ($session->issetValue('material_add_tags')) {
         $form->setSessionTagArray($session->getValue('material_add_tags'));
      }
      $form->prepareForm();
      $form->loadValues();
      if ( isset($failure) ) {
         $form->setFailure($failure['name'], '', $failure['text']);
      }

      // Save item
      if ( !empty($command) and
           ( isOption($command, getMessage('MATERIAL_SAVE_BUTTON'))
             or isOption($command, getMessage('MATERIAL_CHANGE_BUTTON'))
             or isOption($command, getMessage('MATERIAL_VERSION_BUTTON'))
     )
     and ( !isset($error_on_upload)
                 or !$error_on_upload
               )
   ) {

         $correct = $form->check();
         if ( $correct ) {
            $error = false;

            if ( !isset($material_item) ) {
               $material_manager = $environment->getMaterialManager();
               $material_item = $material_manager->getNewItem();
               $material_item->setVersionID(0); // Should not be required, mj
               $material_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $material_item->setCreatorItem($user);
               $material_item->setCreationDate(getCurrentDateTimeInMySQL());
            }

            // Create new version button pressed
            if( isOption($command, getMessage('MATERIAL_VERSION_BUTTON')) ) {
               $new_version_id = $material_item->getVersionID()+1;
               $new_version = true;
               $material_item = $material_item->cloneCopy($new_version);
               $material_item->setVersionID($new_version_id);
               $infoBox_forAutoNewVersion = '';
            }

            // Material was edited by someone else since
            // the form was loaded. => Create a new version automatically
            elseif ( $_POST['modification_date'] != $material_item->getModificationDate() ) {
               $new_version_id = $material_item->getVersionID()+1;
               $new_version = true;
               $material_item = $material_item->cloneCopy($new_version);
               $material_item->setVersionID($new_version_id);
               $infoBox_forAutoNewVersion = "&autoVersion=true";
            }

            // Don't create a new version
            else {
               $infoBox_forAutoNewVersion = '';
            }

            // Set changed data from postvars
            if (isset($_POST['title']) and $material_item->getTitle() != $_POST['title']) {
               $material_item->setTitle($_POST['title']);
            }
            if (isset($_POST['author']) and $material_item->getAuthor() != $_POST['author']) {
               $material_item->setAuthor($_POST['author']);
            }
            if (isset($_POST['publishing_date']) and $material_item->getPublishingDate() != $_POST['publishing_date']) {
               $material_item->setPublishingDate($_POST['publishing_date']);
            }
            if (isset($_POST['bibliographic']) and $material_item->getBibliographicValues() != $_POST['bibliographic']) {
               $material_item->setBibliographicValues($_POST['bibliographic']);
            }
            if (isset($_POST['description']) and $material_item->getDescription() != $_POST['description']) {
               $material_item->setDescription($_POST['description']);
            }

            // Detail bibliographic values
            if ( isset($_POST['bib_kind']) and $material_item->getBibKind() != $_POST['bib_kind'] ) {
               $material_item->setBibKind($_POST['bib_kind']);
               $material_item->setBibliographicValues('');
            }
            if (isset($_POST['common']) and $material_item->getBibliographicValues() != $_POST['common']) {
               $material_item->setBibliographicValues($_POST['common']);
            }
            if ( isset($_POST['publisher']) and $material_item->getPublisher() != $_POST['publisher'] ) {
               $material_item->setPublisher($_POST['publisher']);
            }
            if ( isset($_POST['address']) and $material_item->getAddress() != $_POST['address'] ) {
               $material_item->setAddress($_POST['address']);
            }
            if ( isset($_POST['edition']) and $material_item->getEdition() != $_POST['edition'] ) {
               $material_item->setEdition($_POST['edition']);
            }
            if ( isset($_POST['series']) and $material_item->getSeries() != $_POST['series'] ) {
               $material_item->setSeries($_POST['series']);
            }
            if ( isset($_POST['volume']) and $material_item->getVolume() != $_POST['volume'] ) {
               $material_item->setVolume($_POST['volume']);
            }
            if ( isset($_POST['isbn']) and $material_item->getISBN() != $_POST['isbn'] ) {
               $material_item->setISBN($_POST['isbn']);
            }
            if ( isset($_POST['issn']) and $material_item->getISSN() != $_POST['issn'] ) {
               $material_item->setISSN($_POST['issn']);
            }
            if ( isset($_POST['editor']) and $material_item->getEditor() != $_POST['editor'] ) {
               $material_item->setEditor($_POST['editor']);
            }
            if ( isset($_POST['booktitle']) and $material_item->getBooktitle() != $_POST['booktitle'] ) {
               $material_item->setBooktitle($_POST['booktitle']);
            }
            if ( isset($_POST['pages']) and $material_item->getPages() != $_POST['pages'] ) {
               $material_item->setPages($_POST['pages']);
            }
            if ( isset($_POST['journal']) and $material_item->getJournal() != $_POST['journal'] ) {
               $material_item->setJournal($_POST['journal']);
            }
            if ( isset($_POST['issue']) and $material_item->getIssue() != $_POST['issue'] ) {
               $material_item->setIssue($_POST['issue']);
            }
            if ( isset($_POST['thesis_kind']) and $material_item->getThesisKind() != $_POST['thesis_kind'] ) {
               $material_item->setThesisKind($_POST['thesis_kind']);
            }
            if ( isset($_POST['university']) and $material_item->getUniversity() != $_POST['university'] ) {
               $material_item->setUniversity($_POST['university']);
            }
            if ( isset($_POST['faculty']) and $material_item->getFaculty() != $_POST['faculty'] ) {
               $material_item->setFaculty($_POST['faculty']);
            }
            if ( isset($_POST['url']) and $material_item->getURL() != $_POST['url'] ) {
               $material_item->setURL($_POST['url']);
            }
            if ( isset($_POST['url_date']) and $material_item->getURL() != $_POST['url_date'] ) {
               $material_item->setURLDate($_POST['url_date']);
            }
            if ( $context_item->isCommunityRoom() and $context_item->isOpenForGuests() ) {
               $old_world_public = $material_item->getWorldPublic();
               if ( ( isset($_POST['world_public']) and $old_world_public == 0) or
                    ( !isset($_POST['world_public']) and $old_world_public == 2 and !$current_user->isModerator())  ){               // Request for world public
                  $material_item->setWorldPublic(1);
                  $createATask = 'TASK_REQUEST_MATERIAL_WORLDPUBLIC';
               } elseif ( isset($_POST['world_public']) and $old_world_public == 1 ) {
                  $material_item->setWorldPublic(0);
                  $createATask = 'TASK_CANCEL_MATERIAL_WORLDPUBLIC';
               } elseif ( isset($_POST['world_public']) and $old_world_public == 2 ) {
                  $material_item->setWorldPublic(0);
                  $createATask = '';
               } else {
                  $createATask = '';
               }
            } else {
               $createATask = '';
            }

            // modificator
            $material_item->setModificatorItem($current_user);

            // buzzwords
      $buzzword_array = array();
      if ( isset($_POST['buzzwordlist']) ) {
         $buzzword_array = $_POST['buzzwordlist'];
      }
      if ( isset($_POST['buzzword']) and !in_array($_POST['buzzword'],$buzzword_array) and $_POST['buzzword'] > 0) {
         $buzzword_array[] = $_POST['buzzword'];
      }
      $material_item->setBuzzwordListByID($buzzword_array);
            // tags
      $tag_array = array();
      if ( isset($_POST['taglist']) ) {
         $tag_array = $_POST['taglist'];
      }
      if ( isset($_POST['tag']) and !in_array($_POST['tag'],$tag_array) and $_POST['tag'] > 0) {
         $tag_array[] = $_POST['tag'];
      }
      $material_item->setTagListByID($tag_array);





            // Files
            $item_files_upload_to = $material_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            if ( isset($_POST[CS_TOPIC_TYPE]) ) {
               $material_item->setTopicListByID($_POST[CS_TOPIC_TYPE]);
            } else {
               $material_item->setTopicListByID(array());
            }

            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $material_item->setLinkedItemsByID(CS_MATERIAL_TYPE,$_POST[CS_MATERIAL_TYPE]);
            } else {
               $material_item->setLinkedItemsByID(CS_MATERIAL_TYPE,array());
            }

            if ( isset($_POST[CS_ANNOUNCEMENT_TYPE]) ) {
               $material_item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,$_POST[CS_ANNOUNCEMENT_TYPE]);
            } else {
               $material_item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,array());
            }

            if ( isset($_POST[CS_DATE_TYPE]) ) {
               $material_item->setLinkedItemsByID(CS_DATE_TYPE,$_POST[CS_DATE_TYPE]);
            } else {
               $material_item->setLinkedItemsByID(CS_DATE_TYPE,array());
            }

            if ( isset($_POST[CS_TODO_TYPE]) ) {
               $material_item->setLinkedItemsByID(CS_TODO_TYPE,$_POST[CS_TODO_TYPE]);
            } else {
               $material_item->setLinkedItemsByID(CS_TODO_TYPE,array());
            }

            if ( isset($_POST[CS_DISCUSSION_TYPE]) ) {
               $material_item->setLinkedItemsByID(CS_DISCUSSION_TYPE,$_POST[CS_DISCUSSION_TYPE]);
            } else {
               $material_item->setLinkedItemsByID(CS_DISCUSSION_TYPE,array());
            }


            if ( isset($_POST[CS_PROJECT_TYPE]) ) {
               $material_item->setProjectListByID($_POST[CS_PROJECT_TYPE]);
            } else {
               $material_item->setProjectListByID(array());
            }

            if ( $context_item->isProjectRoom() ) {

               if ( isset($_POST[CS_GROUP_TYPE]) ) {
                  $material_item->setGroupListByID($_POST[CS_GROUP_TYPE]);
               } else {
                  $material_item->setGroupListByID(array());
               }

            } else {

               // Institutions
               if ( isset($_POST[CS_INSTITUTION_TYPE]) ) {
                  $material_item->setInstitutionListByID($_POST[CS_INSTITUTION_TYPE]);
               } else {
                  $material_item->setInstitutionListByID(array());
               }

            }

/*** Neue Schlagwörter und Tags***/
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
         $material_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
         $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
      }
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
         $material_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
         $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
      }
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
         $material_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
         $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      }
/*** Neue Schlagwörter und Tags***/


            if ( isset($_POST['public']) ) {
               if ( $material_item->isPublic() != $_POST['public'] ) {
                  $material_item->setPublic($_POST['public']);
               }
            } else {
               if ( isset($_POST['private_editing']) ) {
                  $material_item->setPrivateEditing('0');
               } else {
                  $material_item->setPrivateEditing('1');
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
                $material_item->setModificationDate($dt_hiding_datetime);
            }else{
               if($material_item->isNotActivated()){
                  $material_item->setModificationDate(getCurrentDateTimeInMySQL());
               }
            }




            if ( !$error ) {

               // Save material
               $material_item->save();

               // send notifications if world public status is requested
               if ( $material_item->getWorldPublic() == 1
                    and isset($context_item)
                    and $context_item->isCommunityRoom()
                  ) {

                  // Get receiving moderators
                  $modList = $context_item->getModeratorList();
                  $moderator = $modList->getFirst();
                  $mailSendTo = '';
                  while ( $moderator ) {
                     if ( $moderator->getPublishMaterialWantMail() == 'yes' ) {
                        $mailSendTo .= $moderator->getFullName().LF;
                     }
                     $moderator = $modList->getNext();
                  }

                  // Send mails // Warum werden die einzeln verschickt ???
                  $moderator = $modList->getFirst();
                  $translator = $environment->getTranslationObject();
                  while ( $moderator ) {
                     if ( $moderator->getPublishMaterialWantMail() == 'yes' ) {
                        include_once('classes/cs_mail.php');
                        $mail = new cs_mail();
                        $sender = $material_item->getModificatorItem();
                        $mail->set_from_name($sender->getFullName());
                        $mail->set_from_email($sender->getEMail());
                        $mail->set_reply_to_name($sender->getFullName());
                        $mail->set_reply_to_email($sender->getEMail());
                        $mail->set_to($moderator->getEMail());
                        $language = $moderator->getLanguage();
                        $translator->setSelectedLanguage($language);
                        $mail_subject = $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_SUBJECT',$context_item->getTitle());
                        $mail_body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $mail_body.= "\n\n";
                        $mail_body.= $translator->getMessage('ADMIN_MAIL_MATERIAL_SHOULD_BE_WORLDPUBLIC_BODY',$material_item->getTitle(),$context_item->getTitle(),$sender->getFullName());
                        $mail_body.= "\n\n";
                        $mail_body.= $translator->getMessage('MAIL_SEND_TO',$mailSendTo);
                        $mail_body.= "\n";
                        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID().'&mod=material_admin&fct=index&iid='.$material_item->getItemID().'&selstatus=1';
                        $mail_body.= $url;
                        $mail->set_subject($mail_subject);
                        $mail->set_message($mail_body);
                        $mail->send();
                     }
                     $moderator = $modList->getNext();
                  }
               }

               // Create tasks for world public status
               if ( $createATask == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC' ) {
                  $task_manager = $environment->getTaskManager();
                  $task_item = $task_manager->getNewItem();
                  $task_item->setTitle('TASK_REQUEST_MATERIAL_WORLDPUBLIC');
                  $task_item->setStatus('REQUEST');
                  $user = $environment->getCurrentUserItem();
                  $task_item->getCreatorItem($user);
                  $task_item->setItem($material_item);
                  $task_item->save();
               } elseif ( $createATask == 'TASK_CANCEL_MATERIAL_WORLDPUBLIC' ) {
                  $task_manager = $environment->getTaskManager();

                  // Close any open requests
                  $task_list = $task_manager->getTaskListForItem($material_item);
                  if ( !$task_list->isEmpty() ) {
                     $task_item = $task_list->getFirst();
                     while ( $task_item ) {
                        if ( $task_item->getStatus() == 'REQUEST'
                             and $task_item->getTitle() == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC' ) {
                           $task_item->setStatus('CLOSED');
                           $task_item->save();
                        }
                        $task_item = $task_list->getNext();
                     }
                  }

                  // Create new task
                  $task_item = $task_manager->getNewItem();
                  $task_item->setTitle('TASK_CANCEL_MATERIAL_WORLDPUBLIC');
                  $task_item->setStatus('CLOSED');
                  $user = $environment->getCurrentUserItem();
                  $task_item->getCreatorItem($user);
                  $task_item->setItem($material_item);
                  $task_item->save();
               }

               // Reset id array
               $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids',
                                  array($material_item->getItemID()));

               // Redirect
               cleanup_session($current_iid);
/*** Neue Schlagwörter und Tags***/
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
               $session->unsetValue('buzzword_post_vars');
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
               $session->unsetValue('tag_post_vars');
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
               $session->unsetValue('linked_items_post_vars');
/*** Neue Schlagwörter und Tags***/
               $params = array();
               $params['iid'] = $material_item->getItemID();
               if (!empty($infoBox_forAutoNewVersion)) {
                  $params['autoVersion'] = 'true';
               }
               redirect($environment->getCurrentContextID(), 'material', 'detail', $params);
            }
         }
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

   if (isset($focus_element_onload)) {
     $form_view->setFocusElementOnLoad($focus_element_onload);
   }
   if (isset($focus_element_anchor)) {
     $form_view->setFocusElementAnchor($focus_element_anchor);
   }

   if (!mayEditRegular($current_user, $material_item)) {
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
   $form_view->setAction(curl($environment->getCurrentContextID(),'material','edit',''));
   $form_view->setForm($form);
   if (isset($material_item)){
      $form_view->setItem($material_item);
   }
   $form_view->setForm($form);
   $page->add($form_view);
}
?>