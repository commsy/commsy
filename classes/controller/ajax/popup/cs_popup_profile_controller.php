<?php
class cs_popup_profile_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_return = '';
	private $_user = null;
	private $_config = array();
	private $_data = array();
	
	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}
	
	public function save($form_data) {
		
	}
	
	public function initPopup() {
		$current_portal_item = $this->_environment->getCurrentPortalItem();
		
		// set configuration
		$account = array();
		
		// set user item
		if($this->_environment->inCommunityRoom() || $this->_environment->inProjectRoom()) {
			$this->_user = $this->_environment->getPortalUserItem();
		} else {
			$this->_user = $this->_environment->getCurrentUserItem();
		}
		
		// disable merge form only for root
		$this->_config['show_merge_form'] = true;
		if(isset($this->_user) && $this->_user->isRoot()) {
			$this->_config['show_merge_form'] = false;
		}
		
		// auth source
		if(!isset($current_portal_item)) $current_portal_item = $this->_environment->getServerItem();
		
		#$this->_show_auth_source = $current_portal_item->showAuthAtLogin();
		# muss angezeigt werden, sonst koennen mit der aktuellen Programmierung
		# keine Acounts mit gleichen Kennungen aber unterschiedlichen Quellen
		# zusammengelegt werden
		$this->_config['show_auth_source'] = true;
		
		$auth_source_list = $current_portal_item->getAuthSourceListEnabled();
		if(isset($auth_source_list) && !$auth_source_list->isEmpty()) {
			$auth_source_item = $auth_source_list->getFirst();
			
			while($auth_source_item) {
				$this->_data['auth_source_array'][] = array(
					'value'		=> $auth_source_item->getItemID(),
					'text'		=> $auth_source_item->getTitle());
				
				$auth_source_item = $auth_source_list->getNext();
			}
		}
		$this->_data['default_auth_source'] = $current_portal_item->getAuthDefault();
		
		// password change form
		$this->_config['show_password_change_form'] = false;
		$current_auth_source_item = $current_portal_item->getAuthSource($this->_user->getAuthSource());
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangePassword()) ||
			$this->_user->isRoot()) {
			
			$this->_config['show_password_change_form'] = true;
		}
		
		// account change form
		$this->_config['show_account_change_form'] = false;
		if(	(isset($current_auth_source_item) && $current_auth_source_item->allowChangeUserID()) ||
			$this->_user->isRoot()) {
			
			$this->_config['show_account_change_form'] = true;
		}
		
		// mail form
		$this->_config['show_mail_change_form'] = false;
		if($this->_user->isModerator()) {
			$this->_config['show_mail_change_form'] = true;
		}
		
		// assign template vars
		$this->assignTemplateVars();
	}
	
	public function getReturn() {
		return $this->_return;
	}
	
	public function getFieldInformation() {
		return array(
			array(	'name'		=> 'title',
					'type'		=> 'text',
					'mandatory' => true),
			array(	'name'		=> 'description',
					'type'		=> 'text',
					'mandatory'	=> false)
		);
	}
	
	private function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		$current_user = $this->_environment->getCurrentUserItem();
		$portal_user = $this->_environment->getPortalUserItem();
		
		// general information
		$general_information = array();
		
		// max upload size
		$val = ini_get('upload_max_filesize');
		$val = trim($val);
		$last = $val[mb_strlen($val) - 1];
		switch($last) {
			case 'k':
			case 'K':
				$val *= 1024;
				break;
			case 'm':
			case 'M':
				$val *= 1048576;
				break;
		}
		$meg_val = round($val / 1048576);
		$general_information['max_upload_size'] = $meg_val;
		
		$this->_popup_controller->assign('popup', 'general', $general_information);
		
		// portal information
		$portal_information = array();
		$portal_information['portal_name'] = $this->_environment->getCurrentPortalItem()->getTitle();
		$this->_popup_controller->assign('popup', 'portal', $portal_information);
		
		// form information
		$form_information = array();
		$form_information['account'] = $this->getAccountInformation();
		$form_information['user'] = $this->getUserInformation();
		$form_information['newsletter'] = $this->getNewsletterInformation();
		$form_information['room_list'] = $this->getRoomListInformation();
		$form_information['config'] = $this->_config;
		$form_information['data'] = $this->_data;
		
		// languages
		$languages = array();
		$languages[] = array(
			'value'		=>	'browser',
			'text'		=>	$translator->getMessage('USER_BROWSER_LANGUAGE')
		);
		$languages[] = array(
			'value'		=>	'disabled',
			'text'		=>	'------------------'
		);
		
		$available_languages = $this->_environment->getAvailableLanguageArray();
		foreach($available_languages as $language) {
			$languages[] = array(
				'value'		=>	$language,
				'text'		=>	$translator->getLanguageLabelOriginally($language)
			);
		}
		
		$form_information['languages'] = $languages;
		
		$this->_popup_controller->assign('popup', 'form', $form_information);
	}
	
	private function getAccountInformation() {
		$return = array();
		
		// get data from database
		$return['firstname'] = $this->_user->getFirstname();
		$return['lastname'] = $this->_user->getLastname();
		$return['user_id'] = $this->_user->getUserID();
		$return['language'] = $this->_user->getLanguage();
		$return['email_account'] = ($this->_user->getAccountWantMail() === 'yes') ? true : false;
		$return['email_room'] = ($this->_user->getOpenRoomWantMail() === 'yes') ? true : false;
		$return['new_upload'] = ($this->_user->isNewUploadOn()) ? true : false;
		$return['auto_save'] = ($this->_user->isAutoSaveOn()) ? true : false;
		
		return $return;
	}
	
	private function getUserInformation() {
		$retrun = array();
		
		// get data from database
		$return['title'] = $this->_user->getTitle();
		$return['birthday'] = $this->_user->getBirthday();
		//$return['picture']
		$return['mail'] = $this->_user->getEmail();
		$return['telephone'] = $this->_user->getTelephone();
		$return['cellularphone'] = $this->_user->getCellularphone();
		$return['street'] = $this->_user->getStreet();
		$return['zipcode'] = $this->_user->getZipcode();
		$return['city'] = $this->_user->getCity();
		$return['room'] = $this->_user->getRoom();
		$return['organisation'] = $this->_user->getOrganisation();
		$return['position'] = $this->_user->getPosition();
		$return['icq'] = $this->_user->getICQ();
		$return['msn'] = $this->_user->getMSN();
		$return['skype'] = $this->_user->getSkype();
		$return['yahoo'] = $this->_user->getYahoo();
		$return['jabber'] = $this->_user->getJabber();
		$return['homepage'] = $this->_user->getHomepage();
		$return['description'] = $this->_user->getDescription();
		
		return $return;
		
		/*

            if ($this->_item->isModerator()) {
               $this->_values['want_mail_get_account'] = $this->_item->getAccountWantMail();
               $this->_values['is_moderator'] = true;
            } else {
               $this->_values['is_moderator'] = false;
            }
            $picture = $this->_item->getPicture();
            $this->_values['upload'] = $picture;
            if (!empty($picture)) {
               $this->_values['with_picture'] = true;
            } else {
               $this->_values['with_picture'] = false;
            }

            if (!$this->_item->isEmailVisible()) {
               $this->_values['email_visibility'] = 'checked';
            }
		 */
	}
	
	private function getNewsletterInformation() {
		$return = array();
		
		// get data from database
		$room = $this->_environment->getCurrentUserItem()->getOwnRoom();
		$newsletter = $room->getPrivateRoomNewsletterActivity();
		
		switch($newsletter) {
			case 'weekly':
				$return['newsletter'] = '2';
				break;
			case 'daily':
				$return['newsletter'] = '3';
				break;
			default:
				$return['newsletter'] = '1';
				break;
		}
		
		return $return;
	}
	
	private function getRoomListInformation() {
		
	}
	
	private function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}