<?php

require_once('classes/controller/ajax/popup/cs_popup_controller.php');
require_once ('classes/cs_mail.php');

class cs_popup_mailtouser_controller implements cs_popup_controller {
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
		array('name'		=> 'mailcontent',
			  'type'		=> 'text',
			  'mandatory'	=> false),
		array('name'        => 'receivers',
		      'type'        => 'checkbox',
		      'mandatory'   => false)
		);
	}

	public function save($form_data, $additional = array()) {
		$mail = new cs_mail();

        global $symfonyContainer;
        $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
        $mail->set_from_email($emailFrom);

        $mail->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
		
		$roomId = null;
		if (isset($additional['roomId']) && !empty($additional['roomId'])) {
			$roomId = $additional['roomId'];
		}

		if (!empty($form_data['receiver']['0'])) {
			$receiverId = $form_data['receiver']['0'];
			$userManager = $this->_environment->getUserManager();
			$userItem = $userManager->getItem($receiverId);

			$mail->set_to($userItem->getEmail());
		} else {
			$this->_popup_controller->setErrorReturn(112, 'no receiver checked');
		}

		// if (!empty($form_data['reciever'])) {
		// 	$recipients = implode(', ', $form_data['reciever']);
		// 	$mail->set_to($recipients);
		// } else {
		//     $list = $this->getRecieverList($roomId);
		// 	if(count($list) == 1) {
		// 	    $mail->set_to($list[0] ['value']);
		// 	} else {
		// 	    //no reciever checked
		// 	    $this->_popup_controller->setErrorReturn(112, 'no reciever checked');
		// 	}
		// }

		$context_item = $this->_environment->getCurrentContextItem();

		$mail->set_message($form_data['mailcontent']);
		if(!empty($form_data['subject'])){
			$mail->set_subject($form_data['subject']);
		} else {
			$this->_popup_controller->setErrorReturn(113, 'no subject');
		}
		

		$success = $mail->send();
		if ($success) {
			$this->_popup_controller->setSuccessfullDataReturn('mail send successfully');
		} else {
			//TODO: Error handling
			pr($mail);
		}
	}

	// private function getRecieverList($id = null) {
	// 	$translator = $this->_environment->getTranslationObject();

	// 	if ($id) {
	// 		$projectManager = $this->_environment->getProjectManager();
	// 		$projectItem = $projectManager->getItem($id);
	// 		$mod_list = $projectItem->getContactModeratorList();
	// 	} else {
	// 		$context_item = $this->_environment->getCurrentContextItem();
	// 		$mod_list = $context_item->getModeratorList();
	// 	}

	// 	$receiver_array = array();
	// 	if (!$mod_list->isEmpty()) {
	// 		$mod_item = $mod_list->getFirst();
	// 		while ($mod_item) {
	// 			$temp_array = array();
	// 			$temp_array['value'] = $mod_item->getEmail();
	// 			if ($mod_item->isEmailVisible()) {
	// 				$temp_array['text'] = $mod_item->getFullName().' ('.$mod_item->getEmail().')';
	// 			} else {
	// 				$temp_array['text'] = $mod_item->getFullName().' ('.$translator->getMessage('USER_EMAIL_HIDDEN2').')';
	// 			}
	// 			$receiver_array[] = $temp_array;
	// 			$mod_item = $mod_list->getNext();
	// 		}
	// 	}

	// 	return $receiver_array;
	// }

	public function initPopup($item) {
		$current_user = $this->_environment->getCurrentUserItem();
		$context_item = $this->_environment->getCurrentContextItem();

		// user information
		$user_information = array();
		$user_information['fullname'] = $current_user->getFullName();
		$user_information['mail'] = $current_user->getEmail();
		$this->_popup_controller->assign('popup', 'user', $user_information);

		$receiver_information = array();
		if(isset($item)) {
			$receiver_information['fullname'] = $item->getFullName();
			$receiver_information['id'] = $item->getItemID();
		}

		$this->_popup_controller->assign('popup', 'receiver', $receiver_information);

		// $mod_information = array();
		// if(isset($item)) {
		// 	$mod_information['list'] = $this->getRecieverList($item->getItemID());
		// } else {
		// 	$mod_information['list'] = $this->getRecieverList();
		// }
		
		// $this->_popup_controller->assign('popup', 'mod', $mod_information);

		$translator = $this->_environment->getTranslationObject();

      $context_title = str_ireplace('&amp;', '&', $context_item->getTitle());
		if ( $context_item->isCommunityRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY', $context_title);
		} elseif ( $context_item->isProjectRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT', $context_title);
		} elseif ( $context_item->isGroupRoom() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_GROUPROOM', $context_title);
		} elseif ( $context_item->isPortal() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PORTAL', $context_title);
		} elseif ( $context_item->isServer() ) {
			$body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_SERVER', $context_title);
		}

		$this->_popup_controller->assign('popup', 'mailcontent', $body_message);
	}

}