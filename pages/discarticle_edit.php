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
   $discarticle_item = NULL;
} else {
   $discarticle_manager = $environment->getDiscussionArticlesManager();
   $discarticle_item = $discarticle_manager->getItem($current_iid);
}
// Find the discussion this article belongs to
if ( $current_iid != 'NEW' ) {
   $discussion_id = $discarticle_item->getDiscussionID();
   $ref_position = $discarticle_item->getPosition();
} else {
   if ( !empty($_GET['did']) ) {
      $discussion_id = $_GET['did'];
   } elseif ( !empty($_POST['discussion_id']) ) {
      $discussion_id = $_POST['discussion_id'];
   } elseif ( !empty($_GET['discussion_id']) ) {
      $discussion_id = $_GET['discussion_id'];
   } else {
      if ( $session->issetValue($current_iid.'_post_vars') ) {
         $session_postvars = $session->getValue($current_iid.'_post_vars');
         if ( isset($session_postvars['discussion_id']) ) {
            $discussion_id = $session_postvars['discussion_id'];
         } else {
            include_once('functions/error_functions.php');trigger_error('A discussion id must be given for new discussion articles.', E_USER_ERROR);
         }
      } elseif ( $session->issetValue($environment->getCurrentModule().'_multi_upload_post_vars') ) {
         $session_postvars = $session->getValue($environment->getCurrentModule().'_multi_upload_post_vars');
         if ( isset($session_postvars['discussion_id']) ) {
            $discussion_id = $session_postvars['discussion_id'];
         } else {
            include_once('functions/error_functions.php');trigger_error('Lost discussion id for discussion articles.', E_USER_ERROR);
         }
      } else {
         include_once('functions/error_functions.php');trigger_error('A discussion id must be given for new discussion articles.', E_USER_ERROR);
      }
   }
   $discussion_manager = $environment->getDiscussionManager();
   $discussion = $discussion_manager->getItem($discussion_id);
   $discussion_type = $discussion->getDiscussionType();
   $ref_position = '1';
   if ( $discussion_type == 'threaded' ) {
      if ( !empty($_GET['ref_position']) ) {
         $ref_position = $_GET['ref_position'];
      } elseif ( !empty($_POST['ref_position']) ) {
         $ref_position = $_POST['ref_position'];
      } elseif ( !empty($_GET['ref_position']) ) {
         $ref_position = $_GET['ref_position'];
      } else {
         if ( $session->issetValue($current_iid.'_post_vars') ) {
            $session_postvars = $session->getValue($current_iid.'_post_vars');
            if ( isset($session_postvars['ref_position']) ) {
               $ref_position = $session_postvars['ref_position'];
            } else {
               include_once('functions/error_functions.php');trigger_error('A ref_position id must be given for new discussion articles.', E_USER_ERROR);
            }
         } elseif ( $session->issetValue($environment->getCurrentModule().'_multi_upload_post_vars') ) {
            $session_postvars = $session->getValue($environment->getCurrentModule().'_multi_upload_post_vars');
            if ( isset($session_postvars['ref_position']) ) {
               $ref_position = $session_postvars['ref_position'];
            } else {
               include_once('functions/error_functions.php');trigger_error('A ref_position id must be given for new discussion articles.', E_USER_ERROR);
            }
         } else {
            include_once('functions/error_functions.php');trigger_error('A ref_position id must be given for new discussion articles.', E_USER_ERROR);
         }
      }
      if ( !empty($_GET['ref_did']) ) {
         $ref_did = $_GET['ref_did'];
      } elseif ( !empty($_POST['ref_did']) ) {
         $ref_did = $_POST['ref_did'];
      }elseif ( !empty($_GET['ref_did']) ) {
         $ref_did = $_GET['ref_did'];
      }
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
} elseif ( $current_iid != 'NEW' and !isset($discarticle_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($discarticle_item) and
              $discarticle_item->mayEdit($current_user))) ) {
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
       if (isset($discarticle_item) and !empty($discarticle_item)){
         $discarticle_id = 'anchor'.$discarticle_item->getItemID();
      } else {
         $discarticle_id = '';
      }
      cleanup_session($current_iid);
      if ( $current_iid == 'NEW' and empty($discussion_id) ) {
         redirect($environment->getCurrentContextID(), 'discussion', 'index', '');
      } else {
         $params = array();
         $params['iid'] = $discussion_id;
         redirect($environment->getCurrentContextID(), 'discussion', 'detail', $params, $discarticle_id);
      }
   }

   // Show form and/or save item
   else {
      // Handle requests from discussion_detail_view   
      if(   isset($_GET['back_to_discussion_detail_view']) &&
            !empty($command) &&
               !(isOption($command, $translator->getMessage('DISCARTICLE_SAVE_BUTTON')) ||
               isOption($command, $translator->getMessage('DISCARTICLE_CHANGE_BUTTON')))
            ) {
         $session_item = $environment->getSessionItem();
         
         if(   (!$session_item->issetValue($environment->getCurrentModule().'_add_files') &&
               isset($discarticle_item))) {
	        // get files from database
	        $file_list = $discarticle_item->getFileList();
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
         
         include_once('include/inc_fileupload_edit_page_handling.php');
         
         // set session post vars
         $session_post_vars = $_POST;
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
           
         $session_item->setValue('back_to_discussion_detail_view_postvars', $session_post_vars);
         
         if(isset($discarticle_item)) {
            $session_item->setValue('back_to_discussion_detail_view_last_upload', "edit" . $discarticle_item->getItemID());
         } else {
            $session_item->setValue('back_to_discussion_detail_view_last_upload', "new" . $_GET['answer_to']);
         }
         
         
         // Redirect
         //cleanup_session($current_iid);
         $params = array();
         $params['iid'] = $_POST['discussion_id'];
         $anchor = '';
         
         if($_GET['back_to_discussion_detail_view'] == 'new') {
            // new
            $params['ref_position'] = $_POST['ref_position'];
            $params['answer_to'] = $_GET['answer_to'];
         } else {
            // edit
            $params['discarticle_action'] = 'edit';
            $params['discarticle_iid'] = $discarticle_item->getItemID();
         }
         
         redirect(   $environment->getCurrentContextID(),
                     'discussion',
                     'detail',
                     $params,
                     'discarticle_form');
      }
      
      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(DISCARTICLE_FORM,$class_params);
      unset($class_params);
      $form->setDiscussionID($discussion_id);
      $form->setRefPosition($ref_position);
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
      elseif ( isset($discarticle_item) ) {
         $form->setItem($discarticle_item);

         // Files
         $file_list = $discarticle_item->getFileList();
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
         include_once('functions/error_functions.php');trigger_error('discarticle_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('DISCARTICLE_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('DISCARTICLE_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct ) {

            // Create new item
            if ( !isset($discarticle_item) ) {
               $discarticle_manager = $environment->getDiscussionArticlesManager();
               $discarticle_item = $discarticle_manager->getNewItem();
               $discarticle_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $discarticle_item->setCreatorItem($user);
               $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
               $discarticle_item->setDiscussionID($discussion_id);


               $discussion_manager = $environment->getDiscussionManager();
               $discussion_item = $discussion_manager->getItem($discussion_id);
               $discussion_type = $discussion_item->getDiscussionType();
               if ($discussion_type=='threaded'){
                  // Load discussion articles
                  $discussionarticles_manager = $environment->getDiscussionArticlesManager();
                  $discussionarticles_manager->setDiscussionLimit($discussion_id,'');
                  $discussionarticles_manager->select();
                  $articles_list = $discussionarticles_manager->get();
                  $article = $articles_list->getFirst();
                  $position_array = array();
                  while($article){
                     $article_position = $article->getPosition();
                     if ($article_position > $ref_position){
                        $position_array[]= $article_position;
                     }
                     $article= $articles_list->getNext();
                  }
                  sort($position_array);
                  $in = in_array($ref_position.'.1001',$position_array);
                  if (!$in){
                     $discarticle_item->setPosition($ref_position.'.1001');
                  } else {
                     $ref_pos_array = explode('.',$ref_position);
                     $compare_array = array();
                     $end = count($position_array)-1;
                     for ($i = 0; $i <= $end; $i++){
                        $value_array = explode('.',$position_array[$i]);
                        $in = true;
                        $end2 = count($ref_pos_array)-1;
                        for ($j = 0; $j <= $end2; $j++){
                           if ( isset($value_array[$j])
                                and $ref_pos_array[$j] != $value_array[$j]){
                              $in = false;
                           }
                        }
                        if ($in and count($value_array) == count($ref_pos_array)+1){
                           $compare_array[] = $value_array[count($ref_pos_array)];
                        }
                     }
                     $lenght = count($compare_array)-1;
                     $result = $compare_array[$lenght];
                     $end_result = $result+1;
                     $discarticle_item->setPosition($ref_position.'.'.$end_result);
                  }
               } else {
                  $discarticle_item->setPosition('1');
               }
            }
            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $discarticle_item->setModificatorItem($user);
            $discarticle_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if (isset($_POST['subject'])) {
               $discarticle_item->setSubject($_POST['subject']);
            }
            if (isset($_POST['description'])) {
               $discarticle_item->setDescription($_POST['description']);
            }

            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $discarticle_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
            } else {
               $discarticle_item->setMaterialListByID(array());
            }

            $item_files_upload_to = $discarticle_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            // Save item
            $discarticle_item->save();

            // Redirect
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $discarticle_item->getDiscussionID();
            redirect($environment->getCurrentContextID(),
                     'discussion', 'detail', $params,'anchor'.$discarticle_item->getItemID());
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
      $discussion_manager = $environment->getDiscussionManager();
      if ( isset($discarticle_item) ){
         $discussion_item = $discussion_manager->getItem($discarticle_item->getDiscussionID());
         if (!mayEditRegular($current_user, $discarticle_item)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'discarticle','edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}

?>