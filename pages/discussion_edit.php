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


function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);
   if ( isset($_POST[$rubric_type]) ) {
      $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
   } else {
      $session->setValue($current_iid.$infix.'_attach_ids', array());
   }
   $session->setValue($current_iid.$infix.'_back_module', 'discussion');
   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params);
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
   $session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
   $session->unsetValue($environment->getCurrentModule().'_add_tags');
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_group_attach_ids');
   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_institution_attach_ids');
   $session->unsetValue($current_iid.'_group_back_module');
   $session->unsetValue($current_iid.'_topic_back_module');
}

// Get the current user and context
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

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
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( $current_iid != 'NEW' and !isset($discussion_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($discussion_item) and
              $discussion_item->mayEditIgnoreClose($current_user))) ) {
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
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
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

      // Redirect to attach PROJECT
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_PROJECT_BUTTON')) ) {
         attach_redirect(CS_PROJECT_TYPE, $current_iid);
      }


      // Redirect to attach groups
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_GROUP_BUTTON')) ) {
         attach_redirect(CS_GROUP_TYPE, $current_iid);
      }

      // Redirect to attach topics
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON')) ) {
         attach_redirect(CS_TOPIC_TYPE, $current_iid);
      }

      // Redirect to attach institution
      if ( isOption($command, getMessage('RUBRIC_DO_ATTACH_INSTITUTION_BUTTON')) ) {
         attach_redirect(CS_INSTITUTION_TYPE, $current_iid);
      }
      include_once('include/inc_right_boxes_handling.php');

      // Add a new buzzword
      if ( isOption($command, getMessage('COMMON_ADD_BUZZWORD_BUTTON')) or isOption($command, getMessage('COMMON_NEW_BUZZWORD_BUTTON')) ) {
         $focus_element_onload = 'buzzword';
         $post_buzzword_ids = array();
         $new_buzzword_ids = array();
         if ( isset($_POST['buzzwordlist']) ) {
            $post_buzzword_ids = $_POST['buzzwordlist'];
         }
         if ( $session->issetValue($environment->getCurrentModule().'_add_buzzwords') ) {
            $buzzword_array = $session->getValue($environment->getCurrentModule().'_add_buzzwords');
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
            $session->setValue($environment->getCurrentModule().'_add_buzzwords', $buzzword_array);
         } else {
            $session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
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
         if ( $session->issetValue($environment->getCurrentModule().'_add_tags') ) {
            $tag_array = $session->getValue($environment->getCurrentModule().'_add_tags');
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
            $session->setValue($environment->getCurrentModule().'_add_tags', $tag_array);
         } else {
            $session->unsetValue($environment->getCurrentModule().'_add_tags');
         }
         $post_tag_ids = array_merge($post_tag_ids, $new_tag_ids);
      }

      // Load form data from postvars
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
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

      // Back from attaching groups
      elseif ( $backfrom == CS_GROUP_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_GROUP_TYPE, $current_iid);
       $with_anchor = true;
         $session_post_vars[CS_GROUP_TYPE] = $attach_ids;
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
      elseif ( isset($discussion_item) ) {
         $form->setItem($discussion_item);
         // Buzzwords
         $buzzword_list = $discussion_item->getBuzzwordList();
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
               $session->setValue($environment->getCurrentModule().'_add_buzzwords', $buzzword_array);
            }
         }
         // Tags
         $tag_list = $discussion_item->getTagList();
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
               $session->setValue($environment->getCurrentModule().'_add_tags', $tag_array);
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
      if ($session->issetValue($environment->getCurrentModule().'_add_buzzwords')) {
         $form->setSessionBuzzwordArray($session->getValue($environment->getCurrentModule().'_add_buzzwords'));
      }
      if ($session->issetValue($environment->getCurrentModule().'_add_tags')) {
         $form->setSessionTagArray($session->getValue($environment->getCurrentModule().'_add_tags'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, getMessage('DISCUSSIONS_SAVE_BUTTON'))
            or isOption($command, getMessage('DISCUSSIONS_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct ) {

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

            // Set links to connected rubrics
            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_MATERIAL_TYPE,$_POST[CS_MATERIAL_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_MATERIAL_TYPE,array());
            }

            if ( isset($_POST[CS_ANNOUNCEMENT_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,$_POST[CS_ANNOUNCEMENT_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,array());
            }

            if ( isset($_POST[CS_DATE_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_DATE_TYPE,$_POST[CS_DATE_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_DATE_TYPE,array());
            }

            if ( isset($_POST[CS_TODO_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_TODO_TYPE,$_POST[CS_TODO_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_TODO_TYPE,array());
            }

            if ( isset($_POST[CS_DISCUSSION_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_DISCUSSION_TYPE,$_POST[CS_DISCUSSION_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_DISCUSSION_TYPE,array());
            }

            if ( isset($_POST[CS_PROJECT_TYPE]) ) {
               $discussion_item->setLinkedItemsByID(CS_PROJECT_TYPE,$_POST[CS_PROJECT_TYPE]);
            } else {
               $discussion_item->setLinkedItemsByID(CS_PROJECT_TYPE,array());
            }

            if ( isset($_POST[CS_GROUP_TYPE]) ) {
               $discussion_item->setGroupListByID($_POST[CS_GROUP_TYPE]);
            } else {
               $discussion_item->setGroupListByID(array());
            }
            if ( isset($_POST[CS_TOPIC_TYPE]) ) {
               $discussion_item->setTopicListByID($_POST[CS_TOPIC_TYPE]);
            } else {
               $discussion_item->setTopicListByID(array());
            }
            if ($environment->inCommunityRoom()) {
               if ( isset($_POST[CS_INSTITUTION_TYPE]) ) {
                  $discussion_item->setInstitutionListByID($_POST[CS_INSTITUTION_TYPE]);
               } else {
                  $discussion_item->setInstitutionListByID(array());
               }
            }
            // buzzwords
            $buzzword_array = array();
            if ( isset($_POST['buzzwordlist']) ) {
               $buzzword_array = $_POST['buzzwordlist'];
            }
            if ( isset($_POST['buzzword']) and !in_array($_POST['buzzword'],$buzzword_array) and $_POST['buzzword'] > 0) {
               $buzzword_array[] = $_POST['buzzword'];
            }
            $discussion_item->setBuzzwordListByID($buzzword_array);

            // tags
            $tag_array = array();
            if ( isset($_POST['taglist']) ) {
               $tag_array = $_POST['taglist'];
            }
            if ( isset($_POST['tag']) and !in_array($_POST['tag'],$tag_array) and $_POST['tag'] > 0) {
               $tag_array[] = $_POST['tag'];
            }
            $discussion_item->setTagListByID($tag_array);
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

            // Reset id array
            $session->setValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids',
                               array($discussion_item->getItemID()));

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
         $errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->add($errorbox);
      }
      $form_view->setAction(curl($environment->getCurrentContextID(),'discussion','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>