<?php

namespace CommsyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

use Symfony\Component\Translation\TranslatorInterface;

use \Twig_Environment;

class MailAssistant
{
    private $legacyEnvironment;
    private $translator;
    private $twig;

    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator, Twig_Environment $twig)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function prepareMessage($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        return $this->twig->render('CommsyBundle:Mail:send.html.twig', [
            'contextItem' => $currentContextItem,
            'item' => $item,
            'content' => $this->generateMessageData($item),
        ]);
    }

    public function showGroupRecipients($item) {
        $groupArray = $this->getChoicesByLabelType("group");

        if ($this->legacyEnvironment->inProjectRoom() && !empty($groupArray)) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContextItem->withRubric('group')) {
                return true;
            }
        }

        return false;
    }

    public function showInstitutionRecipients($item) {
        $institutionArray = $this->getChoicesByLabelType("institution");

        if ($this->legacyEnvironment->inCommunityRoom() && !empty($institutionArray)) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContextItem->withRubric('institution')) {
                return true;
            }
        }

        return false;
    }

    public function showGroupAllRecipients($item) {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($currentContextItem->isProjectRoom() && !$currentContextItem->withRubric('group')) {
            return true;
        }

        return false;
    }

    public function getGroupChoices($item)
    {
        return $this->getChoicesByLabelType("group");
    }

    public function getInstitutionChoices($item)
    {
        return $this->getChoicesByLabelType("institution");
    }

    public function showAllMembersRecipients($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($currentContextItem->isCommunityRoom() && !$currentContextItem->withRubric('institution') ||
            $currentContextItem->isGroupRoom()) {

            return true;
        }

        return false;
    }

    public function getSwiftMessage($formData)
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $serverItem = $this->legacyEnvironment->getServerItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $recipients = $this->getRecipients($formData);

        $message = \Swift_Message::newInstance()
            ->setSubject($formData['subject'])
            ->setBody($formData['message'], 'text/plain')
            ->setFrom([$serverItem->getDefaultSenderAddress() => $portalItem->getTitle()])
            ->setReplyTo([$currentUser->getEmail() => $currentUser->getFullName()])
            ->setTo($recipients['to'])
            ->setBcc($recipients['bcc']);

        if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
            $message->setCc($message->getReplyTo());
        }

        return $message;
    }

    private function getRecipients($formData)
    {
        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        // form option: send_to_all
        if (isset($formData['send_to_all']) && $formData['send_to_all']) {
            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
            $userManager->select();
            $userList = $userManager->get();

            $user = $userList->getFirst();
            while($user) {
                if ($user->isEmailVisible()) {
                    $recipients['to'][] = [$user->getEmail() => $user->getFullName()];
                } else {
                    $recipients['bcc'][] = [$user->getEmail() => $user->getFullName()];
                }

                $user = $userList->getNext();
            }

        }

        return $recipients;











        $mail['to'] = implode(", ", $recipients);
        $email->set_to($mail['to']);



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
        $institution_list = new cs_list();

        // build institution id array
        $institutionIdArray = array();
        foreach ($this->_data as $key => $value) {
            if (mb_stristr($key, "institution_") && $value == true) {
                $institutionIdArray[] = mb_substr($key, 12);
            }
        }

        if (!empty($institutionIdArray)) {
            $institution_list = $label_manager->getItemList($institutionIdArray);
        }
        $institution_item = $institution_list->getFirst();
        while ($institution_item){
            // get selected rubrics for inclusion in recipient list
            $user_manager->setInstitutionLimit($institution_item->getItemID());
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
            $institution_item = $institution_list->getNext();
        }

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




        if ( !empty($recipients_bcc) ) {
            $email->set_bcc_to(implode(",",$recipients_bcc));
        }
    }

    /** Retrieves all form choices by label type in the current context
     *   @param $type: typ of label, e.g. 'topic', 'group' or 'institution'
     *   @return array with label name as key and id as value
     */
    private function getChoicesByLabelType($type)
    {
        $labelManager = $this->legacyEnvironment->getLabelManager();
        $labelManager->resetLimits();
        $labelManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $labelManager->setTypeLimit($type);
        $labelManager->select();
        $labelList = $labelManager->get();

        $choiceArray = array();
        if ($labelList->getCount() > 0) {
            $labelItem =  $labelList->getFirst();

            while ($labelItem) {
                $choiceArray[$labelItem->getName()] = $labelItem->getItemID();

                $labelItem =  $labelList->getNext();
            }
        }

        return $choiceArray;
    }

    private function generateMessageData($item)
    {
        $data = [];

        $type = $item->getType();
        if ($type === 'date') {
            /*
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

            */
        }

        return $data;
    }
}