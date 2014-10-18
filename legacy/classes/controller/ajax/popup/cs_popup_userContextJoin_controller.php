<?php

require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');
require_once ('classes/cs_mail.php');

class cs_popup_userContextJoin_controller implements cs_rubric_popup_controller {
	private $_receiver_array = null;
	private $_environment = null;
	private $_popup_controller = null;

	/**
	 * constructor
	 */
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function getFieldInformation($sub = '') {
		return array(
		);
	}

	public function save($form_data, $additional = array()) {
        switch ($additional['action']){
		   case 'context_join':
		      //---
		      $room_manager = $this->_environment->getRoomManager();
		      $room_item = $room_manager->getItem($form_data['iid']);
		      $current_item_id = $form_data['iid'];
		      if(empty($room_item)){
		      	$grouproom_flag = true;
		      	$room_item = $room_manager->getItem($additional['context_id']);
		      	$current_item_id = $additional['context_id'];
		      	// label item holen und addmember ausführen wenn kein member
		      	$label_manager = $this->_environment->getLabelManager();
		      	$label_item = $label_manager->getItem($form_data['iid']);
		      }
		      $translator = $this->_environment->getTranslationObject();
		      $portal_item = $this->_environment->getCurrentPortalItem();
		      $agb_flag = false;
		      
		      
		      if($portal_item->withAGBDatasecurity()){
				if($room_item->getAGBStatus() == 1){
					if($form_data['agb']){
						$agb_flag = true;
					} else {
						$agb_flag = false;
					}
				} else {
					$agb_flag = true;
				}
			  } else {
			  	$agb_flag = true;
			  }
			  #pr($agb_flag);
			  
		      // build new user_item
		      if ( (!$room_item->checkNewMembersWithCode()
		      or ( $room_item->getCheckNewMemberCode() == $form_data['code'])
		      or ( $room_item->getCheckNewMemberCode() and !empty($form_data['description_user']))
		      			) and $agb_flag
		      ) {
		         $current_user = $this->_environment->getCurrentUserItem();
		         $private_room_user_item = $current_user->getRelatedPrivateRoomUserItem();
		         if ( isset($private_room_user_item) ) {
		            $user_item = $private_room_user_item->cloneData();
		            $picture = $private_room_user_item->getPicture();
		         } else {
		            $user_item = $current_user->cloneData();
		            $picture = $current_user->getPicture();
		         }
		         $user_item->setContextID($current_item_id);
		         if (!empty($picture)) {
		            $value_array = explode('_',$picture);
		            $value_array[0] = 'cid'.$user_item->getContextID();
		      
		            $new_picture_name = implode('_',$value_array);
		            $disc_manager = $this->_environment->getDiscManager();
		            $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
		            $user_item->setPicture($new_picture_name);
		         }
		         if (isset($form_data['description_user'])) {
		            $user_item->setUserComment($form_data['description_user']);
		         }

		         //check room_settings
		         if ( (!$room_item->checkNewMembersNever() and !$room_item->checkNewMembersWithCode())
		         or ($room_item->checkNewMembersWithCode() and $room_item->getCheckNewMemberCode() != $form_data['code'])
		         ) {
		            $user_item->request();
		            $check_message = 'YES'; // for mail body
		            $account_mode = 'info';
		            
		         } else {
		            $user_item->makeUser(); // for mail body
		            $check_message = 'NO';
		            $account_mode = 'to_room';
		            // save link to the group ALL
		            $group_manager = $this->_environment->getLabelManager();
		            $group_manager->setExactNameLimit('ALL');
		            $group_manager->setContextLimit($current_item_id);
		            $group_manager->select();
		            $group_list = $group_manager->get();
		            if ($group_list->getCount() == 1) {
		               $group = $group_list->getFirst();
		               $group->setTitle('ALL');
		               $user_item->setGroupByID($group->getItemID());
		            }
		            
		            if(isset($label_item) and !empty($label_item)){
		            	if(!$label_item->isMember($current_user)){
		            		$label_item->addMember($current_user);
		            	}
		            }
		         }
		         
		         if($portal_item->withAGBDatasecurity()){
		         	if($room_item->getAGBStatus()){
		         		if($form_data['agb']){
		         			$user_item->setAGBAcceptance();
		         		}
		         	}
		         }
		      
		         // test if user id already exists (reload page)
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
		               $task_manager = $this->_environment->getTaskManager();
		               $task_item = $task_manager->getNewItem();
		               $current_user = $this->_environment->getCurrentUserItem();
		               $task_item->setCreatorItem($current_user);
		               $task_item->setContextID($room_item->getItemID());
		               $task_item->setTitle('TASK_USER_REQUEST');
		               $task_item->setStatus('REQUEST');
		               $task_item->setItem($user_item);
		               $task_item->save();
		            }
		      
		            // send email to moderators if necessary
		            $user_manager = $this->_environment->getUserManager();
		            $user_manager->resetLimits();
		            $user_manager->setModeratorLimit();
		            $user_manager->setContextLimit($current_item_id);
		            $user_manager->select();
		            $user_list = $user_manager->get();
		            $email_addresses = array();
		            $moderator_item = $user_list->getFirst();
		            $recipients = '';
		            while ($moderator_item) {
		               $want_mail = $moderator_item->getAccountWantMail();
		               if (!empty($want_mail) and $want_mail == 'yes') {
		                  $email_addresses[] = $moderator_item->getEmail();
		                  $recipients .= $moderator_item->getFullname()."\n";
		               }
		               $moderator_item = $user_list->getNext();
		            }
		      
		            // language
		            $language = $room_item->getLanguage();
		            if ($language == 'user') {
		               $language = $user_item->getLanguage();
		               if ($language == 'browser') {
		                  $language = $this->_environment->getSelectedLanguage();
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
		               	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
		               } else {
		               	$userid = $user_item->getUserID();
		               }
		               $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$userid,$user_item->getEmail(),$room_item->getTitle());
		               $body .= LF.LF;
		      
		               $tempMessage = "";
		               switch ( cs_strtoupper($check_message) ) {
		                  case 'YES':
		                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
		                     break;
		                  case 'NO':
		                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
		                     break;
		                  default:
		                     $body .= $translator->getMessage('COMMON_MESSAGETAG_ERROR')." context_detail(244) ";
		                  break;
		               }
		      
		               $body .= LF.LF;
		               if (!empty($form_data['description_user'])) {
		                  $body .= $translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$form_data['description_user']);
		                  $body .= LF.LF;
		               }
		               $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
		               $body .= LF;
		               if ( cs_strtoupper($check_message) == 'YES') {
		                  $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
		                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_item_id.'&mod=account&fct=index'.'&selstatus=1';
		               } else {
		                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_item_id;
		               }
		               $mail = new cs_mail();
		               $mail->set_to(implode(',',$email_addresses));
		               $server_item = $this->_environment->getServerItem();
		               $default_sender_address = $server_item->getDefaultSenderAddress();
		               if (!empty($default_sender_address)) {
		                  $mail->set_from_email($default_sender_address);
		               } else {
		                  $mail->set_from_email('@');
		               }
		               $current_context = $this->_environment->getCurrentContextItem();
		               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
		               $mail->set_reply_to_name($user_item->getFullname());
		               $mail->set_reply_to_email($user_item->getEmail());
		               $mail->set_subject($subject);
		               $mail->set_message($body);
		               $mail->send();
		               $translator->setSelectedLanguage($save_language);
		            }
		      
