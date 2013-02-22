<?php
	require_once('classes/controller/cs_edit_controller.php');

	class cs_discussion_edit_controller extends cs_edit_controller {
		private $_item = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'discussion_edit';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_DISCUSSION_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionEdit() {
			//$this->assign('detail', 'content', $this->getDetailContent());
			
			$session = $this->_environment->getSessionItem();
			
			// get post data
			$this->getPostData();
			
			
			/*
			 * 
			 * // Linked item from "NEW" dropdown-menu
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
			 */
			
			
			
			
			
			
			
			
			
			
			
			if($this->_item_id !== null) {
				$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
				$this->_item = $discarticle_manager->getItem($this->_item_id);
			}
			
			
			
			
			
			
			
			
			
			
			
			
			
			// find the discussion this article belongs to
			if($this->_item !== NULL) {
				$discussion_id = $this->_item->getDiscussionID();
				$ref_position = $this->_item->getPosition();
			} else {
				if(!empty($_GET['did'])) {
					$discussion_id = $_GET['did'];
				} elseif(!empty($_POST['discussion_id'])) {
					$discussion_id = $_POST['discussion_id'];
				} elseif(!empty($_GET['discussion_id'])) {
					$discussion_id = $_GET['discussion_id'];
				} else {
					/*
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
					 */
				}
				/*
				$discussion_manager = $this->_environment->getDiscussionManager();
				$discussion = $discussion_manager->getItem($discussion_id);
				/*
				 * 

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
				 */
			}
			/*
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
				*/
				
				$translator = $this->_environment->getTranslationObject();
				
				// cancel editing
				if(isOption($this->_command, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
					/*
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
					 */
				// show form and/or save item
				} else {
					if(	isset($_GET['back_to_discussion_detail_view']) &&
					//TODO: command is not defined, use command from parent class($this->_command)
						!empty($command) &&
							!(isOption($command, $translator->getMessage('DISCARTICLE_SAVE_BUTTON')) ||
							isOption($command, $translator->getMessage('DISCARTICLE_CHANGE_BUTTON')))) {	
					/*
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
						*/
				
						$post_file_ids = array();
						if(isset($_POST['filelist'])) {
							$post_file_ids = $_POST['filelist'];
						}
						
						// set session post vars
						$session_post_vars = $_POST;
						if(isset($post_file_ids) && !empty($post_file_ids)) {
							$session_post_vars['filelist'] = $post_file_ids;
						}
						
						$session_item->setValue('back_to_discussion_detail_view_postvars', $session_post_vars);
						
						if(isset($discarticle_item)) {
							$session_item->setValue('back_to_discussion_detail_view_last_upload', 'edit' . $discarticle_item->getItemID());
						} else {
							$session_item->setValue('back_to_discussion_detail_view_last_upload', 'new' . $_GET['answer_to']);
						}
					
					// redirect
					//cleanup_session($current_iid);
					
					/*
					 * 
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
					 * 
					 * 
					 */
					
					
					}
					/*

         

      
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
*/
					
					$post_file_ids = array();
					if(isset($_POST['filelist'])) {
						$post_file_ids = $_POST['filelist'];
					}

					// load form data from postvars
					if(!empty($_POST)) {
						$session_post_vars = $_POST;
						if(isset($post_file_ids) && !empty($post_file_ids)) {
							$session_post_vars['filelist'] = $post_file_ids;
						}
						//$form->setFormPost($session_post_vars);
					}
					
					// back from multi upload
					/*
					elseif($from_multiupload) {
					$session_post_vars = array();
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
         }
					 */
					
					// load form data from database
					elseif(isset($this->_item)) {
						/*
						 * 
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
						 */
					}
					
					// create data for new item
					elseif($this->_item_id === null) {
						$this->cleanup_session($this->_item_id);
					} else {
						include_once('functions/error_functions.php');trigger_error('discarticle_edit was called in an unknown manner', E_USER_ERROR);
					}
					
					if($session->issetValue($this->_environment->getCurrentModule() . '_add_files')) {
						//$form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
						//die("UPLOAD");
					}
					
					// save item
					if($this->_command === 'new') {
						// TODO: implement form check
						$correct = true;
						if($correct) {
							// create new item
							if(!isset($this->_item)) {
								$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
								$discarticle_item = $discarticle_manager->getNewItem();
								$discarticle_item->setContextID($this->_environment->getCurrentContextID());
								$user = $this->_environment->getCurrentUserItem();
								$discarticle_item->setCreatorItem($user);
								$discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
								$discarticle_item->setDiscussionID($discussion_id);
								
								$discussion_manager = $this->_environment->getDiscussionManager();
								$discussion_item = $discussion_manager->getItem($discussion_id);
								$discussion_type = $discussion_item->getDiscussionType();
								if($discussion_type === 'threaded') {
									// load discussion articles
									/*
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
									 */
								} else {
									$discarticle_item->setPosition('1');
								}
							}
							
							// set modificator and modification date
							$user = $this->_environment->getCurrentUserItem();
							$discarticle_item->setModificatorItem($user);
							$discarticle_item->setModificationDate(getCurrentDateTimeInMySQL());
							
							// set attributes
							if(isset($_POST['form_data']['title'])) {
								$discarticle_item->setSubject($_POST['form_data']['title']);
							}
							if(isset($_POST['form_data']['description'])) {
								$discarticle_item->setDescription($_POST['form_data']['description']);
							}
							
							// set links to connected rubrics
							if(isset($_POST[CS_MATERIAL_TYPE])) {
								$discarticle_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
							} else {
								$discarticle_item->setMaterialListByID(array());
							}
							
							$this->setFilesForItem($discarticle_item, $post_file_ids);
							
							// save item
							$discarticle_item->save();
							
							// redirect
							$this->cleanup_session($this->_item_id);
							$params = array();
							$params['iid'] = $discarticle_item->getDiscussionID();
							redirect($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, 'disc_article_' . $discarticle_item->getItemID());
						}
					}
					
					
					
/*


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
      $page->add($form_view);*/
				}
			
			/*
			}
			

			 */
			
				
				
				
				
			// mark as read and noticed
			//$this->markRead();
			//$this->markNoticed();
			
			// set list actions
			//$this->assign('list', 'actions', $this->getListActions());

			/*
			// set paging information
			$paging = array(
				'num_pages'		=> ceil($this->_num_entries / $this->_paging['limit']),
				'actual_page'	=> floor($this->_paging['offset'] / $this->_paging['limit']) + 1,
				'from'			=> $this->_paging['offset'] + 1,
				'to'			=> $this->_paging['offset'] + $this->_paging['limit']
			);
			$this->assign('list', 'paging', $paging);
			$this->assign('list', 'num_entries', $this->_num_entries);
			*/
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function getDetailContent() {
			/*
			$disc_articles = $this->getDiscArticleContent();
			
			$return = array(
				'discussion'		=> $this->getDiscussionContent(),
				'disc_articles'		=> $disc_articles,
				'new_num'			=> count($disc_articles) + 1
			);
			
			return $return;
			*/
		}
		
		private function cleanup_session($current_iid) {
			$session = $this->_environment->getSessionItem();
			$session->unsetValue($this->_environment->getCurrentModule().'_add_files');
			$session->unsetValue($current_iid.'_post_vars');
			$session->unsetValue($current_iid.'_material_attach_ids');
			$session->unsetValue($current_iid.'_material_back_module');
		}
	}