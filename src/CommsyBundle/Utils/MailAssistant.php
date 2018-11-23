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
    private $from;

    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator, Twig_Environment $twig, $from)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->twig = $twig;
        $this->from = $from;
    }

    public function prepareMessage($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        return $this->twig->render('CommsyBundle:mail:send.html.twig', [
            'contextItem' => $currentContextItem,
            'currentUser' => $currentUser,
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

    public function getSwiftMessage($formData, $item, $forceBCCMail = false): \Swift_Message
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $recipients = $this->getRecipients($formData, $item);
        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        if ($currentUser->isEmailVisible()) {
            $replyTo[$currentUser->getEmail()] = $currentUser->getFullName();
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($formData['subject'])
            ->setBody($formData['message'], 'text/html')
            ->setFrom([$this->from => $portalItem->getTitle()])
            ->setReplyTo($replyTo);

        if ($forceBCCMail) {
            $allRecipients = array_merge($to, $toBCC);
            $message->setBcc($allRecipients);
        } else {
            if (!empty($to)) {
                $message->setTo($to);
            }

            if (!empty($toBCC)) {
                $message->setBcc($toBCC);
            }
        }

        // form option: copy_to_sender
        if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
            $message->setCc($message->getReplyTo());
        }

        // form option: additional_recipients
        if (isset($formData['additional_recipients'])) {
            $additionalRecipients = array_filter($formData['additional_recipients']);

            if (!empty($additionalRecipients)) {
                array_walk($additionalRecipients, function($additionalRecipient) use ($message) {
                    $message->addTo($additionalRecipient);
                });
            }
        }

        return $message;
    }

    private function getRecipients($formData, $item)
    {
        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        // form option: send_to_all
        if (isset($formData['send_to_all']) && $formData['send_to_all']) {
            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();
            $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
            $userManager->select();
            $userList = $userManager->get();

            $this->addRecipients($recipients, $userList);
        }

        // form option: send_to_attendees
        if (isset($formData['send_to_attendees']) && $formData['send_to_attendees']) {
            if ($item instanceof \cs_dates_item) {
                $attendees = $item->getParticipantsItemList();
                $this->addRecipients($recipients, $attendees);
            }
        }

        // form option: send_to_assigned
        if (isset($formData['send_to_assigned']) && $formData['send_to_assigned']) {
            if ($item instanceof \cs_todo_item) {
                $processors = $item->getProcessorItemList();
                $this->addRecipients($recipients, $processors);
            }
        }

        // form option: send_to_group_all - if group rubric is not active
        if (isset($formData['send_to_group_all']) && $formData['send_to_group_all']) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
            $userList = $currentContextItem->getUserList();

            $this->addRecipients($recipients, $userList);
        }

        // form option: send_to_groups
        if (isset($formData['send_to_groups']) && !empty($formData['send_to_groups'])) {
            $labelManager = $this->legacyEnvironment->getLabelManager();
            $groups = $labelManager->getItemList($formData['send_to_groups']);

            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();

            $group = $groups->getFirst();
            while ($group) {
                $userManager->setGroupLimit($group->getItemID());
                $userManager->select();

                $userList = $userManager->get();
                $this->addRecipients($recipients, $userList);

                $group = $groups->getNext();
            }
        }

        // form option: send_to_institutions
        if (isset($formData['send_to_institutions']) && !empty($formData['send_to_institutions'])) {
            $labelManager = $this->legacyEnvironment->getLabelManager();
            $institutions = $labelManager->getItemList($formData['send_to_institutions']);

            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();

            $institution = $institutions->getFirst();
            while ($institution) {
                $userManager->setInstitutionLimit($institution->getItemID());
                $userManager->select();
                $userList = $userManager->get();

                $this->addRecipients($recipients, $userList);

                $institution = $institutions->getNext();
            }
        }

        return $recipients;
    }

    private function addRecipients(&$recipients, $userList)
    {
        $user = $userList->getFirst();
        while($user) {
            if ($user->isEmailVisible()) {
                if (!array_key_exists($user->getEmail(), $recipients['to'])) {
                    $recipients['to'][$user->getEmail()] = $user->getFullName();
                }
            } else {
                if (!array_key_exists($user->getEmail(), $recipients['bcc'])) {
                    $recipients['bcc'][$user->getEmail()] = $user->getFullName();
                }
            }

            $user = $userList->getNext();
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
        /*
        $type = $item->getType();
        if ($type === 'date') {

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


        }
        */
        return $data;
    }
}