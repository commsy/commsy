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
if(isset($_GET['mylist_id'])){
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id',$_GET['mylist_id']);
}

// Function used for redirecting to connected rubrics
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
// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
   $session->unsetValue($environment->getCurrentModule().'_add_tags');
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
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
$with_anchor = false;

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   $discussion_item = NULL;
} else {
   $discussion_manager = $environment->getDiscussionManager();
   $discussion_item = $discussion_manager->getItem($current_iid);
   if(empty($_POST)){
      $buzzword_array = array();
      $buzzwords = $discussion_item->getBuzzwordList();
      $buzzword = $buzzwords->getFirst();
      while($buzzword){
         $buzzword_array[] = $buzzword->getItemID();
         $buzzword = $buzzwords->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids',$buzzword_array);
   }
   if(empty($_POST)){
      $tag_array = array();
      $tags = $discussion_item->getTagList();
      $tag = $tags->getFirst();
      while($tag){
         $tag_array[] = $tag->getItemID();
         $tag = $tags->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids',$tag_array);
   }
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $discussion_item->getAllLinkedItemIDArray();
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
} elseif ( $current_iid != 'NEW' and !isset($discussion_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($discussion_item) and
              $discussion_item->mayEditIgnoreClose($current_user))) ) {
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
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
      $session->unsetValue('buzzword_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
      $session->unsetValue('tag_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
      cleanup_session($current_iid);
      if ( $current_iid == 'NEW' ) {
         redirect($environment->getCurrentContextID(), 'discussion', 'index', '');
      } else {
         $params = array();
         $params['iid'] = $current_iid;
         redirect($environment->getCurrentContextID(), 'discussion', 'detail', $params);
      }
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(DISCUSSION_FORM,$class_params);
      unset($class_params);

      include_once('include/inc_fileupload_edit_page_handling.php');
      include_once('include/inc_right_boxes_handling.php');
      // Load form data from postvars
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
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
      // Load form data from database
      elseif ( isset($discussion_item) ) {
         $form->setItem($discussion_item);

         // Files
         $file_list = $discussion_item->getFileList();
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
         include_once('functions/error_functions.php');trigger_error('discussion_edit was called in an unknown manner', E_USER_ERROR);
      }

      // If it is a new discussion, also show the text field for
      // the initial discussion article
      if ( $current_iid == 'NEW' ) {
         $form->setNewDiscussion(true);
      } else {
         $form->setNewDiscussion(false);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('DISCUSSIONS_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('DISCUSSIONS_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct ) {
            $item_is_new = false;

            // New Item?
            if ( !isset($discussion_item) ) {
              $new_discussion = true;
            } else {
              $new_discussion = false;
            }

            if ($new_discussion) {
               $discussion_manager = $environment->getDiscussionManager();
               $discussion_item = $discussion_manager->getNewItem();
               $discussion_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $discussion_item->setCreatorItem($user);
               $discussion_item->setCreationDate(getCurrentDateTimeInMySQL());
               $item_is_new = true;
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $discussion_item->setModificatorItem($user);

            // Set attributes
            if ( isset($_POST['title']) ) {
               $discussion_item->setTitle($_POST['title']);
            }
            if ( isset($_POST['public']) ) {
               if ( $discussion_item->isPublic() != $_POST['public'] ) {
                  $discussion_item->setPublic($_POST['public']);
               }
            } else {
               if ( isset($_POST['private_editing']) ) {
                  $discussion_item->setPrivateEditing('0');
               } else {
                  $discussion_item->setPrivateEditing('1');
               }
            }
            if ( isset($_POST['external_viewer']) and isset($_POST['external_viewer_accounts']) ) {
               $user_ids = explode(" ",$_POST['external_viewer_accounts']);
               $discussion_item->setExternalViewerAccounts($user_ids);
            }else{
               $discussion_item->unsetExternalViewerAccounts();
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
                $discussion_item->setModificationDate($dt_hiding_datetime);
            }else{
               if($discussion_item->isNotActivated()){
                  $discussion_item->setModificationDate(getCurrentDateTimeInMySQL());
               }
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
               $discussion_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
               $discussion_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $discussion_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }

            // Save item
            $discussion_item->save();
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
               $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
            }else{
               $id_array =  array();
            }
            if ($item_is_new){
               $id_array[] = $discussion_item->getItemID();
               $id_array = array_reverse($id_array);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
           }

            // Also save the initial discussion article
            if ( $new_discussion ) {
               $discarticle_manager = $environment->getDiscussionArticlesManager();
               $discarticle_item = $discarticle_manager->getNewItem();
               $discarticle_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $discarticle_item->setCreatorItem($user);
               $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
               $discarticle_item->setDiscussionID($discussion_item->getItemId());
               if (isset($_POST['subject'])) {
                  $discarticle_item->setSubject($_POST['subject']);
               }
               if ( isset($_POST['description'])) {
                  $discarticle_item->setDescription($_POST['description']);
               }
                  if (isset($_POST['discussion_type']) and $_POST['discussion_type']==2){
                    $discarticle_item->setPosition('1');
                  }
               $item_files_upload_to = $discarticle_item;
               include_once('include/inc_fileupload_edit_page_save_item.php');

               $discarticle_item->save();

               // ... and update the discussion item
               $discussion_item->setLatestArticleID($discarticle_item->getItemID());
               $discussion_item->setLatestArticleModificationDate($discarticle_item->getCreationDate());
               $discussion_status = $context_item->getDiscussionStatus();
               if ($discussion_status == 3){
                  if ($_POST['discussion_type']==2){
                     $discussion_item->setDiscussionType('threaded');
                  }else{
                     $discussion_item->setDiscussionType('simple');
                  }
               }elseif($discussion_status == 2){
                  $discussion_item->setDiscussionType('threaded');
               }else{
                  $discussion_item->setDiscussionType('simple');
               }
               $discussion_item->save();
            }

            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id')){
               $mylist_manager = $environment->getMylistManager();
               $mylist_item = $mylist_manager->getItem($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id'));
               $id_array = $mylist_item->getAllLinkedItemIDArrayLabelVersion();
               if (!in_array($discussion_item->getItemID(),$id_array)){
                  $id_array[] =  $discussion_item->getItemID();
               }
               $mylist_item->saveLinksByIDArray($id_array);
            }
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id');

            // Redirect
            cleanup_session($current_iid);
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
            $session->unsetValue('buzzword_post_vars');
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
            $session->unsetValue('tag_post_vars');
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');
            $params = array();
            $params['iid'] = $discussion_item->getItemID();;
            redirect($environment->getCurrentContextID(),
                     'discussion', 'detail', $params);
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
      if (!mayEditRegular($current_user, $discussion_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'discussion','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>