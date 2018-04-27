<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_group_detail_controller extends cs_detail_controller {
		private $_show_content_without_window = false;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'group_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_GROUP_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();
			$environment = $this->_environment;
			$translator = $this->_environment->getTranslationObject();

			// try to set the item
			$this->setItem();

#			if($this->_item->isA(CS_LABEL_TYPE) && $this->_item->getLabelType() === CS_GROUP_TYPE && $this->_item->isGroupRoomActivated()) {
#				$this->_show_content_without_window = true;
#			}

			$this->setupInformation();

			// TODO: include_once('include/inc_delete_entry.php');

			$label_manager = $this->_environment->getGroupManager();

			$account_mode = 'none';
			if(!empty($_GET['account'])) {
				$account_mode = $_GET['account'];
			}
			
			// use agb datasecurity
			$portal_item = $this->_environment->getCurrentPortalItem();
			
			$agb_checked = $_GET['agb']; // true or false
			
			if($portal_item->getAGBStatus() == 2 and $portal_item->withAGBDatasecurity()){
				if($agb_checked == "true"){
					$check_agb_ds = true;
				} else {
					$check_agb_ds = false;
				}
			} else {
				$check_agb_ds = true;
			}
			

			$option = 'none';
			if(isset($_POST['option'])) {
				$option = $_POST['option'];
			}

			if(isOption($option, CS_OPTION_JOIN)) {
			}

			$type = $this->_item->getItemType();

			// check for correct type
			if($type !== CS_GROUP_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				// used to signal which "craetor infos" of annotations are expanded...
				$creatorInfoStatus = array();
				if(!empty($_GET['creator_info_max'])) {
					$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
				}

				// initialize
				$current_user = $this->_environment->getCurrentUser();

				// check for deleted
				if($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
				}

				// check for visibility
				elseif(!$this->_item->maySee($current_user)) {
					// TODO: implement error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
					 */
				}

				else {
					// enter or leave group
					if(!empty($_GET['group_option'])) {
						if($_GET['group_option'] === '1') {

							$room_item = $this->_item->getGroupRoomItem();
   								if ( isset($room_item) and !empty($room_item) ) {
      								$session = $environment->getSessionItem();
      								$params['iid']= $this->_item->getItemID();

      // build new user_item
      if ( (!$room_item->checkNewMembersWithCode()
           or ( $room_item->getCheckNewMemberCode() == $_GET/*$_POST*/['code']))
           and $check_agb_ds
           
         ) {
         $current_user = $environment->getCurrentUserItem();
         $user_item = $current_user->cloneData();
         $picture = $current_user->getPicture();
         $user_item->setContextID($room_item->getItemID());
         if ( !empty($picture) ) {
            $value_array = explode('_',$picture);
            $value_array[0] = 'cid'.$user_item->getContextID();
            $new_picture_name = implode('_',$value_array);
            $disc_manager = $environment->getDiscManager();
            $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
            $user_item->setPicture($new_picture_name);
         }
         if (isset($_POST['description_user'])) {
            $user_item->setUserComment($_POST['description_user']);
         }

         //check room_settings
         if ( !$room_item->checkNewMembersNever()
              and !$room_item->checkNewMembersWithCode()
            ) {
            $user_item->request();
            $check_message = 'YES'; // for mail body
            $account_mode = 'info';
         } else {
            $user_item->makeUser(); // for mail body
            $check_message = 'NO';
            $account_mode = 'to_room';
         }
         
         if($portal_item->getAGBStatus() == 2 and $portal_item->withAGBDatasecurity()){
         	if($agb_checked){
         		$user_item->setAGBAcceptance();
         	}
         }

         // test if user id allready exist (reload page)
         $user_id = $user_item->getUserID();
         $user_test_item = $room_item->getUserByUserID($user_id,$user_item->getAuthSource());
         if ( !isset($user_test_item)
              and mb_strtoupper($user_id, 'UTF-8') != 'GUEST'
              and mb_strtoupper($user_id, 'UTF-8') != 'ROOT'
            ) {
            $user_item->save();
            $user_item->setCreatorID2ItemID();

            // save task
            if ( !$room_item->checkNewMembersNever()
                 and !$room_item->checkNewMembersWithCode()
               ) {
               $task_manager = $environment->getTaskManager();
               $task_item = $task_manager->getNewItem();
               $current_user = $environment->getCurrentUserItem();
               $task_item->setCreatorItem($current_user);
               $task_item->setContextID($room_item->getItemID());
               $task_item->setTitle('TASK_USER_REQUEST');
               $task_item->setStatus('REQUEST');
               $task_item->setItem($user_item);
               $task_item->save();
            }

            // send email to moderators if necessary
            $user_list = $room_item->getModeratorList();
            $email_addresses = array();
            $moderator_item = $user_list->getFirst();
            $recipients = '';
            while ( $moderator_item ) {
               $want_mail = $moderator_item->getAccountWantMail();
               if ( !empty($want_mail) and $want_mail == 'yes' ) {
                  $email_addresses[] = $moderator_item->getEmail();
                  $recipients .= $moderator_item->getFullname().LF;
               }
               $moderator_item = $user_list->getNext();
            }

            // language
            $language = $room_item->getLanguage();
            if ( $language == 'user' ) {
               $language = $user_item->getLanguage();
               if ( $language == 'browser' ) {
                  $language = $environment->getSelectedLanguage();
               }
            }

            if ( count($email_addresses) > 0 ) {
               $save_language = $translator->getSelectedLanguage();
               $translator->setSelectedLanguage($language);
               $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
               $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
               $body .= LF.LF;
               
               // Datenschutz
               if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
               	$userid = 'XXX (aus Datenschutzgründen verborgen)';
               } else {
               	$userid = $user_item->getUserID();
               }
               
               $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$userid,$user_item->getEmail(),$room_item->getTitle());
               $body .= LF.LF;

               switch ( $check_message )
               {
                   case 'YES':
                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                     break;
                   case 'NO':
                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                     break;
                   default:
                     break;
               }

               $body .= LF.LF;
               if ( !empty($_POST['description_user']) ) {
                  $body .= $translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$_POST['description_user']);
                  $body .= LF.LF;
               }
               $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
               if ( !$room_item->checkNewMembersNever() ) {
                  $body .= LF;
                  $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID().'&mod=account&fct=index&selstatus=1';
               } else {
                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
               }
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to(implode(',',$email_addresses));

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

               $current_context = $environment->getCurrentContextItem();
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
               $mail->set_reply_to_name($user_item->getFullname());
               $mail->set_reply_to_email($user_item->getEmail());
               $mail->set_subject($subject);
               $mail->set_message($body);
               $mail->send();
               $translator->setSelectedLanguage($save_language);
            }

            // send email to user when account is free automatically
            // and make member of the group in the group room
            if ( $user_item->isUser() ) {

               // make member
               $this->_item->addMember($current_user);

               // get contact moderator (TBD) now first contect moderator
               $user_list = $room_item->getContactModeratorList();
               $contact_moderator = $user_list->getFirst();

               // change context to group room
               $translator->setEmailTextArray($room_item->getEmailTextArray());
               $translator->setContext(CS_GROUPROOM_TYPE);
               $save_language = $translator->getSelectedLanguage();

               // language
               $language = $room_item->getLanguage();
               if ( $language == 'user' ) {
                  $language = $user_item->getLanguage();
                  if ( $language == 'browser' ) {
                     $language = $environment->getSelectedLanguage();
                  }
               }

               $translator->setSelectedLanguage($language);

               // email texts
               $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
               $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
               $body .= LF.LF;
               $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
               $body .= LF.LF;
               
               // Datenschutz
               if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
               	$userid = ' ';
               } else {
               	$userid = $user_item->getUserID();
               }
               
               $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room_item->getTitle());
               $body .= LF.LF;
               $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
               $body .= LF.LF;
               $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();

               // send mail to user
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to($user_item->getEmail());
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

               $mail->set_reply_to_email($contact_moderator->getEmail());
               $mail->set_reply_to_name($contact_moderator->getFullname());
               $mail->set_subject($subject);
               $mail->set_message($body);
               $mail->send();
            }
         }
      } elseif ( $room_item->checkNewMembersWithCode()
                 and $room_item->getCheckNewMemberCode() != $_GET/*$_POST*/['code']
               ) {
         $account_mode = 'member';
         $error = 'code';
      }
      if ( $account_mode == 'to_room' ) {
         redirect($room_item->getItemID(), 'home', 'index', '');
      } else {
         $params['account'] = $account_mode;
         if ( isset($error) and !empty($error) ) {
            $params['error'] = $error;
         }
         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),'detail',$params);
      }
   }else{
							$this->_item->addMember($current_user);
							if($this->_environment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() === '1') {
								$wiki_manager = $this->_environment->getWikiManager();
								$wiki_manager->updateNotification();
							}

   }







						} elseif($_GET['group_option'] === '2') {
							$this->_item->removeMember($current_user);
							if($this->_environment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() === '1') {
								$wiki_manager = $this->_environment->getWikiManager();
								$wiki_manager->updateNotification();
							}

							if($this->_item->isGroupRoomActivated()) {
								$grouproom_item = $this->_item->getGroupRoomItem();
								if(isset($grouproom_item) && !empty($grouproom_item)) {
									$group_room_user_item = $grouproom_item->getUserByUserID($current_user->getUserID(), $current_user->getAuthSource());
									$group_room_user_item->delete();
									$group_room_user_item->save();
								}
							}
						}
					} else {

						if($this->_item->isGroupRoomActivated()) {
							$grouproom_item = $this->_item->getGroupRoomItem();
							if(isset($grouproom_item) && !empty($grouproom_item)) {
								if( $grouproom_item->checkNewMembersWithCode()) {
									$this->assign("join", "code", true);
								}
								if($grouproom_item->getAGBStatus() != 2){
									$this->assign("join", "agb", true);
								}
							}
						}
					}

					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					/*
					 * // Create view
      $context_item = $environment->getCurrentContextItem();
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $params['creator_info_status'] = $creatorInfoStatus;
      $detail_view = $class_factory->getClass(GROUP_DETAIL_VIEW,$params);
      unset($params);
      $detail_view->setItem($group_item);

      #######################################
      # FLAG: group room
      #######################################
      $detail_view->setAccountMode($account_mode);
      #######################################
      # FLAG: group room
      #######################################
					 */

					/*
					 *



      // Set up rubric connections and browsing
      if ( $context_item->withRubric(CS_USER_TYPE) ) {
         $ids = $group_item->getLinkedItemIDArray(CS_USER_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $ids);
      }
      $rubric_connections = array();
      if ( $context_item->withRubric(CS_TOPIC_TYPE) ) {
         $ids = $group_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
         $rubric_connections = array(CS_TOPIC_TYPE);
      }
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            switch ($link_name[0]) {
               case CS_ANNOUNCEMENT_TYPE:
                  $ids = $group_item->getLinkedItemIDArray(CS_ANNOUNCEMENT_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids', $ids);
                  $rubric_connections[] = CS_ANNOUNCEMENT_TYPE;
                  break;
               case 'todo':
                  $context = $environment->getCurrentContextItem();
                  if ($context->withRubric(CS_TODO_TYPE)){
                     $ids = $group_item->getLinkedItemIDArray(CS_TODO_TYPE);
                     $session->setValue('cid'.$environment->getCurrentContextID().'_todo_index_ids', $ids);
                     $rubric_connections[] = CS_TODO_TYPE;
                  }
                  break;
               case CS_DATE_TYPE:
                  $ids = $group_item->getLinkedItemIDArray(CS_DATE_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_dates_index_ids', $ids);
                  $rubric_connections[] = CS_DATE_TYPE;
                  break;
               case 'material':
                  $ids = $group_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $ids);
                  $rubric_connections[] = CS_MATERIAL_TYPE;
                  break;
               case 'discussion':
                  $ids = $group_item->getLinkedItemIDArray(CS_DISCUSSION_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids', $ids);
                  $rubric_connections[] = CS_DISCUSSION_TYPE;
                  break;
            }
         }
      }
      $detail_view->setRubricConnections($rubric_connections);

      // highlight search words in detail views
      $session_item = $environment->getSessionItem();
      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
         if ( !empty($search_array['search']) ) {
            $detail_view->setSearchText($search_array['search']);
         }
         unset($search_array);
      }

      // Add view to page ... and done
      $page->add($detail_view);
					 */

					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/


		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_group_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_group_index_ids'));
			}
		}

		protected function setItem() {
			// try to set the item
			if(!empty($_GET['iid'])) {
				$current_item_id = $_GET['iid'];
			} else {
				include_once('functions/error_functions.php');
				trigger_error('An item id must be given.', E_USER_ERROR);
			}

			$this->_manager = $this->_environment->getGroupManager();
			$this->_item = $this->_manager->getItem($current_item_id);
			
			//$this->checkNotSet();
		}

		protected function getAdditionalActions(&$return) {
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$is_in_group = $current_user->isInGroup($this->_item);
	        $grouproom_item = $this->_item->getGroupRoomItem();
	        if ( isset($grouproom_item) and !empty($grouproom_item) ) {
	            if ( $grouproom_item->isUser($current_user) ) {
	               if ($this->_item->isSystemLabel() or ($grouproom_item->isLastModeratorByUserID($current_user->getUserID(),$current_user->getAuthSource())) ) {
						if ($is_in_group) {
							$return['member'] = 'member_false';
						} else {
							$return['member'] = 'no_member_false';

						}
	               } else {
						if ($is_in_group) {
							$return['member'] = 'member';
						} else {
							$return['member'] = 'no_member';

						}
			        }
	            } else {
	               $grouproom_user_item = $grouproom_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
	               if ( !empty($grouproom_user_item)
	                    and ( $grouproom_user_item->isRequested()
	                          or $grouproom_user_item->isRejected()
	                        )
	                  ) {
						if ($is_in_group) {
							$return['member'] = 'member_false';
						} else {
							$return['member'] = 'no_member_false';

						}
	               } else {
						if ($is_in_group) {
							$return['member'] = 'member';
						} else {
							$return['member'] = 'no_member';

						}
	               }
	            }
	         }else{
	         	if ($this->_item->isSystemLabel()){
	         		$return['member'] = 'member_false';
	         	}else{
					if ($is_in_group) {
						$return['member'] = 'member';
					} else {
						$return['member'] = 'no_member';

					}
	         	}

	        }
			return $return;
		}
		//TODO: redfine getDetailActions
		/*
		 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= $this->_getEditAction($item,$current_user);
      $html .= $this->_getDetailItemActionsAsHTML($item).'&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getPrintAction($item,$current_user);
      $html .= $this->_getMailAction($item,$current_user,type2Module(CS_GROUP_TYPE));
      $html .= $this->_getDownloadAction($item,$current_user);
      $html .= $this->_getNewAction($item,$current_user);

      $html .= $this->_initDropDownMenus();
      return $html;
		 */

		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$return = array();
			$return['title'] = $this->_item->getTitle();
			$return['show_picture'] = false;
			$return['moredetails'] = $this->getCreatorInformationAsArray($this->_item);

