<?php
require_once('classes/controller/ajax/popup/cs_popup_controller.php');

class cs_popup_clipboard_controller implements cs_popup_controller {
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
		
		
		/*
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
		*/
	}

	public function getFieldInformation($sub) {
		$return = array(
			
		);

		return $return;
	}

	private function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		$current_user = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		$portal_user = $this->_environment->getPortalUserItem();

		// form information
		$form_information = array();
		$form_information['account'] = $this->getAccountInformation();

		//$this->_popup_controller->assign('popup', 'form', $form_information);
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
		$return['email_to_commsy_on'] = false;

	    global $c_email_upload;
	    if ($c_email_upload ) {
		   $return['email_to_commsy_on'] = true;
	       $own_room = $this->_user->getOwnRoom();
	       $return['email_to_commsy'] = $own_room->getEmailToCommSy();
	       $return['email_to_commsy_secret'] = $own_room->getEmailToCommSySecret();
	       global $c_email_upload_email_account;
	       $return['email_to_commsy_mailadress'] = $c_email_upload_email_account;
	    }

		return $return;
	}
}