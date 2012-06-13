<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_accounts_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetInitialData() {
			$return = array();
			
			$translator = $this->_environment->getTranslationObject();
			
			$translations = array();
			$translations['USER_LIST_ACTION_DELETE_ACCOUNT'] = $translator->getMessage('USER_LIST_ACTION_DELETE_ACCOUNT');
			$translations['USER_LIST_ACTION_LOCK_ACCOUNT'] = $translator->getMessage('USER_LIST_ACTION_LOCK_ACCOUNT');
			$translations['USER_LIST_ACTION_FREE_ACCOUNT'] = $translator->getMessage('USER_LIST_ACTION_FREE_ACCOUNT');
			$translations['USER_LIST_ACTION_STATUS_USER'] = $translator->getMessage('USER_LIST_ACTION_STATUS_USER');
			$translations['USER_LIST_ACTION_STATUS_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_MODERATOR');
			$translations['USER_LIST_ACTION_STATUS_CONTACT_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_CONTACT_MODERATOR');
			$translations['USER_LIST_ACTION_STATUS_NO_CONTACT_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_NO_CONTACT_MODERATOR');
			$translations['USER_LIST_ACTION_EMAIL_SEND'] = $translator->getMessage('USER_LIST_ACTION_EMAIL_SEND');
			
			$return['translations'] = $translations;
			$return['success'] = true;
			
			echo json_encode($return);
		}

		public function actionPerformUserAction() {
			$return = array();
			
			$user_manager = $this->_environment->getUserManager();
			
			// get request data
			$ids = $this->_data['ids'];
			$action = $this->_data['action'];
			
			// prevent removing all moderators
			$user_manager->resetLimits();
			$user_manager->setContextLimit($this->_environment->getCurrentContextID());
			$user_manager->setModeratorLimit();
			
			$moderator_ids = $user_manager->getIds();
			if(!is_array($moderator_ids)) $moderator_ids = array();
			$room_moderator_count = count($moderator_ids);
			
			$selected_moderator_count = count(array_intersect($selected_ids, $moderator_ids));
			$room_moderator_count = count($moderator_ids);
			
			// perform checks
			switch($action) {
				case "delete":
				case "lock":
				case "free":
				case "status_user":
					if($room_moderator_count - $selected_moderator_count < 1) {
						$this->setErrorReturn('103', 'you are trying to remove all moderators', array());
						echo $this->_return;
						exit;
					}
					break;
				case "status_contact_moderator":
					foreach($ids as $id) {
						$user = $user_manager->getItem($id);
						if(!$user->isUser()) {
							// if user is not normal user or moderator
							$this->setErrorReturn('104', $user->getUserID() . ' is neither a normal user nor a moderator', array());
							echo $this->_return;
							exit;
						}
					}
					break;
				
				case "status_no_contact_moderator":
					foreach($ids as $id) {
						$user = $user_manager->getItem($id);
						if(!$user->isContact()) {
							$this->setErrorReturn('105', $user->getUserID() . ' is no contact person', array());
							echo $this->_return;
							exit;
						}
					}
					break;
			}
			
			// perform actions
			$admin = $this->_environment->getCurrentUser();
			
			foreach($ids as $id) {
				$user = $user_manager->getItem($id);
				
				$this->performAction($action, $user);
			}
			
			/*
			 * $send_to = $user->getEmail();
			
			
			if($user->isEmailVisible()){
				$formal_data_send_to[] = $user->getFullName()." &lt;".$send_to."&gt;";
			} else {
				$translator = $environment->getTranslationObject();
				$formal_data_send_to[] = $user->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
			}
			
			
			
			
			
			// send email
			if ( ( isset($post_array['with_mail']) and $post_array['with_mail'] == '1') ) {
				include_once('classes/cs_mail.php');
				$mail = new cs_mail();
				$mail->set_from_email($admin->getEmail());
				$mail->set_from_name($admin->getFullname());
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
					$content = str_replace('%2',$user->getUserID(),$content);
					$content = str_replace('%3',$room->getTitle(),$content);
				} elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
					$content = str_replace('%2',$room->getTitle(),$content);
					$content = str_replace('%3',$user->getUserID(),$content);
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
			 */
			
			
			
			foreach ( $action_array['selected_ids'] as $user_item_id ) {
				$user = $user_manager->getItem($user_item_id);
				if ( isset($user) ) {
					$last_status = $user->getStatus();
				}
			}
			
			// check if send mail etc...

			$return['success'] = true;

			echo json_encode($return);
		}
		
		private function performAction($action, $user) {
			$current_context = $this->_environment->getCurrentContextItem();
			$hash_manager = $this->_environment->getHashManager();
			$label_manager = $this->_environment->getLabelManager();
			
			switch($action) {
				case "delete":
					if(!$this->_environment->inPortal() && !$this->_environment->inServer()) {
						if($this->_environment->inGroupRoom()) {
							$group_item = $current_context->getLinkedGroupItem();
							if(isset($group_item) && !empty($group_item)) {
								$project_room_item = $current_context->getLinkedProjectItem();
								if(isset($project_room_item) && !empty($project_room_item)) {
									$project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(), $user->getAuthSource());
									$group_item->removeMember($project_room_user_item);
								}
							}
						}
						
						$hash_manager->deleteHashesForUser($user->getItemID());
						$user->delete();
					}
					break;
				case "lock":
					$hash_manager->deleteHashesForUser($user->getItemID());
					$user->reject();
					$user->save();
					
					if($this->_environment->inGroupRoom()) {
						$group_item = $current_context->getLinkedGroupItem();
						if(isset($group_item) && !empty($group_item)) {
							$project_room_item = $current_context->getLinkedProjectItem();
							if(isset($project_room_item) && !empty($project_room_item)) {
								$project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(), $user->getAuthSource());
								$group_item->removeMember($project_room_user_item);
							}
						}
					}
					break;
				case "free":
					// link to group 'ALL' in project rooms
					if($this->_environment->inProjectRoom()) {
						$group_list = $user->getGroupList();
						if($group_list->isEmpty()) {
							$label_manager->setExactNameLimit('ALL');
							$label_manager->setContextLimit($this->_environment->getCurrentContextID());
							$label_manager->select();
							$group_list = $label_manager->get();
							
							if($group_list->getCount() == 1) {
								$group = $group_list->getFirst();
								$group->setTitle('ALL');			// needed, but not good(TBD)
							}
							
							// save link to the group ALL
							if(isset($group)) {
								$user->setGroupByID($group->getItemID());
								$group->setModificatorItem($user);
								$group->save();
							}
						}
					}
					
					// don't change users with status user or Moderator
					if(!$user->isUser() && !$user->isModerator()) {
						$user->makeUser();
						$user->save();
						
						if($this->_environment->inGroupRoom()) {
							$group_item = $current_context->getLinkedGroupItem();
							if(isset($group_item) && !empty($group_item)) {
								$project_room_item = $current_context->getLinkedProjectItem();
								if(isset($project_room_item) && !empty($project_room_item)) {
									$project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(), $user->getAuthSource());
									$group_item->addMember($project_room_user_item);
								}
							}
						}
					}
					break;
				case "status_user":
					// link to group 'ALL' in project rooms
					if($this->_environment->inProjectRoom()) {
						$group_list = $user->getGroupList();
						if($group_list->isEmpty()) {
							$label_manager->setExactNameLimit('ALL');
							$label_manager->setContextLimit($this->_environment->getCurrentContextID());
							$label_manager->select();
							$group_list = $label_manager->get();
								
							if($group_list->getCount() == 1) {
								$group = $group_list->getFirst();
								$group->setTitle('ALL');			// needed, but not good(TBD)
							}
								
							// save link to the group ALL
							if(isset($group)) {
								$user->setGroupByID($group->getItemID());
								$group->setModificatorItem($user);
								$group->save();
							}
						}
					}
					
					$user->makeUser();
					$user->save();
					
					if($this->_environment->inGroupRoom()) {
						$group_item = $current_context->getLinkedGroupItem();
						if(isset($group_item) && !empty($group_item)) {
							$project_room_item = $current_context->getLinkedProjectItem();
							if(isset($project_room_item) && !empty($project_room_item)) {
								$project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(), $user->getAuthSource());
								$group_item->addMember($project_room_user_item);
							}
						}
					}
					break;
				case "status_moderator":
					// link to group 'ALL' in project rooms
					if($this->_environment->inProjectRoom()) {
						$group_list = $user->getGroupList();
						if($group_list->isEmpty()) {
							$label_manager->setExactNameLimit('ALL');
							$label_manager->setContextLimit($this->_environment->getCurrentContextID());
							$label_manager->select();
							$group_list = $label_manager->get();
					
							if($group_list->getCount() == 1) {
								$group = $group_list->getFirst();
								$group->setTitle('ALL');			// needed, but not good(TBD)
							}
					
							// save link to the group ALL
							if(isset($group)) {
								$user->setGroupByID($group->getItemID());
								$group->setModificatorItem($user);
								$group->save();
							}
						}
					}
						
					$user->makeModerator();
					$user->save();
						
					if($this->_environment->inGroupRoom()) {
						$group_item = $current_context->getLinkedGroupItem();
						if(isset($group_item) && !empty($group_item)) {
							$project_room_item = $current_context->getLinkedProjectItem();
							if(isset($project_room_item) && !empty($project_room_item)) {
								$project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(), $user->getAuthSource());
								$group_item->addMember($project_room_user_item);
							}
						}
					}
					break;
				case "status_contact_moderator":
					$user->makeContactPerson();
					$user->save();
					break;
				
				case "status_no_contact_moderator":
					$user->makeNoContactPerson();
					$user->save();
					break;
				
				case "email":
					break;
			}
			
			
			// change task status
			if(	$action === 'delete' ||
				$action === 'lock' ||
				$action === 'free' ||
				$action === 'status_user' ||
				$action === 'status_moderator') {
				
				$task_manager = $this->_environment->getTaskManager();
				$task_list = $task_manager->getTaskListForItem($user);
				
				if($task_list->getCount() > 0) {
					$task_item = $task_list->getFirst();
					while($task_item) {
						if($task_item->getStatus() == 'REQUEST' && ($task_item->getTitle() == 'TASK_USER_REQUEST' || $task_item->getTitle() == 'TASK_PROJECT_ROOM_REQUEST')) {
							$task_item->setStatus('CLOSED');
							$task_item->save();
						}
						
						$task_item = $task_list->getNext();
					}
				}
			}
			
			// if commsy user is rejected, reject all accounts in project and community rooms
			if($user->isRejectd() && $this->_environment->inPortal()) {
				$user_list = $user->getRelatedUserList();
				$user_item = $user_list->getFirst();
				
				while($user_item) {
					$user_item->reject();
					$user_item->save();
					
					$user_item = $user_list->getNext();
				}
			}
			
			// if commsy user is re-opened, re-open own room user
			if(isset($user)) $last_status = $user->getStatus();
			if($this->_environment->inPortal() && isset($last_status) && (empty($last_status) || $last_status == 0)) {
				$user_own_room = $user->getRelatedPrivateRoomUserItem();
				
				if(isset($user_own_room)) {
					$user_own_room->makeModerator();
					$user_own_room->makeContactPerson();
					$user_own_room->save();
				}
			}
		}

		public function actionPerformRequest() {
			$return = array();

			$user_manager = $this->_environment->getUserManager();
			$translator = $this->_environment->getTranslationObject();

			// get request data
			$current_page = $this->_data['current_page'];
			$restrictions = $this->_data['restrictions'];
			
			// get data from db
			$user_manager->reset();
			$user_manager->setContextLimit($this->_environment->getCurrentContextID());
			$count_all = $user_manager->getCountAll();
			
			// set restrictions
			/*
			 * if ( !empty($sort) ) {
			      $user_manager->setSortOrder($sort);
			   }
			   
			   if ( !empty($sel_auth_source)
			        and  $sel_auth_source != -1
			      ) {
			      $user_manager->setAuthSourceLimit($sel_auth_source);
			   }
			 */
			
			if(!empty($restrictions['search'])) $user_manager->setSearchLimit($restrictions['search']);
			
			if(!empty($restrictions['status'])) {
				if($restrictions['status'] == '10') $user_manager->setContactModeratorLimit();
				else $user_manager->setStatusLimit($restrictions['status']);
			}
			
			$ids = $user_manager->getIDArray();
			$count_all_shown = count($ids);
			
			$interval = CS_LIST_INTERVAL;
			$from = $current_page * $interval;
			
			$user_manager->setIntervalLimit($from, $interval);
			$user_manager->select();
			
			// get user list
			$user_list = $user_manager->get();
			
			// prepare return
			$return['list'] = array();
			$item = $user_list->getFirst();
			while($item) {
				$entry = array();
				
				$status = '';
				if($item->isModerator()) $status = $translator->getMessage('USER_STATUS_MODERATOR');
				elseif($item->isUser()) $status = $translator->getMessage('USER_STATUS_USER');
				elseif($item->isRequested()) $status = $translator->getMessage('USER_STATUS_REQUESTED');
				else {
					$status = $translator->getMessage('USER_STATUS_REJECTED');
				}
				
				if($item->isContact()) $status .= ' [' . $translator->getMessage('USER_STATUS_CONTACT_SHORT') . ']';
				
				$entry['item_id']			= $item->getItemID();
				$entry['fullname']			= $item->getFullName();
				$entry['email']				= $item->getEmail();
				$entry['status']			= $status;
			
				$return['list'][] = $entry;
				$item = $user_list->getNext();
			}
			$return['paging']['pages'] = ceil($count_all_shown / $interval);

			$return['success'] = true;

			echo json_encode($return);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// check rights
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
				
			$error = false;
			if(isset($current_context) && !$current_context->isOpen() && !$current_context->isTemplate()) {
				// room is closed
				$error = true;
			} elseif(!$current_user->isModerator()) {
				// not allowed
				$error = true;
			}
				
			if($error === true) exit;
			
			// call parent
			parent::process();
		}
	}