		            // send email to user when account is free automatically (PROJECT ROOM)
		            if ($user_item->isUser()) {
		      
		               // get contact moderator (TBD) now first moderator
		               $user_list = $room_item->getModeratorList();
		               $contact_moderator = $user_list->getFirst();
		      
		               // change context to project room
		               $translator->setEmailTextArray($room_item->getEmailTextArray());
		               $translator->setContext('project');
		               $save_language = $translator->getSelectedLanguage();
		      
		               // language
		               $language = $room_item->getLanguage();
		               if ($language == 'user') {
		                  $language = $user_item->getLanguage();
		                  if ($language == 'browser') {
		                     $language = $this->_environment->getSelectedLanguage();
		                  }
		               }
		               
		               // Datenschutz
		               if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
		               	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
		               } else {
		               	$userid = $user_item->getUserID();
		               }
		      
		               $translator->setSelectedLanguage($language);
		      
		               // email texts
		               $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
		               $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
		               $body .= LF.LF;
		               $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
		               $body .= LF.LF;
		               $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room_item->getTitle());
		               $body .= LF.LF;
		               $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
		               $body .= LF.LF;
		               $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();
		      
		               // send mail to user
		               $mail = new cs_mail();
		               $mail->set_to($user_item->getEmail());
		               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
		               $server_item = $this->_environment->getServerItem();
		               $default_sender_address = $server_item->getDefaultSenderAddress();
		               if (!empty($default_sender_address)) {
		                  $mail->set_from_email($default_sender_address);
		               } else {
		                  $mail->set_from_email('@');
		               }
		               $mail->set_reply_to_email($contact_moderator->getEmail());
		               $mail->set_reply_to_name($contact_moderator->getFullname());
		               $mail->set_subject($subject);
		               $mail->set_message($body);
		               $mail->send();
		            }
		         }
		      } elseif ( $room_item->checkNewMembersWithCode()
		      and $room_item->getCheckNewMemberCode() != $form_data['code']
		      ) {
		         $account_mode = 'member';
		         $error = 'code';
		         $this->_popup_controller->setErrorReturn(111, 'wrong_code', array());
		      } elseif (!$agb_flag and $portal_item->withAGBDatasecurity() and $room_item->getAGBStatus() == 1){
		      	 $this->_popup_controller->setErrorReturn(115, 'agb_not_accepted', array());
		      }
		      
		      if ($account_mode =='to_room'){
// 		        $this->_popup_controller->setSuccessfullItemIDReturn($form_data['iid']);
// 		      	$this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentContextID());
		      	$data['cid'] = $this->_environment->getCurrentContextID();
		      	if($label_item){
		      		$data['item_id'] = $label_item->getItemID();
		      		$data['mod'] = 'group';
		      	} else {
		      		$data['item_id'] = $room_item->getItemID();
		      		$data['mod'] = 'project';
		      	}
		      	$this->_popup_controller->setSuccessfullDataReturn($data);
		      } else {
		      	$data['cid'] = $this->_environment->getCurrentContextID();
		      	if($label_item){
		      		$data['item_id'] = $label_item->getItemID();
		      		$data['mod'] = 'group';
		      	} else {
		      		$data['item_id'] = $room_item->getItemID();
		      		$data['mod'] = 'project';
		      	}
		      	
		      	$this->_popup_controller->setSuccessfullDataReturn($data);
// 		        $this->_popup_controller->setSuccessfullItemIDReturn($this->_environment->getCurrentContextID());
		      }
		      //---
		      
		      // set return
		      
		      break;
		}
	}

	public function initPopup($item, $data) {
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_item = $this->_environment->getCurrentPortalItem();
		$translator = $this->_environment->getTranslationObject();
		
		if($item->isA('label')){
			if($item->isGroupRoomActivated()){
				$item = $item->getGroupRoomItem();
			}
		}

		// user information
		$user_information = array();
		$user_information['item_id'] = $current_user->getItemID();
		$this->_popup_controller->assign('popup', 'user', $user_information);

		$room_information = array();
		$room_information['room_id'] = $item->getItemID();
		if($item->checkNewMembersWithCode()){
		   $room_information['check_with_code'] = true;
		}
		if($portal_item->withAGBDatasecurity()){
			if($item->getAGBStatus() == 1){
				$agb_text = $item->getAGBTextArray();
				
				$agb_link = '';
				
				$room_information['agb_text'] = $agb_text[strtoupper($translator->_selected_language)];
			}
		}
		$this->_popup_controller->assign('popup', 'room', $room_information);
		
		// agb Datenschutz
		if($portal_item->withAGBDatasecurity()){
			if($item->getAGBStatus() == 1){
				$agb_information['agb_datasecurity'] = true;
			}
		}
		$this->_popup_controller->assign('popup', 'agb', $agb_information);
	}

	
	public function cleanup_session($current_iid) {
	}
}