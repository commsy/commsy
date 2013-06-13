<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_configuration_mail_controller implements cs_popup_controller {
	private $_environment = null;
	private $_popup_controller = null;

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional = array()) {
		
	}

	public function initPopup($data) {
		$current_context = $this->_environment->getCurrentContextItem();
		$user_manager = $this->_environment->getUserManager();
		$translator = $this->_environment->getTranslationObject();
		
		$this->assignTemplateVars($data['ids'], $data["action"]);
	}

	public function getFieldInformation($sub) {


		// TODO
		// form_data[communityrooms} is mendatory if the following is true
		/*
		 * if($this->_environment->inProjectRoom()) {
			// project room
			if(!empty($this->_community_room_array)) {
				$portal_item = $this->_environment->getCurrentPortalItem();
				$project_room_link_status = $portal_item->getProjectRoomLinkStatus();
		 */

		$return = array(
			'newsletter'	=> array(
				array('name' => 'newsletter', 'type' => 'radio', 'mandatory' => true)
			),
			'merge'	=> array(
				array('name' => 'merge_user_id', 'type' => 'text', 'mandatory' => false),
				array('name' => 'merge_user_password', 'type' => 'text', 'mandatory' => false)
			),
			'account'	=> array(
				array('name' => 'forename', 'type' => 'text', 'mandatory' => true),
				array('name' => 'surname', 'type' => 'text', 'mandatory' => true),
				array('name' => 'user_id', 'type' => 'text', 'mandatory' => true),
				array('name' => 'old_password', 'type' => 'text', 'mandatory' => false),
				array('name' => 'new_password', 'type' => 'text', 'mandatory' => false, 'same_as' => 'new_password_confirm'),
				array('name' => 'new_password_confirm', 'type' => 'text', 'mandatory' => true),
				array('name' => 'language', 'type' => 'select', 'mandatory' => true),
				array('name' => 'mail_account', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail_room', 'type' => 'checkbox', 'mandatory' => false),
				array('name' => 'upload', 'type' => 'radio', 'mandatory' => true),
				array('name' => 'auto_save', 'type' => 'checkbox', 'mandatory' => true),
			),
			'user'			=> array(
				array('name' => 'title','type' => 'text', 'mandatory' => false), array('name' => 'title_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'birthday','type' => 'text', 'mandatory' => false), array('name' => 'birthday_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'picture','type' => 'file', 'mandatory' => false), array('name' => 'picture_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'mail','type' => 'mail', 'mandatory' => true), array('name' => 'mail_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'telephone','type' => 'text', 'mandatory' => false), array('name' => 'telephone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'cellularphone','type' => 'text', 'mandatory' => false), array('name' => 'cellularphone_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'street','type' => 'text', 'mandatory' => false), array('name' => 'street_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'zipcode','type' => 'numeric', 'mandatory' => false), array('name' => 'zipcode_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'city','type' => 'text', 'mandatory' => false), array('name' => 'city_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'room','type' => 'text', 'mandatory' => false), array('name' => 'room_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'organisation','type' => 'text', 'mandatory' => false), array('name' => 'organisation_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'position','type' => 'text', 'mandatory' => false), array('name' => 'position_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'icq','type' => 'numeric', 'mandatory' => false),
				array('name' => 'msn','type' => 'text', 'mandatory' => false),
				array('name' => 'skype','type' => 'text', 'mandatory' => false),
				array('name' => 'yahoo','type' => 'text', 'mandatory' => false),
				array('name' => 'jabber','type' => 'text', 'mandatory' => false), array('name' => 'messenger_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'homepage','type' => 'text', 'mandatory' => false), array('name' => 'homepage_all','type' => 'checkbox', 'mandatory' => false),
				array('name' => 'description','type' => 'text', 'mandatory' => false), array('name' => 'description_all','type' => 'checkbox', 'mandatory' => false),
			),
			'user_picture'	=> array(
			),
		);

		return $return[$sub];
	}

	private function assignTemplateVars($ids, $action) {
		$translator = $this->_environment->getTranslationObject();
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_user = $this->_environment->getPortalUserItem();
		$user_manager = $this->_environment->getUserManager();
		
		// receiver
		$receiver = array();
		$user = null;
		foreach($ids as $id) {
			$user = $user_manager->getItem($id);
			if(!empty($user)) {
				if($user->isEmailVisible()) {
					$receiver[] = $user->getFullname() . " (" . $user->getEmail() . ")";
				} else {
					$receiver[] = $user->getFullname() . " (" . $translator->getMessage("USER_EMAIL_HIDDEN") . ")";
				}
			}
		}
		$this->_popup_controller->assign('popup', 'receiver', $receiver);
		
		// send mail checkbox
		$this->_popup_controller->assign('popup', 'send_mail_checkbox', ($action !== "email"));
		
		$admin = $this->_environment->getCurrentUserItem();
		
		// cc / bcc
		$cc_bcc = array(
			array(
				"text"		=> $translator->getMessage("INDEX_ACTION_FORM_CC"),
				"value"		=> "cc"
			),
			array(
				"text"		=> $translator->getMessage("INDEX_ACTION_FORM_BCC", $admin->getFullname()),
				"value"		=> "bcc"
			),
			array(
				"text"		=> $translator->getMessage("INDEX_ACTION_FORM_CC_MODERATOR"),
				"value"		=> "cc_moderator"
			),
			array(
				"text"		=> $translator->getMessage("INDEX_ACTION_FORM_BCC_MODERATOR"),
				"value"		=> "bcc_moderator"
			)
		);
		$this->_popup_controller->assign('popup', 'cc_bcc', $cc_bcc);
		
		// specific text
		$room = $this->_environment->getCurrentContextItem();
		$url_to_room = LF.LF;
        $url_to_room .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();
        
        $needTranslation = (sizeof($ids) === 1) ? true : false;
        
        if($needTranslation) {
        	$content = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
        } else {
        	$content = $translator->getEmailMessage('MAIL_BODY_HELLO');
        }
        
        $subject = "";
        $content .= LF.LF;
        
        // Datenschutz
        if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
        	$userid = ' ';
        } else {
        	$userid = $user->getUserID();
        }
        
        switch($action) {
        	case "delete":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_DELETE');
            $this->_warning  = $translator->getMessage('INDEX_ACTION_FORM_USER_ACCOUNT_DELETE_WARNING');
        		 */
        		break;
        	
        	case "lock":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_LOCK');
        		 */
        		break;
        	
        	case "free":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_FREE');
        		 */
        		break;
        	
        	case "status_user":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_USER');
        		 */
        		break;
        	
        	case "status_moderator":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_MODERATOR');
        		 */
        		break;
        	
        	case "status_contact_moderator":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_MAKE_CONTACT_PERSON');
        		 */
        		break;
        	
        	case "status_no_contact_moderator":
        		$subject = $translator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON',$room->getTitle());
        		
        		if($needTranslation) {
        			$content .= $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',$userid,$room->getTitle());
        		} else {
        			$content_temp = $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON');
        			$content_temp = str_replace('%2','%3',$content_temp);
        			$content_temp = str_replace('%1','%2',$content_temp);
        			$content .= $content_temp;
        		}
        		
        		$content .= LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_UNMAKE_CONTACT_PERSON');
        		 */
        		break;
        	
        	case "email":
        		$content = LF.LF.LF;
        		$content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
        		$content .= $url_to_room;
        		
        		/*
        		 * $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_EMAIL_SEND');
        		 */
        		break;
        }
        
        $specific = array(
        	"subject"		=> $subject,
        	"content"		=> $content
        );
        $this->_popup_controller->assign('popup', 'specific', $specific);
        
        // submit translation
        if($action !== "email") {
        	$translation = "";
        	
        	switch($action) {
        		case "delete":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_DELETE_BUTTON');
        			break;
        			 
        		case "lock":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_FREE_BUTTON');
        			break;
        			 
        		case "free":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_LOCK_BUTTON');
        			break;
        			 
        		case "status_user":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_USER_BUTTON');
        			break;
        			 
        		case "status_moderator":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_MODERATOR_BUTTON');
        			break;
        			 
        		case "status_contact_moderator":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_MAKE_CONTACT_PERSON_BUTTON');
        			break;
        			 
        		case "status_no_contact_moderator":
        			$translation = $translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
        			break;
        	}
        	
        	$this->_popup_controller->assign('popup', 'submit_translation', $translation);
        }
	}
}