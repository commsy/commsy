<?php
class cs_popup_configuration_mail_controller {
	private $_environment = null;
	private $_popup_controller = null;

	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}

	public function save($form_data, $additional) {
		
	}

	public function initPopup() {
		$current_context = $this->_environment->getCurrentContextItem();
		$user_manager = $this->_environment->getUserManager();
		$translator = $this->_environment->getTranslationObject();
		
		var_dump($this->_data);
		
		//$admin = $user_manager->get
		

		/*

         $admin = $user_manager->getItem($this->_action_array['user_item_id']);
         if ( !isset($admin) ) {
            $admin = $this->_environment->getCurrentUserItem();
         }
         $room = $this->_environment->getCurrentContextItem();
         $url_to_room = LF.LF;
         $url_to_room .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();

         $array_user_item_id = $this->_action_array['selected_ids'];
         $this->_name = '';
         $first = true;
         foreach ($array_user_item_id as $id) {
            if ($first) {
               $first = false;
            } else {
               $this->_name .= LF;
            }
            $user = $user_manager->getItem($id);
            if ( !empty($user) ) {
               if($user->isEmailVisible()){
                  $this->_name .= $user->getFullname().' ('.$user->getEmail().')';
               } else {
                  $this->_name .= $user->getFullname().' ('.$translator->getMessage('USER_EMAIL_HIDDEN').')';
               }
            }
         }
         $translate = false;
         if ( count($array_user_item_id) == 1 ) {
            $translate = true;
         }

         if ( $translate ) {
            $this->_content  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
         } else {
            $this->_content  = $translator->getEmailMessage('MAIL_BODY_HELLO');
         }
         $this->_content .= LF.LF;

         // now prepare for each action separately
         if ( $this->_action_array['action'] == 'USER_ACCOUNT_DELETE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_DELETE');
            $this->_warning  = $translator->getMessage('INDEX_ACTION_FORM_USER_ACCOUNT_DELETE_WARNING');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$room->getTitle());
            if ( $translate ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_ACCOUNT_LOCK' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_LOCK');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$room->getTitle());
            if ( $translate ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_ACCOUNT_FREE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_FREE');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_STATUS_USER' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_USER');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_STATUS_MODERATOR' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_MODERATOR');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_MAKE_CONTACT_PERSON' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_MAKE_CONTACT_PERSON');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_UNMAKE_CONTACT_PERSON');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',$user->getUserID(),$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_SEND' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_EMAIL_SEND');
            $this->_subject  = '';
            $this->_content  = LF.LF.LF;
            if ( $this->_environment->getCurrentModule() == 'account' ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            } else { // user: send mail
               $this->_content .= $translator->getMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            }
            $this->_content .= $url_to_room;
            $this->_with_copy_mod = true;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_PASSWORD');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_PASSWORD',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_PASSWORD',$room->getTitle(),$user->getUserID());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_PASSWORD');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_MERGE');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_MERGE',$room->getTitle());
            if ($translate) {
               $account_text = '';
               $user_manager->resetLimits();
               $user_manager->setContextLimit($this->_environment->getCurrentContextID());
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
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_MERGE',$user->getEMail(),$room->getTitle(),$account_text);
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_MERGE');
               $content_temp = str_replace('%3','%4',$content_temp);
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
            }
            $this->_content .= $content_temp;
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
		 */

		// assign template vars
		$this->assignTemplateVars();
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

	private function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_user = $this->_environment->getPortalUserItem();


		$this->_popup_controller->assign('popup', 'general', $general_information);
	}
}