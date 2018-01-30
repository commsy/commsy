<?php
	require_once('classes/controller/cs_room_controller.php');
	
	class cs_user_action_controller extends cs_room_controller {
	   
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'user_action';
		}
		
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUser();
			$translator = $this->_environment->getTranslationObject();
			$session = $this->_environment->getSessionItem();
			$index_action = $session->getValue('index_action');
			
			if($index_action['action'] == 'USER_EMAIL_SEND'){
			   $user_manager = $this->_environment->getUserManager();
			   //$user = $user_manager->get
			   
			   $user_array = array();
			   foreach($index_action['selected_ids'] as $item_id){
			      $temp_user = $user_manager->getItem($item_id);
			      $temp_array['name'] = $temp_user->getFullName();
			      if($temp_user->isEmailVisible()){
			         $temp_array['email'] = $temp_user->getEmail();
			      } else {
			         $temp_array['email'] = $translator->getMessage('USER_EMAIL_HIDDEN2');
			      }
			      $temp_array['item_id'] = $temp_user->$item_id;
			      $user_array[] = $temp_array;
			   }
			   
			   $this->assign('user', 'data', $user_array);
			   
			   $email_footer = $translator->getMessage('MAIL_BODY_CIAO_PR', $current_user->getFullName(), $current_context->getTitle());
			   
			   $this->assign('email', 'footer', $email_footer);
			}
		}
		
		public function actionAction(){
		   $environment = $this->_environment;
		   $class_factory = $environment->getClassFactory();
			
         $translator = $environment->getTranslationObject();
         $session_item = $environment->getSessionItem();
         $action_array = $session_item->getValue('index_action');
         
         #pr($action_array);
         
         // option contains the name of the submit button, if this
         // script is called as result of a form post
         if (!empty($_POST['option'])) {
            $command = $_POST['option'];
         } else {
            $command = '';
         }
         
         if ( $command != 'error' ) {
            #pr(1);
            if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
               redirect($action_array['backlink']['cid'],$action_array['backlink']['mod'],$action_array['backlink']['fct'],$action_array['backlink']['par']);
            } else {
               #pr(2);
               $form = $class_factory->getClass(ACCOUNT_ACTION_FORM,array('environment' => $environment));
         
               // init display data
               if ( !empty($_POST) ) {     // second call of form: set post data
                  $form->setFormPost($_POST);
               }
         
               $form->setActionArray($action_array);
               $form->prepareForm();
               $form->loadValues();
         
               $temp = $action_array['action'];
               $tempMessage = "";
               switch( $temp )
               {
                  case 'USER_ACCOUNT_DELETE':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_DELETE_BUTTON');
                     break;
                  case 'USER_ACCOUNT_FREE':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_FREE_BUTTON');
                     break;
                  case 'USER_ACCOUNT_LOCK':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_LOCK_BUTTON');
                     break;
                  case 'USER_MAKE_CONTACT_PERSON':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_MAKE_CONTACT_PERSON_BUTTON');
                     break;
                  case 'USER_STATUS_MODERATOR':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_MODERATOR_BUTTON');
                     break;
                  case 'USER_STATUS_USER':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_USER_BUTTON');
                     break;
                  case 'USER_UNMAKE_CONTACT_PERSON':
                     $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
                     break;
                  default:
                     $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR');
                     break;
               }
         
               if ( !empty($command)
                    and ( isOption($command,$tempMessage)
                          or isOption(strtolower($command),strtolower($translator->getMessage('INDEX_ACTION_SEND_MAIL_BUTTON')))
                        )
                  ) {
                  #pr(4);
                  $correct = $form->check();
                  if ( $correct
                       or !isset($_POST['with_mail'])
                     ) {
                     $this->performAction($environment,$action_array,$_POST);
                     if($environment->getCurrentModule() != 'user'){
                        redirect($action_array['backlink']['cid'],$action_array['backlink']['mod'],$action_array['backlink']['fct'],$action_array['backlink']['par']);
                     } else {
                        if($mail_success) {
         	               $params = array();
         	               $params['environment'] = $environment;
         	               $params['with_modifying_actions'] = false;
         	               $detail_view = $class_factory->getClass(MAIL_VIEW,$params);
         	               unset($params);
         	               $detail_view->setFormalData($formal_data);
###         	               $page->add($detail_view);
                        } else {
                           $params = array();
         		            $params['environment'] = $environment;
         		            $params['with_modifying_actions'] = true;
         		            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         		            unset($params);
         		            if ( !empty($mail_error_array) ) {
         		               $error_string = $translator->getMessage('ERROR_SEND_EMAIL_TO');
         		               foreach ($mail_error_array as $error) {
         		                  $error = htmlentities($error, ENT_NOQUOTES, 'UTF-8');
         		                  $error = str_replace(',',BRLF,$error);
         		                  $error_string .= BRLF.$error;
         		               }
         		            } else {
         		               $error_string = $translator->getMessage('ERROR_SEND_MAIL');
         		            }
         		
         		            $params = array();
         		            $params['environment'] = $environment;
         		            $params['with_modifying_actions'] = false;
         		            $detail_view = $class_factory->getClass(MAIL_VIEW,$params);
         		            unset($params);
         		            $detail_view->setFormalData($formal_data);
         		            $errorbox->setText($error_string);
###         		            $page->add($errorbox);
###         		            $page->add($detail_view);
                        }
                     }
                  }
               } else {
                  // display form
                  if ( $environment->getCurrentModule() == 'account') {
                     $params = array();
                     $params['environment'] = $environment;
                     $params['with_modifying_actions'] = true;
                     $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
                     unset($params);
                  } else {
                     $params = array();
                     $params['environment'] = $environment;
                     $params['with_modifying_actions'] = true;
                     $form_view = $class_factory->getClass(FORM_VIEW,$params);
                     unset($params);
                  }
                  $params = array();
                  $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),'action',$params));
                  $form_view->setForm($form);
                  if ( $environment->inPortal() or $environment->inServer() ){
###                     $page->addForm($form_view);
                  } else {
###                     $page->add($form_view);
                  }
               }
            }
   		}
		}
		
	function performAction ( $environment, $action_array, $post_array ) {
         global $formal_data;
         global $mail_success;
         global $mail_error_array;
      
         // Get the translator object
         $translator = $environment->getTranslationObject();
      
         // perform action
         $user_manager = $environment->getUserManager();
         $admin = $user_manager->getItem($action_array['user_item_id']);
         if ( !isset($admin) ) {
            $admin = $environment->getCurrentUserItem();
         }
      
         if ( isset($post_array['copy'])
              and !empty($post_array['copy'])
              and !in_array($action_array['user_item_id'],$action_array['selected_ids'])
              and count($action_array['selected_ids']) > 1
            ) {
            $action_array['selected_ids'][] = $action_array['user_item_id'];
         }
      
         foreach ( $action_array['selected_ids'] as $user_item_id ) {
            $user = $user_manager->getItem($user_item_id);
            if ( isset($user) ) {
               $last_status = $user->getStatus();
            }
      
            if ( $action_array['action'] == 'USER_ACCOUNT_DELETE' ) {
               if ( $environment->inPortal() or $environment->inServer() ) {
                  $authentication = $environment->getAuthenticationObject();
                  $authentication->delete($user_item_id);
                  unset($authentication);
               } else {
      
                  ################################
                  # FLAG: group room
                  ################################
                  if ( $environment->inGroupRoom() ) {
                     $current_context = $environment->getCurrentContextItem();
                     $group_item = $current_context->getLinkedGroupItem();
                     if ( isset($group_item) and !empty($group_item) ) {
                        $project_room_item = $current_context->getLinkedProjectItem();
                        if ( isset($project_room_item) and !empty($project_room_item) ) {
                           $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                           $group_item->removeMember($project_room_user_item);
                           unset($project_room_user_item);
                        }
                        unset($project_room_item);
                     }
                     unset($group_item);
                     unset($current_context);
                  }
                  ################################
                  # FLAG: group room
                  ################################
                  $hash_manager = $environment->getHashManager();
                  $hash_manager->deleteHashesForUser($user->getItemID());
                  $user->delete();
               }
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_ACCOUNT_LOCK' ) {
            	$hash_manager = $environment->getHashManager();
               $hash_manager->deleteHashesForUser($user->getItemID());
               $user->reject();
               $user->save();
               
               ################################
               # FLAG: group room
               ################################
               if ( $environment->inGroupRoom() ) {
                  $current_context = $environment->getCurrentContextItem();
                  $group_item = $current_context->getLinkedGroupItem();
                  if ( isset($group_item) and !empty($group_item) ) {
                     $project_room_item = $current_context->getLinkedProjectItem();
                     if ( isset($project_room_item) and !empty($project_room_item) ) {
                        $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                        $group_item->removeMember($project_room_user_item);
                        unset($group_item);
                        unset($project_room_user_item);
                     }
                     unset($project_room_item);
                  }
                  unset($current_context);
               }
               ################################
               # FLAG: group room
               ################################
      
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_ACCOUNT_FREE' ) {
      
               // link to group 'ALL' in project rooms
               if ($environment->inProjectRoom()) {
                 $group_list = $user->getGroupList();
                 if ($group_list->isEmpty()) {
                     $group_manager = $environment->getLabelManager();
                     $group_manager->setExactNameLimit('ALL');
                     $group_manager->setContextLimit($environment->getCurrentContextID());
                     $group_manager->select();
                     $group_list = $group_manager->get();
                     if ($group_list->getCount() == 1) {
                        $group = $group_list->getFirst();
                        $group->setTitle('ALL'); // needed, but not good (TBD)
                     }
      
                     // save link to the group ALL
                     if (isset($group)) {
                        $user->setGroupByID($group->getItemID());
                        $group->setModificatorItem($user);
                        $group->save();
                        unset($group);
                     }
                     unset($group_list);
                     unset($group_manager);
                  }
               }
               // don't change users with status user or Moderator
               if ( (!$user->isUser()) and (!$user->isModerator()) ) {
                  $user->makeUser();
                  $user->save();
      
                  ################################
                  # FLAG: group room
                  ################################
                  if ( $environment->inGroupRoom() ) {
                     $current_context = $environment->getCurrentContextItem();
                     $group_item = $current_context->getLinkedGroupItem();
                     if ( isset($group_item) and !empty($group_item) ) {
                        $project_room_item = $current_context->getLinkedProjectItem();
                        if ( isset($project_room_item) and !empty($project_room_item) ) {
                           $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                           $group_item->addMember($project_room_user_item);
                           unset($group_item);
                           unset($project_room_user_item);
                        }
                        unset($project_room_item);
                     }
                     unset($group_item);
                     unset($current_context);
                  }
                  ################################
                  # FLAG: group room
                  ################################
      
               }
               $send_to = $user->getEmail();
      
            } elseif ( $action_array['action'] == 'USER_STATUS_USER' ) {
      
               // link to group 'ALL' in project rooms
               if ($environment->inProjectRoom()) {
                  $group_list = $user->getGroupList();
                  if ($group_list->isEmpty()) {
                     $group_manager = $environment->getLabelManager();
                     $group_manager->setExactNameLimit('ALL');
                     $group_manager->setContextLimit($environment->getCurrentContextID());
                     $group_manager->select();
                     $group_list = $group_manager->get();
                     if ($group_list->getCount() == 1) {
                        $group = $group_list->getFirst();
                        $group->setTitle('ALL'); // needed, but not good (TBD)
                     }
      
                     // save link to the group ALL
                     if (isset($group)) {
                        $user->setGroupByID($group->getItemID());
                        $group->setModificatorItem($user);
                        $group->save();
                        unset($group);
                     }
                     unset($group_list);
                     unset($group_manager);
                  }
               }
      
               $user->makeUser();
               $user->save();
               $send_to = $user->getEmail();
      
               ################################
               # FLAG: group room
               ################################
               if ( $environment->inGroupRoom() ) {
                  $current_context = $environment->getCurrentContextItem();
                  $group_item = $current_context->getLinkedGroupItem();
                  if ( isset($group_item) and !empty($group_item) ) {
                     $project_room_item = $current_context->getLinkedProjectItem();
                     if ( isset($project_room_item) and !empty($project_room_item) ) {
                        $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                        $group_item->addMember($project_room_user_item);
                        unset($project_room_user_item);
                        unset($group_item);
                     }
                     unset($project_room_item);
                  }
                  unset($current_context);
                  unset($group_item);
               }
               ################################
               # FLAG: group room
               ################################
      
            } elseif ( $action_array['action'] == 'USER_STATUS_MODERATOR' ) {
      
               // link to group 'ALL' in project rooms
               if ($environment->inProjectRoom()) {
                  $group_list = $user->getGroupList();
                  if ($group_list->isEmpty()) {
                     $group_manager = $environment->getLabelManager();
                     $group_manager->setExactNameLimit('ALL');
                     $group_manager->setContextLimit($environment->getCurrentContextID());
                     $group_manager->select();
                     $group_list = $group_manager->get();
                     if ($group_list->getCount() == 1) {
                        $group = $group_list->getFirst();
                        $group->setTitle('ALL'); // needed, but not good (TBD)
                     }
                     unset($group_list);
      
                     // save link to the group ALL
                     if (isset($group)) {
                        $user->setGroupByID($group->getItemID());
                        $group->setModificatorItem($user);
                        $group->save();
                        unset($group);
                     }
                     unset($group_manager);
                  }
               }
      
               $user->makeModerator();
               $user->save();
               $send_to = $user->getEmail();
      
               ################################
               # FLAG: group room
               ################################
               if ( $environment->inGroupRoom() ) {
                  $current_context = $environment->getCurrentContextItem();
                  $group_item = $current_context->getLinkedGroupItem();
                  if ( isset($group_item) and !empty($group_item) ) {
                     $project_room_item = $current_context->getLinkedProjectItem();
                     if ( isset($project_room_item) and !empty($project_room_item) ) {
                        $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                        $group_item->addMember($project_room_user_item);
                        unset($project_room_user_item);
                        unset($group_item);
                     }
                     unset($project_room_item);
                  }
                  unset($current_context);
                  unset($group_item);
               }
               ################################
               # FLAG: group room
               ################################
      
            } elseif ( $action_array['action'] == 'USER_MAKE_CONTACT_PERSON' ) {
               $user->makeContactPerson();
               $user->save();
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON' ) {
               $user->makeNoContactPerson();
               $user->save();
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_EMAIL_SEND' ) {
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
               $send_to = $user->getEmail();
            } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
               $send_to = $user->getEmail();
            }
      
            if($user->isEmailVisible()){
               $formal_data_send_to[] = $user->getFullName()." &lt;".$send_to."&gt;";
            } else {
               $translator = $environment->getTranslationObject();
               $formal_data_send_to[] = $user->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
            }
      
            // change task status
            if ( $action_array['action'] == 'USER_ACCOUNT_DELETE'
                 or $action_array['action'] == 'USER_ACCOUNT_LOCK'
                 or $action_array['action'] == 'USER_ACCOUNT_FREE'
                 or $action_array['action'] == 'USER_STATUS_USER'
                 or $action_array['action'] == 'USER_STATUS_MODERATOR'
               ) {
               $task_manager = $environment->getTaskManager();
               $task_list = $task_manager->getTaskListForItem($user);
               if ($task_list->getCount() > 0) {
                  $task_item = $task_list->getFirst();
                  while ($task_item) {
                     if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_USER_REQUEST' or $task_item->getTitle() == 'TASK_PROJECT_ROOM_REQUEST')) {
                        $task_item->setStatus('CLOSED');
                        $task_item->save();
                     }
                     $task_item = $task_list->getNext();
                  }
               }
               unset($task_list);
               unset($task_item);
               unset($task_manager);
            }
      
            // if commsy user is rejected, reject all accounts in projectrooms and community rooms
            if ( $user->isRejected() and $environment->inPortal() ) {
               $user_list = $user->getRelatedUserList();
               $user_item = $user_list->getFirst();
               while ($user_item) {
                  $user_item->reject();
                  $user_item->save();
                  $user_item = $user_list->getNext();
               }
               unset($user_list);
               unset($user_item);
            }
      
            // if commsy user is re-opend, re-open own room user
            if ( $environment->inPortal()
                 and isset($last_status)
                 and ( empty($last_status)
                       or $last_status == 0
                     )
               ) {
               $user_own_room = $user->getRelatedPrivateRoomUserItem();
               if ( isset($user_own_room) ) {
                  $user_own_room->makeModerator();
                  $user_own_room->makeContactPerson();
                  $user_own_room->save();
               }
            }
      
            // send email
            if ( ( isset($post_array['with_mail']) and $post_array['with_mail'] == '1') ) {
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

               $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
               $mail->set_reply_to_email($admin->getEmail());
               $mail->set_reply_to_name($admin->getFullname());
      
               if(!isset($formal_data_from)){
                  $formal_data_from = array($translator->getMessage('MAIL_FROM'), $admin->getFullname()." &lt;".$admin->getEmail()."&gt;");
                  $formal_data[] = $formal_data_from;
               }
               if(!isset($formal_data_reply)){
                  $formal_data_reply = array($translator->getMessage('REPLY_TO'), $admin->getFullname()." &lt;".$admin->getEmail()."&gt;");
                  $formal_data[] = $formal_data_reply;
               }
               // subject and body
               // language
               $translator = $environment->getTranslationObject();
               $room  = $environment->getCurrentContextItem();
               $url_to_room = LF.LF;
               $url_to_room .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
               $subject = $_POST['subject'];
               $content = $_POST['content'];
               $content = str_replace('%1',$user->getFullname(),$content);
      
               // now prepare for each action separately
               if ( $action_array['action'] == 'USER_ACCOUNT_DELETE'
                    or $action_array['action'] == 'USER_ACCOUNT_LOCK'
                    or $action_array['action'] == 'USER_ACCOUNT_FREE'
                    or $action_array['action'] == 'USER_STATUS_USER'
                    or $action_array['action'] == 'USER_STATUS_MODERATOR'
                    or $action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON'
                    or $action_array['action'] == 'USER_MAKE_CONTACT_PERSON'
                  ) {
               if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
               	  $userid = $user->getFullName();
               } else {
               	  $userid = $user->getUserID();
               }
                  $content = str_replace('%2',$userid,$content);
                  $content = str_replace('%3',$room->getTitle(),$content);
               } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
                  $content = str_replace('%2',$room->getTitle(),$content);
                  $content = str_replace('%3',$userid,$content);
               } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
                  $account_text = '';
                  $user_manager->resetLimits();
                  $user_manager->setContextLimit($environment->getCurrentContextID());
                  $user_manager->setUserLimit();
                  $user_manager->setSearchLimit($user->getEmail());
                  $user_manager->select();
                  $user_list = $user_manager->get();
                  if (!$user_list->isEmpty()) {
                     if ($user_list->getCount() > 1) {
                        $first = true;
                        $user_item = $user_list->getFirst();
                        while ($user_item) {
                           if ($first) {
                              $first = false;
                           } else {
                              $account_text .= LF;
                           }
                           $account_text .= $user_item->getUserID();
                           $user_item = $user_list->getNext();
                        }
                     } else {
                        include_once('functions/error_functions.php');
                        trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
                     }
                  } else {
                     include_once('functions/error_functions.php');
                     trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
                  }
                  $content = str_replace('%2',$user->getEmail(),$content);
                  $content = str_replace('%3',$room->getTitle(),$content);
                  $content = str_replace('%4',$account_text,$content);
               }
      
               unset($translator);
               unset($room);
      
               $translator = $environment->getTranslationObject();
      
               if ( isset($subject) and !empty($subject) ) {
                  $mail->set_subject($subject);
                  if(!isset($formal_data_subject)){
                     $formal_data_subject = array($translator->getMessage('MAIL_SUBJECT'), $subject);
                  }
               }
               if ( isset($content) and !empty($content) ) {
                  $mail->set_message($content);
                  if(!isset($formal_data_message)){
                     $formal_data_message = array($translator->getMessage('COMMON_MAIL_CONTENT').":", $content);
                  }
               }
               $mail->set_to($send_to);
      
               #// cc / bcc
               $cc_string = '';
               $bcc_string = '';
               $cc_array = array();
               $bcc_array = array();
               if (isset($post_array['cc']) and $post_array['cc'] == 'cc') {
                  $cc_array[] = $admin->getEmail();
               }
               if (isset($post_array['bcc']) and $post_array['bcc'] == 'bcc') {
                  $bcc_array[] = $admin->getEmail();
               }
               if (isset($post_array['cc_moderator']) and $post_array['cc_moderator'] == 'cc_moderator') {
                  $current_context = $environment->getCurrentContextItem();
                  $mod_list = $current_context->getModeratorList();
                  if (!$mod_list->isEmpty()) {
                     $moderator_item = $mod_list->getFirst();
                     while ($moderator_item) {
                        $email = $moderator_item->getEmail();
                        if (!empty($email)) {
                           $cc_array[] = $email;
                        }
                        unset($email);
                        $moderator_item = $mod_list->getNext();
                     }
                  }
                  unset($current_context);
               }
               if (isset($post_array['bcc_moderator']) and $post_array['bcc_moderator'] == 'bcc_moderator') {
                  $current_context = $environment->getCurrentContextItem();
                  $mod_list = $current_context->getModeratorList();
                  if (!$mod_list->isEmpty()) {
                     $moderator_item = $mod_list->getFirst();
                     while ($moderator_item) {
                        $email = $moderator_item->getEmail();
                        if (!empty($email)) {
                           $bcc_array[] = $email;
                        }
                        unset($email);
                        $moderator_item = $mod_list->getNext();
                     }
                  }
                  unset($current_context);
               }
      
               if ( isset($post_array['copy'])
                    and !empty($post_array['copy'])
                    and !in_array($action_array['user_item_id'],$action_array['selected_ids'])
                    and count($action_array['selected_ids']) == 1
                   ) {
                   $cc_array[] = $admin->getEmail();
               }
      
      
               if (!empty($cc_array)) {
                  $cc_array = array_unique($cc_array);
               }
               if (!empty($bcc_array)) {
                  $bcc_array = array_unique($bcc_array);
               }
               $cc_string = implode(",",$cc_array);
               $bcc_string = implode(",",$bcc_array);
               unset($cc_array);
               unset($bcc_array);
               if (!empty($cc_string)) {
                  $mail->set_cc_to($cc_string);
               }
               if (!empty($bcc_string)) {
                  $mail->set_bcc_to($bcc_string);
               }
               unset($cc_string);
               unset($bcc_string);
      
               $mail_success = $mail->send();
               $mail_error_array = $mail->getErrorArray();
               unset($mail);
            }
            unset($user);
         }
         if(isset($formal_data_send_to)){
            $formal_data[] = array($translator->getMessage('MAIL_TO'), implode(",", $formal_data_send_to));
         }
         if(isset($formal_data_subject)){
            $formal_data[] = $formal_data_subject;
         }
         if(isset($formal_data_message)){
            $formal_data[] = $formal_data_message;
         }
         unset($user_manager);
         unset($admin);
         
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), 'index');
      }
	}
?>