/*			if($this->_show_content_without_window) {
				// TODO:
				/*
				 * $grouproom_item = $item->getGroupRoomItem();
         if ( isset($grouproom_item) and !empty($grouproom_item) ) {
            $html .= $this->_getRoomWindowAsHTML($grouproom_item,$this->_account_mode);
         }
				 */
/*			} else {
*/
				//$html  .='<table style="width:100%; border-collapse:collapse; border:0px solid black;" summary="Layout"><tr><td>';


				// picture
				$picture = $this->_item->getPicture();
				if(!empty($picture)) {
					$disc_manager = $this->_environment->getDiscManager();
					$width = 150;
					if($disc_manager->existsFile($picture)) {
						list($pict_width) = getimagesize($disc_manager->getFilePath() . $picture);

						if($pict_width < 150) {
							$width = $pict_width;
						}
					}

					$return['show_picture'] = true;
					$return['picture'] = $picture;
				}

				// description
				$desc = $this->_item->getDescription();
				if(!empty($desc)) {
					$desc = $converter->textFullHTMLFormatting($desc);
					//$desc = $converter->cleanDataFromTextArea($desc);
					// TODO: implement
					//$desc = $converter->compareWithSearchText($desc);
					$converter->setFileArray($this->getItemFileList());
      			if ( $this->_with_old_text_formating ) {
      				$desc = $converter->textFullHTMLFormatting($desc);
      			}
					//$html .= $this->getScrollableContent($desc,$item,'',true).LF;

					$return['description'] = $desc;
				}

				// description
				$return['show_grouproom'] = false;
				$grouproom_item = $this->_item->getGroupRoomItem();
				if(isset($grouproom_item) && !empty($grouproom_item) and $this->_item->isGroupRoomActivated()) {
					//$desc = $converter->cleanDataFromTextArea($desc);
					// TODO: implement
					//$desc = $converter->compareWithSearchText($desc);
					$converter->setFileArray($this->getItemFileList());
					//$desc = $converter->text_as_html_long($desc);
					//$html .= $this->getScrollableContent($desc,$item,'',true).LF;

					$return['grouproom_description'] = $grouproom_item->getDescription();
					$return['grouproom_item_id'] = $grouproom_item->getItemID();
					$return['grouproom_title'] = $grouproom_item->getTitle();
					$return['show_grouproom'] = true;
					$morderator_list = $grouproom_item->getModeratorList();
					$morderator_item = $morderator_list->getFirst();
					$moderator_string = '';
					while($morderator_item){
						$moderator_string .= $morderator_item->getFullname();
						$morderator_item = $morderator_list->getNext();
						if($morderator_item){
							$moderator_string .=', ';
						}
					}
					$return['grouproom_moderators'] = $moderator_string;
				    $user_manager = $this->_environment->getUserManager();
				    $user_manager->setUserIDLimit($current_user->getUserID());
				    $user_manager->setAuthSourceLimit($current_user->getAuthSource());
				    $user_manager->setContextLimit($grouproom_item->getItemID());
				    $user_manager->select();
				    $user_list = $user_manager->get();
				    if (!empty($user_list)){
				       $room_user = $user_list->getFirst();
				    } else {
				       $room_user = '';
				    }
			        if ($current_user->isRoot()) {
			           $may_enter = true;
			        } elseif ( !empty($room_user) ) {
			           $may_enter = $grouproom_item->mayEnter($room_user);
			        } else {
			           $may_enter = false;
			        }
                 if(!empty($room_user) and $room_user->isRequested()) {
                    $return['grouproom_user_request'] = true;
                 } else {
                    $return['grouproom_user_request'] = false;
                 }
               
					$return['grouproom_may_enter'] = $may_enter;
				}

				// members
				$members = $this->_item->getMemberItemList();
				$count_member = $members->getCount();

				if(!$members->isEmpty()) {
					$member = $members->getFirst();

					while($member) {
						$member_info = array();

						if($member->isUser()) {
							$firtsname = $member->getFirstname();
							// TODO:
							//$linktext = $converter->compareWithSearchText($linktext);
							$linktext = $converter->text_as_html_short($linktext);

							$member_title = $member->getTitle();
							// TODO:
							//$member_title = $converter->compareWithSearchText($member_title);
							$member_title = $converter->text_as_html_short($member_title);

							if(!empty($member_title)) {
								$linktext .= ', ' . $member_title;
							}
							$member_info['linktext'] = $linktext;
							$member_info['firstname'] = $converter->text_as_html_short($member->getFirstname());
							$member_info['lastname'] = $converter->text_as_html_short($member->getLastname());
							$member_info['title'] = $converter->text_as_html_short($member->getTitle());
							$member_info['picture'] =$member->getPicture();
							$member_info['iid'] = $member->getItemID();
						    $return['members'][] = $member_info;
						}


						unset($member);
						$member = $members->getNext();
					}
			/*	} */

				/*
				 *

	            $html .='<ul style="list-style-position:inside; font-size:10pt; padding-left:0px; margin-left:20px; margin-top:0px; margin-bottom:20px; padding-bottom:0px;">  '.LF;
	            $html .= $html1.LF;
	            $html .= '</ul>'.LF;


	            if (!empty($html2)){
	               $html .= '<td style="vertical-align:top;">'.LF;
	               $html .='<ul style="list-style-position:inside; font-size:10pt; padding-left:0px; margin-left:20px; margin-top:0px; margin-bottom:20px; padding-bottom:0px;">  '.LF;
	               $html .= $html2;
	               $html .= '</ul>'.LF;
	               $html .= '</td>'.LF;
	            }
	            if (!empty($html3)){
	               $html .= '<td style="vertical-align:top;">'.LF;
	               $html .='<ul style="list-style-position:inside; font-size:10pt; padding-left:0px; margin-left:20px; margin-top:0px; margin-bottom:20px; padding-bottom:0px;">  '.LF;
	               $html .= $html3;
	               $html .= '</ul>'.LF;
	               $html .= '</td>'.LF;
	            }

	            */





				/*

	            // Foren
	            $context_item = $this->_environment->getCurrentContextItem();
	            if($context_item->WikiEnableDiscussionNotificationGroups() == 1){
	               $discussions = $item->getDiscussionNotificationArray();
	               if ( isset($discussions[0]) ) {
	                  $html .= '<h3>'.$this->_translator->getMessage('GROUP_DISCUSSIONS').'</h3>'.LF;
	                  $html .= '<ul>'.LF;
	                  foreach($discussions as $discussion){
	                        $html .= '   <li>' . $discussion . '</li>'.LF;

	                  }
	                  $html .= '</ul>'.LF;
	               }
	            }
				 */
			}
			return $return;
		}
	}