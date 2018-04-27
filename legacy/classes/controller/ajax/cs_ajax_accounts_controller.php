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
			$translations['USER_LIST_ACTION_STATUS_READ_ONLY_USER'] = $translator->getMessage('USER_LIST_ACTION_STATUS_READ_ONLY_USER');
			$translations['USER_LIST_ACTION_STATUS_USER'] = $translator->getMessage('USER_LIST_ACTION_STATUS_USER');
			$translations['USER_LIST_ACTION_STATUS_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_MODERATOR');
			$translations['USER_LIST_ACTION_STATUS_CONTACT_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_CONTACT_MODERATOR');
			$translations['USER_LIST_ACTION_STATUS_NO_CONTACT_MODERATOR'] = $translator->getMessage('USER_LIST_ACTION_STATUS_NO_CONTACT_MODERATOR');
			$translations['USER_LIST_ACTION_EMAIL_SEND'] = $translator->getMessage('USER_LIST_ACTION_EMAIL_SEND');

			$return['translations'] = $translations;

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetNewUserAccount() {
			$current_user = $this->_environment->getCurrentUserItem();
			$count_new_accounts = 0;
			if ($current_user->isModerator()){
                // user count
                $manager = $this->_environment->getUserManager();
                $manager->resetLimits();
                $manager->setContextLimit($this->_environment->getCurrentContextID());
                $manager->setStatusLimit(1);
                $manager->select();
                $user = $manager->get();
                $count_new_accounts = 0;
                if ($user->getCount() > 0) {
                    $count_new_accounts = $user->getCount();
                }
			}
			$this->setSuccessfullDataReturn(array("count" => $count_new_accounts));
			echo $this->_return;
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

			$selected_moderator_count = count(array_intersect($ids, $moderator_ids));
			$room_moderator_count = count($moderator_ids);
			// perform checks
			switch($action) {
				case "delete":
				case "lock":
				case "free":
				case "status_user":
				case "status_readonly_user":
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
			foreach($ids as $id) {
				$user = $user_manager->getItem($id);

				$this->performAction($action, $user);
			}

			$this->setSuccessfullDataReturn();
			echo $this->_return;
		}

		public function actionSendMail() {
			$user_manager = $this->_environment->getUserManager();
			$translator = $this->_environment->getTranslationObject();

			$ids = $this->_data["ids"];
			$sendMail = $this->_data["sendMail"];
			$modCC = $this->_data["modCC"];
			$modBCC = $this->_data["modBCC"];
			$authCC = $this->_data["authCC"];
			$authBCC = $this->_data["authBCC"];
			$subject = $this->_data["subject"];
			$action = $this->_data["action"];

			$admin = $this->_environment->getCurrentUser();

			$response_array = array();
			
			include_once("classes/cs_mail.php");

			foreach ($ids as $id) {
				$user = $user_manager->getItem($id);
				$sendTo = $user->getEmail();

				$description = $this->_data["description"];

				$formal_data_send_to = array();

				if ($user->isEmailVisible()) {
					$formal_data_send_to[] = $user->getFullName() . " &lt;" . $sendTo . "&gt;";
				} else {
					$formal_data_send_to[] = $user->getFullName() . " &lt;" . $translator->getMessage("USER_EMAIL_HIDDEN") . "&gt;";
				}
				
				// prepare mail
				$mail = new cs_mail();

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

                $mail->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
				$mail->set_reply_to_email($admin->getEmail());
				$mail->set_reply_to_name($admin->getFullname());
				
				if (!isset($formal_data_from)) {
					$formal_data_from = array(
							$translator->getMessage("MAIL_FROM"),
							$admin->getFullname() . " &lt;" . $admin->getEmail() . "&gt;"
					);
					$formal_data[] = $form_data_from;
				}
				
				if (!isset($formal_data_reply)) {
					$form_data_reply = array(
							$translator->getMessage("REPLY_TO"),
							$admin->getFullname() . " &lt;" . $admin->getEmail() . "&gt;"
					);
					$formal_data[] = $form_data_reply;
				}
				
				// subject and body
				$room = $this->_environment->getCurrentContextItem();
				$url_to_room = LF.LF;
				$url_to_room .= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?cid=" . $this->_environment->getCurrentContextID();
				
				if($action !== "email") {
					$description = str_replace("%1", $user->getFullname(), $description);
					$description = str_replace("%2", $user->getUserID(), $description);
					$description = str_replace("%3", $room->getTitle(), $description);
				}
				
				if (isset($subject) && !empty($subject)) {
					$mail->set_subject($subject);
				
					if (!isset($formal_data_subject)) {
						$formal_data_subject = array($translator->getMessage("MAIL_SUBJECT"), $subject);
					}
				}
				
				if (isset($description) && !empty($description)) {
					$mail->set_message($description);
				
					if (!isset($formal_data_message)) {
						$formal_data_message = array($translator->getMessage("COMMON_MAIL_CONTENT") . ":", $description);
					}
				}

				// reciever
				if ( (isset($sendMail) && $sendMail == "true") || $action == "mail" ) {
					$mail->set_to($sendTo);
				}
				
				// Datenschutz
				if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
					$mail_user = $mail;
					$user_description = str_replace('XXX '.$translator->getMessage('COMMON_DATASECURITY_NAME', $user->getFullname()),$user->getUserID(),$description);
					$mail_user->set_message($user_description);
					$mail_success = $mail_user->send();
// 					$mail->set_to($admin->getEmail());
					$mail->set_message($description);
				}
				
				
				// cc / bcc
				$cc_array = array();
				$bcc_array = array();

				if (isset($authCC) && $authCC == "true") {
					$cc_array[] = $admin->getEmail();
				}

				if (isset($authBCC) && authBCC == "true") {
					$bcc_array[] = $admin->getEmail();
				}

				if (isset($modCC) && $modCC == "true") {
					$current_context = $this->_environment->getCurrentContextItem();
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
				}

				if (isset($modBCC) && $modBCC == "true") {
					$current_context = $this->_environment->getCurrentContextItem();
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
				}

				// make unique
				if (!empty($cc_array)) $cc_array = array_unique($cc_array);
				if (!empty($bcc_array)) $bcc_array = array_unique($bcc_array);

				// build strings
				$cc_string = implode(",", $cc_array);
				$bcc_string = implode(",", $bcc_array);

				if (!empty($cc_string)) {
					$mail->set_cc_to($cc_string);
				}

				if (!empty($bcc_string)) {
					$mail->set_bcc_to($bcc_string);
				}
				if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
					if(!empty($cc_string) or !empty($bcc_string)){
						$mail->set_to('');
						$mail_success = $mail->send();
					}
				} else {
					$mail_success = $mail->send();
				}

				unset($cc_string);
				unset($bcc_string);

				// send mail
				
				$response_array[] = array(
					$mail_success,
					$mail_error_array = $mail->getErrorArray()
				);
			}

			// setup successfull reponse array - but it can also contains errors
			$this->setSuccessfullDataReturn($response_array);
			echo $this->_return;
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
				case "status_readonly_user":
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
					
					if ($action == "status_user") {
						$user->makeUser();
					} else {
						$user->makeReadOnlyUser();
					}
					
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
				$action === 'status_moderator' ||
				$action === 'status_readonly_user') {

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
			if($user->isRejected() && $this->_environment->inPortal()) {
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

			if(!empty($restrictions['search'])) $user_manager->setSearchLimit($restrictions['search']);

			if(!empty($restrictions['status'])) {
				if($restrictions['status'] == '10') $user_manager->setContactModeratorLimit();
				else if($restrictions['status'] == '11') $user_manager->setReadonlyLimit();
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
			
			$portal_item = $this->_environment->getCurrentPortalItem();

			// prepare return
			$return['list'] = array();
			$item = $user_list->getFirst();
			while($item) {
				$entry = array();

				$status = '';
				if($item->isModerator()) $status = $translator->getMessage('USER_STATUS_MODERATOR');
				elseif($item->isReadOnlyUser()) $status = $translator->getMessage('USER_STATUS_READ_ONLY_USER');
				elseif($item->isUser()) $status = $translator->getMessage('USER_STATUS_USER');
				elseif($item->isRequested()) $status = $translator->getMessage('USER_STATUS_REQUESTED');
				else {
					$status = $translator->getMessage('USER_STATUS_REJECTED');
				}

				if($item->isContact()) $status .= ' [' . $translator->getMessage('USER_STATUS_CONTACT_SHORT') . ']';

				$entry['item_id']			= $item->getItemID();
				// Datenschutz
				if($portal_item->getHideAccountname()){
					$entry['fullname']			= $item->getFullName();
				} else {
					$entry['fullname']			= $item->getFullName().' ('.$item->getUserID().')';
				}
				$entry['email']				= $item->getEmail();
				$entry['status']			= $status;
				if($portal_item->withAGBDatasecurity()){
					$entry['agb'] = $item->getAGBAcceptanceDate();
				}
            if (!$item->isRequested()) {
               $entry['comment'] = '';
            } else {
               $entry['comment'] = $item->getUserComment();
            }

				$return['list'][] = $entry;
				$item = $user_list->getNext();
			}
			$return['paging']['pages'] = ceil($count_all_shown / $interval);

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
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