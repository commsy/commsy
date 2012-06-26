<?php

require_once('classes/controller/ajax/popup/cs_popup_controller.php');
require_once ('classes/cs_mail.php');

class cs_popup_mailtomod_controller implements cs_popup_controller {
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

	public function getFieldInformation() {
		return array(
		array('name'		=> 'subject',
			  'type'		=> 'text',
			  'mandatory' => true),
		array('name'		=> 'content',
			  'type'		=> 'text',
			  'mandatory'	=> true)
		);
	}

	public function save($form_data, $additional = array()) {
		$mail = new cs_mail();
		//TODO: feed mail with formdata etc.
		$mail->set_from_email($this->_environment->getCurrentUser()->getEmail());
		$mail->set_from_name($this->_environment->getCurrentUser()->getFullName());
		if (!empty($form_data['receivers'])) {
			$recipients = implode(', ', $form_data['reciever']);
			$mail->set_to($recipients);
		} else {
			//TODO: Error
		}

		$context_item = $this->_environment->getCurrentContextItem();

		$mail->set_message($form_data['body']);
		$mail->set_subject($form_data['subject']);
		
		$success = $mail->send();
		if ($success) {
			$this->_popup_controller->setSuccessfullDataReturn('mail send successfully');
		} else {
			//TODO: Error handling
			pr($mail);			
		}
	}

	private function getRecieverList() {
		$translator = $this->_environment->getTranslationObject();

		$context_item = $this->_environment->getCurrentContextItem();
		$mod_list = $context_item->getModeratorList();
		$receiver_array = array();
		if (!$mod_list->isEmpty()) {
			$mod_item = $mod_list->getFirst();
			while ($mod_item) {
				$temp_array = array();
				$temp_array['value'] = $mod_item->getEmail();
				if ($mod_item->isEmailVisible()) {
					$temp_array['text'] = $mod_item->getFullName().' ('.$mod_item->getEmail().')';
				} else {
					$temp_array['text'] = $mod_item->getFullName().' ('.$translator->getMessage('USER_EMAIL_HIDDEN2').')';
				}
				$receiver_array[] = $temp_array;
				$mod_item = $mod_list->getNext();
			}
		}

		return $receiver_array;
	}

	public function initPopup($data) {
		$current_user = $this->_environment->getCurrentUserItem();
		$context_item = $this->_environment->getCurrentContextItem();

		// user information
		$user_information = array();
		$user_information['fullname'] = $current_user->getFullName();
		$user_information['mail'] = $current_user->getEmail();
		$this->_popup_controller->assign('popup', 'user', $user_information);

		$mod_information = array();
		$mod_information['list'] = $this->getRecieverList();
		//pr($this->getRecieverList());
		$this->_popup_controller->assign('popup', 'mod', $mod_information);

		$translator = $this->_environment->getTranslationObject();

		if ( $context_item->isCommunityRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY', $context_item->getTitle());
		} elseif ( $context_item->isProjectRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT', $context_item->getTitle());
		} elseif ( $context_item->isGroupRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_GROUPROOM', $context_item->getTitle());
		} elseif ( $context_item->isPortal() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PORTAL', $context_item->getTitle());
		} elseif ( $context_item->isServer() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_SERVER', $context_item->getTitle());
		}

		$this->_popup_controller->assign('popup', 'body', $body_message);
	}

}