<?php

require_once('classes/controller/cs_ajax_controller.php');
require_once ('classes/cs_mail.php');

class cs_ajax_send_controller extends cs_ajax_controller {

	/**
	 * constructor
	 */
	public function __construct(cs_environment $environment) {
		// call parent
		parent::__construct($environment);
	}
	
	public function actionInit() {
		$response = array();
		
		$iid = $this->_data['itemId'];
		
		$current_user = $this->_environment->getCurrentUserItem();
		$current_context = $this->_environment->getCurrentContextItem();
		$translator = $this->_environment->getTranslationObject();

		// context information
		$contextInformation = array();
		$contextInformation["name"] = $current_context->getTitle();
		$response['context'] = $contextInformation;

		// group information
		$groupArray = $this->getAllLabelsByType("group");

		// institutions information
		$institutionArray = $this->getAllLabelsByType("institution");

		// get item
		$manager = $this->_environment->getItemManager();
		$item = $manager->getItem($iid);
		$module = $item->getItemType();
		$link_module = $module;
		if ($module== 'label' or $module== 'labels') {
			$label_manager = $this->_environment->getLabelManager();
			$label = $label_manager->getItem($iid);
			$link_module= $label->getLabelType();
		}
		$item_manager = $this->_environment->getManager($module);
		$item = $item_manager->getItem($iid);
		$item_name = $item->getTitle();

		// Wenn man mit HTTPS auf Commsy surft und eine Email generiert
		// sollte diese Mail auch https links erstellen.
		if ( !empty($_SERVER["HTTPS"])
				and $_SERVER["HTTPS"]
		) {
			$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
			.'?cid='.$this->_environment->getCurrentContextID()
			.'&mod='.$link_module
			.'&fct=detail'
			.'&iid='.$item->getItemID();
		} else {
			$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
			.'?cid='.$this->_environment->getCurrentContextID()
			.'&mod='.$link_module
			.'&fct=detail'
			.'&iid='.$item->getItemID();
		}
		$link = $url;

		$content = '';
		//generate module name for the interface- a pretty version of module...
		if ($module== CS_DATE_TYPE) {
			// set up style of days and times
			$parse_time_start = convertTimeFromInput($item->getStartingTime());
			$conforms = $parse_time_start['conforms'];
			if ($conforms == TRUE) {
				$start_time_print = getTimeLanguage($parse_time_start['datetime']);
			} else {
				$start_time_print = $item->getStartingTime();
			}

			$parse_time_end = convertTimeFromInput($item->getEndingTime());
			$conforms = $parse_time_end['conforms'];
			if ($conforms == TRUE) {
				$end_time_print = getTimeLanguage($parse_time_end['datetime']);
			} else {
				$end_time_print = $item->getEndingTime();
			}

			$parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
			$conforms = $parse_day_start['conforms'];
			if ($conforms == TRUE) {
				$start_day_print = getDateInLang($parse_day_start['datetime']);
			} else {
				$start_day_print = $item->getStartingDay();
			}

			$parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
			$conforms = $parse_day_end['conforms'];
			if ($conforms == TRUE) {
				$end_day_print =getDateLanguage($parse_day_end['datetime']);
			} else {
				$end_day_print =$item->getEndingDay();
			}
			//formating dates and times for displaying
			$date_print ="";
			$time_print ="";

			if ($end_day_print != "") { //with ending day
				$date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
				if ($parse_day_start['conforms']
						and $parse_day_end['conforms']) { //start and end are dates, not strings
					$date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
				}
				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
					if ($parse_time_start['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
					if ($parse_time_end['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					if ($parse_time_end['conforms'] == true) {
						$end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					if ($parse_time_start['conforms'] == true) {
						$start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					$date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.'<br />'.
							$translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
					if ($parse_day_start['conforms']
							and $parse_day_end['conforms']) {
						$date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
					}
				}

			} else { //without ending day
				$date_print = $start_day_print;
				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
					if ($parse_time_start['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
					if ($parse_time_end['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					if ($parse_time_end['conforms'] == true) {
						$end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					if ($parse_time_start['conforms'] == true) {
						$start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
				}
			}

			if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
				$date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
				}
			}
			// Date and time
			$dates_content = '';
			$dates_content = $translator->getMessage('DATES_DATETIME').': '.$item_name.LF;
			if ($time_print != '') {
				$dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.','.$time_print.LF;
			} else {
				$dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.LF;
			}
			// Place
			$place = $item->getPlace();
			if (!empty($place)) {
				$dates_content .= $translator->getMessage('DATES_PLACE').': ';
				$dates_content .= $place.LF;
			}
			$content = $dates_content;
		} elseif ($module== 'discussion' or $module== 'discussions') {
			$discussion_content = $translator->getMessage('COMMON_DISCUSSION').': '.$item->getTitle().LF;
			$article_count = $item->getAllArticlesCount();
			$discussion_content .= $translator->getMessage('DISCUSSION_DISCARTICLE_COUNT').': '.$article_count.LF;
			$time = $item->getLatestArticleModificationDate();
			$discussion_content .= $translator->getMessage('DISCUSSION_LAST_ENTRY').': '.getDateTimeInLang($time).LF;
			$content = $discussion_content;
		} elseif ($module== 'material' or $module== 'materials') {
			$material_content = $translator->getMessage('COMMON_MATERIAL').': '.$item->getTitle().LF;
			$content = $material_content;
		} elseif ($module== 'announcement' or $module== CS_ANNOUNCEMENT_TYPE) {
			$announcement_content = $translator->getMessage('COMMON_ANNOUNCEMENT').': '.$item->getTitle().LF;
			$content = $announcement_content;
		}  elseif ($module== 'label' or $module== 'labels') {
			$label_manager = $this->_environment->getLabelManager();
			$label = $label_manager->getItem($iid);
			$module= $label->getLabelType();
			if ($module== 'group' or $module== 'groups') {
				$group_content = $translator->getMessage('COMMON_GROUP').': '.$item->getTitle().LF;
				$content = $group_content;
			} elseif ($module== 'institution' or $module== 'institutions') {
				$institution_content = $translator->getMessage('INSTITUTION').': '.$item->getTitle().LF;
				$content = $institution_content;
			}
		}
		if ( $this->_environment->inProjectRoom() ){
			$emailtext = $translator->getMessage('RUBRIC_EMAIL_DEFAULT_PROJECT',$current_context->getTitle()).LF;
		} elseif ( $this->_environment->inGroupRoom() ){
			$emailtext = $translator->getMessage('RUBRIC_EMAIL_DEFAULT_GROUPROOM',$current_context->getTitle()).LF;
		} else {
			$emailtext = $translator->getMessage('RUBRIC_EMAIL_DEFAULT_COMMUNITY', $current_context->getTitle()).LF;
		}
		if ( empty($content) ){
			$emailtext .= LF.LF;
		} else {
			$emailtext .= $content;
		}
		$emailtext .= $translator->getMessage('RUBRIC_EMAIL_DEFAULT_PROJECT_END',$link);
		
		$response['body'] = strip_tags($emailtext);

		// receiver
		$showAttendees = false;

		if ($module === CS_DATE_TYPE) {
			$showAttendees = true;
			$attendeeType = CS_DATE_TYPE;
		}
		if ($module === CS_TODO_TYPE) {
			$showAttendees = true;
			$attendeeType = CS_TODO_TYPE;
		}
		
		$response['showAttendees'] = $showAttendees;
		$response['attendeeType'] = $attendeeType;


		$showGroupRecipients = false;
		$showInstitutionRecipients = false;
		if ( $this->_environment->inProjectRoom() and !empty($groupArray) ) {
			if ( $current_context->withRubric(CS_GROUP_TYPE) ) {
				$showGroupRecipients = true;
			}
		}

		//Projectroom and no groups enabled -> send mails to group all
		$withGroups = true;
		if ( $current_context->isProjectRoom() && !$current_context->withRubric(CS_GROUP_TYPE)) {
			$showGroupRecipients = true;
			$withGroups = false;

			// get number of users
			$cid = $this->_environment->getCurrentContextId();
			$user_manager = $this->_environment->getUserManager();
			$user_manager->setUserLimit();
			$user_manager->setContextLimit($cid);
			$count = $user_manager->getCountAll();
         $response['numMebers'] = $count;

			$groupArray = array_slice($groupArray, 0, 1);
		}
		
		$response['showGroupRecipients'] = $showGroupRecipients;
		$response['withGroups'] = $withGroups;
		$response['groups'] = $groupArray;

		$allMembers = false;
		if ( ($current_context->isCommunityRoom()) || $current_context->isGroupRoom()) {
			$allMembers = true;

			// get number of users
			$cid = $this->_environment->getCurrentContextId();
			$user_manager = $this->_environment->getUserManager();
			$user_manager->setUserLimit();
			$user_manager->setContextLimit($cid);
			$count = $user_manager->getCountAll();
			
			$response['numMebers'] = $count;
		}
		
		$response['showInstitutionRecipients'] = $showInstitutionRecipients;
		$response['institutions'] = $institutionArray;
		$response['allMembers'] = $allMembers;
		
		$this->setSuccessfullDataReturn($response);
		echo $this->_return;
	}
	
	/*
	 * every derived class needs to implement an processTemplate function
	*/
	public function process() {
		// call parent
		parent::process();
	}
	
	public function actionSend() {
		$manager = $this->_environment->getItemManager();
		$translator = $this->_environment->getTranslationObject();
		
		$iid = $this->_data['itemId'];

		$rubric_item = $manager->getItem($iid);
		
		if($rubric_item && $this->_data['subject']) {
		    $module = $rubric_item->getItemType();
		    
			$user_manager = $this->_environment->getUserManager();
			$user_manager->resetLimits();
			$user_manager->setUserLimit();
			$recipients = array();
			$recipients_display = array();
			$recipients_bcc = array();
			$recipients_display_bcc = array();
			$label_manager = $this->_environment->getLabelManager();
			$topic_list = new cs_list();

			if (isset($this->_data["allMembers"])) {	//send to all members of a community room, if no institutions and topics are availlable
   			if ($this->_data["allMembers"] == '1') {
   				$cid = $this->_environment->getCurrentContextId();
   				$user_manager->setContextLimit($cid);
   				$user_manager->select();
   				$user_list = $user_manager->get();
   				$user_item = $user_list->getFirst();
   				while($user_item) {
   					if ( $user_item->isEmailVisible()) {
   						$recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
   						$recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
   					} else {
   						$recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
   						$recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
   					}
   					$user_item = $user_list->getNext();
   				}
				}
			}

			if ($module == CS_TOPIC_TYPE) {
				$topic_list = $label_manager->getItemList($_POST[CS_TOPIC_TYPE]);
			}
			$topic_item = $topic_list->getFirst();
			while ($topic_item){
				// get selected rubrics for inclusion in recipient list
				$user_manager->setTopicLimit($topic_item->getItemID());
				$user_manager->select();
				$user_list = $user_manager->get();
				$user_item = $user_list->getFirst();
				while($user_item) {
					if ($user_item->isEmailVisible()) {
						$recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
						$recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
					} else {
						$recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
						$recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
					}
					$user_item = $user_list->getNext();
				}
				$topic_item = $topic_list->getNext();
			}

			if (isset($this->_data["copyToAttendees"]) && $this->_data["copyToAttendees"] == "true") {
				if($module == CS_DATE_TYPE) {
					$date_manager = $this->_environment->getDateManager();
					$date_item = $date_manager->getItem($rubric_item->getItemID());
					$attendees_list = $date_item->getParticipantsItemList();
					$attendee_item = $attendees_list->getFirst();
					while ($attendee_item){
						if ($attendee_item->isEmailVisible()) {
							$recipients[] = $attendee_item->getFullName()." <".$attendee_item->getEmail().">";
							$recipients_display[] = $attendee_item->getFullName()." &lt;".$attendee_item->getEmail()."&gt;";
						} else {
							$recipients_bcc[] = $attendee_item->getFullName()." <".$attendee_item->getEmail().">";
							$recipients_display_bcc[] = $attendee_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
						}
						$attendee_item = $attendees_list->getNext();
					}
				} elseif($module == CS_TOPIC_TYPE) {
					$todo_manager = $this->_environment->getToDoManager();
					$todo_item = $todo_manager->getItem($rubric_item->getItemID());
					$attendees_list = $todo_item->getProcessorItemList();
					$attendee_item = $attendees_list->getFirst();
					while ($attendee_item){
						if ($attendee_item->isEmailVisible()) {
							$recipients[] = $attendee_item->getFullName()." <".$attendee_item->getEmail().">";
							$recipients_display[] = $attendee_item->getFullName()." &lt;".$attendee_item->getEmail()."&gt;";
						} else {
							$recipients_bcc[] = $attendee_item->getFullName()." <".$attendee_item->getEmail().">";
							$recipients_display_bcc[] = $attendee_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
						}
						$attendee_item = $attendees_list->getNext();
					}
				}
			}

			$user_manager->resetLimits();
			$user_manager->setUserLimit();
			$label_manager = $this->_environment->getLabelManager();
			$group_list = new cs_list();

			// build group id array
			$groupIdArray = array();
			foreach ($this->_data as $key => $value) {
				if (mb_stristr($key, "group_") && $value == true) {
					$groupIdArray[] = mb_substr($key, 6);
				}
			}

			if (!empty($groupIdArray)) {
				$group_list = $label_manager->getItemList($groupIdArray);
			}
			$group_item = $group_list->getFirst();
			while ($group_item){
				// get selected rubrics for inclusion in recipient list
				$user_manager->setGroupLimit($group_item->getItemID());
				$user_manager->select();
				$user_list = $user_manager->get();
				$user_item = $user_list->getFirst();
				while($user_item) {
					if ($user_item->isEmailVisible()) {
						$recipients[] = $user_item->getFullName()." <".$user_item->getEmail().">";
						$recipients_display[] = $user_item->getFullName()." &lt;".$user_item->getEmail()."&gt;";
					} else {
						$recipients_bcc[] = $user_item->getFullName()." <".$user_item->getEmail().">";
						$recipients_display_bcc[] = $user_item->getFullName()." &lt;".$translator->getMessage('USER_EMAIL_HIDDEN')."&gt;";
					}
					$user_item = $user_list->getNext();
				}
				$group_item = $group_list->getNext();
			}

			$user_manager->resetLimits();
			$user_manager->setUserLimit();
			$label_manager = $this->_environment->getLabelManager();
			
			// additional recipients
			$additionalRecipientsArray = array();
			foreach ($this->_data as $key => $value) {
			    if (mb_substr($key, 0, 10) == "additional") {
			    	$shortKey = mb_substr($key, 10);
			    	
			    	list($field, $index) = explode('_', $shortKey);
			    	
			    	$additionalRecipientsArray[$index-1][$field] = $value;
			    }
			}
			
			foreach ($additionalRecipientsArray as $additionalRecipient) {
				$recipients[] = $additionalRecipient['FirstName'] . ' ' . $additionalRecipient['LastName'] . " <" . $additionalRecipient['Mail'] . ">";
				$recipients_display[] = $additionalRecipient['FirstName'] . ' ' . $additionalRecipient['LastName'] . " &lt;" . $additionalRecipient['Mail'] . "&gt;";
			}

			$recipients = array_unique($recipients);
			$recipients_display = array_unique($recipients_display);

			if ( $this->_environment->inGroupRoom() and empty($recipients_display) ) {
				$cid = $this->_environment->getCurrentContextId();
				$user_manager->setContextLimit($cid);
				$count = $user_manager->getCountAll();
				unset($user_manager);
				if ( $count == 1 ) {
					$text = $translator->getMessage('COMMON_MAIL_ALL_ONE_IN_ROOM',$count);
				} else {
					$text = $translator->getMessage('COMMON_MAIL_ALL_IN_ROOM',$count);
				}
				$recipients_display[] = $text;
			}
			$recipients_bcc = array_unique($recipients_bcc);
			$recipients_display_bcc = array_unique($recipients_display_bcc);

			$current_user = $this->_environment->getCurrentUser();
			$mail['from_name'] = $current_user->getFullName();
			$mail['from_email'] = $current_user->getEmail();
			$mail["reply_to_name"] = $current_user->getFullName();
			$mail["reply_to_email"] = $current_user->getEmail();
			$mail['to'] = implode(", ", $recipients);
			$mail['subject'] = $this->_data["subject"];
			$mail['message'] = $this->_data["body"];

			$email = new cs_mail();

            global $symfonyContainer;
            $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
            $mail->set_from_email($emailFrom);

            $email->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
            $email->set_reply_to_name($mail["reply_to_name"]);
			$email->set_reply_to_email($mail["reply_to_email"]);
			$email->set_to($mail['to']);
			$email->set_subject($mail['subject']);
			$email->set_message($mail['message']);

			if (isset($this->_data["copyToSender"]) && $this->_data["copyToSender"] == 'true') {
				$email->set_cc_to($current_user->getEmail());
			}
			if ( !empty($recipients_bcc) ) {
				$email->set_bcc_to(implode(",",$recipients_bcc));
			}

			if ($email->send())
			{
				// prepare data for confirm popup
				$confirmPopupData = array(
					"from"			=> $mail['from_email'],
					"to"			=> $recipients,
					"reply"			=> $mail['from_email'],
					"copyToSender"	=> (isset($this->_data["copyToSender"]) && $this->_data["copyToSender"] == 'true'),
					"recipientsBcc"	=> $recipients_bcc,
					"subject"		=> $this->_data["subject"],
					"body"			=> nl2br($this->_data["body"])
				);
				
				$this->setSuccessfullDataReturn(array($confirmPopupData));
				echo $this->_return;
			} // ~email->send()
			else { // Mail could not be send
			    $this->setErrorReturn("110", "mail could not be delivered", array());
			    echo $this->_return;
			    exit;
			}
		} else {
		    $this->setErrorReturn("111", "missing mandatory field", array());
		    echo $this->_return;
		    exit;
		}
	}
	
	/** Retrieves all labels of a type in the current context
	 *   @param $type: typ of label, e.g. 'topic', 'group'
	 *   @return list of names and id's of desired labels
	 */
	private function getAllLabelsByType($type) {
		$label_manager = $this->_environment->getLabelManager();
		$label_manager->resetLimits();
		$label_manager->setContextLimit($this->_environment->getCurrentContextID());
		$label_manager->setTypeLimit($type);
		$label_manager->select();
		$label_list = $label_manager->get();
		$label_array = array();
		if ($label_list->getCount() > 0) {
			$label_item =  $label_list->getFirst();
			while ($label_item) {
				$temp_array['text'] = chunkText($label_item->getName(),'50');
				$temp_array['value'] = $label_item->getItemID();
				$temp_array["checked"] = ($label_item->isSystemLabel()) ? true : false;
				$label_array[] = $temp_array;
				$label_item =  $label_list->getNext();
			}
		}
		return $label_array;
	}
}