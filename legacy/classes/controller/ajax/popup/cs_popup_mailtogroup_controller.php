<?php

require_once('classes/controller/ajax/popup/cs_popup_controller.php');
require_once ('classes/cs_mail.php');

class cs_popup_mailtogroup_controller implements cs_popup_controller {
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
			  'mandatory'	=> true),
		array('name'        => 'groups',
		      'type'        => 'checkbox',
		      'mandatory'   => true),
		array('name'        => 'copytosender',
			  'type'        => 'checkbox',
			  'mandatory'   => true)
		);
	}

	public function save($form_data, $additional = array()) {
		if ($this->_popup_controller->checkFormData()) {
			$groupManager = $this->_environment->getGroupManager();
			$currentContext = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();
			
			$recipients = array();
			$recipientsBCC = array();
			$mail = array();
			$nameArray = array();
			
			foreach ($form_data["groups"] as $groupId) {
				$groupItem = $groupManager->getItem($groupId);
				$userList = $groupItem->getMemberItemList();
				$nameArray[] = $groupItem->getTitle();
				
				$userItem = $userList->getFirst();
				while ($userItem) {
					if ($userItem->isUser()) {
						if ($userItem->isEmailVisible()) {
							$recipients[] = $userItem->getFullName() . " <" . $userItem->getEmail() . ">";
						} else {
							$recipientsBCC[] = $userItem->getFullName() . " <" . $userItem->getEmail() . ">";
						}
					}
					
					$userItem = $userList->getNext();
				}
			}
			
			$recipients = array_unique($recipients);
			$recipientsBCC = array_unique($recipientsBCC);
			
			$serverItem = $this->_environment->getServerItem();
			
			$currentUser = $this->_environment->getCurrentUser();
			$mail["from_name"] = $currentUser->getFullName();
			$mail["from_email"] = $currentUser->getEmail();
			$mail["reply_to_name"] = $currentUser->getFullName();
			$mail["reply_to_email"] = $currentUser->getEmail();
			$mail["to"] = implode(",", $recipients);
			$mail["subject"] = $form_data["subject"];
			$mail["message"] = $form_data["mailcontent"];
			
			$email = new cs_mail();

            global $symfonyContainer;
            $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
            $mail->set_from_email($emailFrom);

            $email->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
			$email->set_reply_to_name($mail["reply_to_name"]);
			$email->set_reply_to_email($mail["reply_to_email"]);
			$email->set_to($mail["to"]);
			$email->set_subject($mail["subject"]);
			
			if ($form_data["copytosender"] === "true") {
				$email->set_cc_to($currentUser->getEmail());
			}
			
			if (!empty($recipientsBCC)) {
				$email->set_bcc_to(implode(",", $recipientsBCC));
			}
			
			$addMessage = "";
			$context_title = str_ireplace('&amp;', '&', $currentContext->getTitle());
			if (sizeof($form_data["groups"]) == 1) {
				$addMessage = $translator->getMessage("RUBRIC_EMAIL_ADDED_BODY_PROJECT_GROUP_S", $context_title, $nameArray[0]);
			} else {
				$addMessage = $translator->getMessage("RUBRIC_EMAIL_ADDED_BODY_PROJECT_GROUP_PL", $context_title, implode("," . LF, $nameArray));
			}
			
			if (!empty($addMessage)) {
				$addMessage = LF.LF."---".LF.$addMessage;
			}
			$email->set_message($mail["message"] . $addMessage);
			
			if ($email->send()) {
				$this->_popup_controller->setSuccessfullDataReturn(array());
			} else {
				$this->_popup_controller->setErrorReturn("113", "error processing mails", $email->getErrorArray());
			}
		}
	}

	private function getRecieverList($id = null) {
		$translator = $this->_environment->getTranslationObject();
		
		if ($id) {
			$projectManager = $this->_environment->getProjectManager();
			$projectItem = $projectManager->getItem($id);
			$mod_list = $projectItem->getContactModeratorList();
		} else {
			$context_item = $this->_environment->getCurrentContextItem();
			$mod_list = $context_item->getModeratorList();
		}
		
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

	public function initPopup($item) {
		$translator = $this->_environment->getTranslationObject();
		$labelManager = $this->_environment->getLabelManager();
		
		// headline
		$headline = $translator->getMessage("GROUPS_EMAIL_TO_GROUP_TITLE");
		if (isset($item)) {
			$headline .= ' "' . $item->getTitle() . '"';
		}
		$this->_popup_controller->assign("popup", "headline", $headline);
		
		// groups
		$labelManager->resetLimits();
		$labelManager->setContextLimit($this->_environment->getCurrentContextID());
		$labelManager->setTypeLimit("group");
		$labelManager->select();
		
		$labelList = $labelManager->get();
		$labelArray = array();
		
		if ($labelList->getCount() > 0) {
			$labelItem = $labelList->getFirst();
			
			while ($labelItem) {
				$labelArray[] = array(
					"text"		=> chunkText($labelItem->getName(), 50),
					"value"		=> $labelItem->getItemID(),
					"checked"	=> $item->getItemID() === $labelItem->getItemID()
				);
				
				$labelItem = $labelList->getNext();
			}
		}
		$this->_popup_controller->assign("popup", "groups", $labelArray);
	}
}