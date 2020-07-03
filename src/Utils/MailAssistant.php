<?php

namespace App\Utils;

use App\Form\Model\File;
use App\Form\Model\Send;
use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;

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

        return $this->twig->render('mail/send.html.twig', [
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

            if ($currentContextItem->withRubric('group') && !$currentContextItem->withRubric('project')) {
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

        if($currentContextItem->isCommunityRoom() && !$currentContextItem->withRubric('group')){
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

        if ($currentContextItem->isCommunityRoom() && !$currentContextItem->withRubric('project') ||
            $currentContextItem->isGroupRoom()) {

            return true;
        }

        return false;
    }

    public function getSwiftMessageContactForm(
        FormInterface $form,
        $item,
        $forceBCCMail = false
    ): \Swift_Message
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        $recipients['to'][$item->getEmail()] = $item->getFullName();

        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[$currentUserEmail] = $currentUserName;
        }

        $formDataSubject = $formData['subject'];

        $formDataMessage = $formData['message'];

        $from = '';
        if(empty($this->from)){
            $from = 'noreply@commsy.net';
        }else{
            $from = $this->from;
        }
        $message = (new \Swift_Message())
            ->setSubject($formDataSubject)
            ->setBody($formDataMessage, 'text/html')
            ->setFrom([$from => $portalItem->getTitle()])
            ->setReplyTo($replyTo);

        // form option: files
        $formDataFiles = $formData['files'];

        if (!empty($formDataFiles)) {
            $message = $this->addAttachments($formDataFiles, $message);
        }

        // form option: copy_to_sender
        $toCC = [];

        $isCopyToSender = $form->has('copy_to_sender') && $formData['copy_to_sender'];

        if ($isCopyToSender) {
            if ($currentUser->isEmailVisible()) {
                $toCC[$currentUserEmail] = $currentUserName;
            } else {
                $toBCC[$currentUserEmail] = $currentUserName;
            }
        }

        $hasAdditionalRecipient = $form->has('additional_recipient') && !empty($formData['additional_recipient']);

        if ($hasAdditionalRecipient) {
            $toCC[$formData['additional_recipient']] = $formData['additional_recipient'];
        }

        if ($forceBCCMail) {
            $allRecipients = array_merge($to, $toCC, $toBCC);
            $message->setBcc($allRecipients);
        } else {
            if (!empty($to)) {
                $message->setTo($to);
            }

            if (!empty($toCC)) {
                $message->setCC($toCC);
            }

            if (!empty($toBCC)) {
                $message->setBcc($toBCC);
            }
        }

        return $message;
    }

    public function getSwiftMailForAccountIndexSendMail(FormInterface $form, $item, $forceBCCMail = false): \Swift_Message
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        $recipients['to'][$item->getEmail()] = $item->getFullName();

        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[$currentUserEmail] = $currentUserName;
        }

        $formDataSubject = $formData->getSubject();

        $formDataMessage = $formData->getMessage();

        $message = (new \Swift_Message())
            ->setSubject($formDataSubject)
            ->setBody($formDataMessage, 'text/html')
            ->setFrom([$currentUserEmail => $portalItem->getTitle()])
            ->setReplyTo($replyTo);

        // form option: copy_to_sender
        $toCC = [];

        $isCopyToSender = $form->has('copy_to_sender') && $formData['copy_to_sender'];

        if ($isCopyToSender) {
            if ($currentUser->isEmailVisible()) {
                $toCC[$currentUserEmail] = $currentUserName;
            } else {
                $toBCC[$currentUserEmail] = $currentUserName;
            }
        }

        if ($forceBCCMail) {
            $allRecipients = array_merge($to, $toCC, $toBCC);
            $message->setBcc($allRecipients);
        } else {
            if (!empty($to)) {
                $message->setTo($to);
            }

            if (!empty($toCC)) {
                $message->setCC($toCC);
            }

            if (!empty($toBCC)) {
                $message->setBcc($toBCC);
            }
        }

        return $message;
    }

    public function getSwiftMailForAccountIndexSendPasswordMail(FormInterface $form, $item, $forceBCCMail = false): \Swift_Message
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        $recipients['to'][$item->getEmail()] = $item->getFullName();

        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[$currentUserEmail] = $currentUserName;
        }

        $formDataSubject = $formData->getSubject();

        $formDataMessage = $formData->getMessage();

        $message = (new \Swift_Message())
            ->setSubject($formDataSubject)
            ->setBody($formDataMessage, 'text/html')
            ->setFrom([$currentUserEmail => $portalItem->getTitle()])
            ->setReplyTo($replyTo);

        // form option: copy_to_sender
        $toCC = [];

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setUserLimit();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->select();
        $portaluserList = $userManager->get();
        $moderators = [];
        foreach($portaluserList as $portalUser){
            if($portalUser->getStatus() == 3){
                array_push($moderators, $portalUser);
            }
        }

        if($form->getData()->getCopyCCToModertor()){
            foreach($moderators as $moderator){
                $toCC[$moderator->getEmail()] = $moderator->getFullName();
            }
        }
        if($form->getData()->getCopyBCCToModerator()){
            foreach($moderators as $moderator){
                $toBCC[$moderator->getEmail()] = $moderator->getFullName();
            }
        }
        if($form->getData()->getCopyCCToSender()){
            $toCC[$currentUserEmail] = $currentUserName;
        }
        if($form->getData()->getCopyBCCToSender()){
            $toBCC[$currentUserEmail] = $currentUserName;
        }


        if (!empty($to)) {
            $message->setTo($to);
        }

        if (!empty($toCC)) {
            $message->setCC($toCC);
        }

        if (!empty($toBCC)) {
            $message->setBcc($toBCC);
        }

        return $message;
    }

    public function getSwiftMessage(FormInterface $form, \cs_item $item, $forceBCCMail = false): \Swift_Message
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = $this->getRecipients($form, $item);
        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[$currentUserEmail] = $currentUserName;
        }

        $formDataSubject = (get_class($formData) == Send::class ? (is_null($formData->getSubject())
            ? false : $formData->getSubject()) : $formData['subject']);

        $formDataMessage = (get_class($formData) == Send::class ? (is_null($formData->getMessage())
            ? false : $formData->getMessage()) : $formData['message']);

        $message = (new \Swift_Message())
            ->setSubject($formDataSubject)
            ->setBody($formDataMessage, 'text/html')
            ->setFrom([$this->from => $portalItem->getTitle()])
            ->setReplyTo($replyTo);

        // form option: files
        $formDataFiles = (get_class($formData) == Send::class ? (is_null($formData->getFiles())
            ? false : $formData->getFiles()) : $formData['files']);

        if ($formDataFiles) {
            $message = $this->addAttachments($formDataFiles, $message);
        }

        // form option: copy_to_sender
        $toCC = [];

        $isSendToCreator = (get_class($formData) == Send::class ? (is_null($formData->getSendToCreator())
            ? false : $formData->getSendToCreator()) : $form->has('send_to_creator') && $formData['send_to_creator']);

        if ($isSendToCreator) {
            /** @var \cs_user_item $itemCreator */
            $itemCreator = $item->getCreatorItem();
            if ($itemCreator->isEmailVisible()) {
                $to[$itemCreator->getEmail()] = $itemCreator->getFullName();
            } else {
                $toBCC[$itemCreator->getEmail()] = $itemCreator->getFullName();
            }
        }

        $isCopyToSender = (get_class($formData) == Send::class ? (is_null($formData->getCopyToSender())
            ? false : $formData->getCopyToSender()) : $form->has('copy_to_sender') && $formData['copy_to_sender']);

        if ($isCopyToSender) {
            if ($currentUser->isEmailVisible()) {
                $toCC[$currentUserEmail] = $currentUserName;
            } else {
                $toBCC[$currentUserEmail] = $currentUserName;
            }
        }

        // form option: additional_recipients
        $isAdditionalRecipients = (get_class($formData) == Send::class ? (is_null($formData->getAdditionalRecipients())
            ? false : true) : $form->has('additional_recipients'));

        if ($isAdditionalRecipients) {
            $formDataAdditionalRecipients = (get_class($formData) == Send::class
                ? ($formData->getAdditionalRecipients()) : $formData['additional_recipients']);
            $additionalRecipients = array_filter($formDataAdditionalRecipients);

            if (!empty($additionalRecipients)) {
                $to = array_merge($to, $additionalRecipients);
            }
        }

        if ($forceBCCMail) {
            $allRecipients = array_merge($to, $toCC, $toBCC);
            $message->setBcc($allRecipients);
        } else {
            if (!empty($to)) {
                $message->setTo($to);
            }

            if (!empty($toCC)) {
                $message->setCC($toCC);
            }

            if (!empty($toBCC)) {
                $message->setBcc($toBCC);
            }
        }

        return $message;
    }

    private function getRecipients(FormInterface $form, $item)
    {
        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        $formData = $form->getData();
        $isSendToAll = (get_class($formData) == Send::class ? (is_null($formData->getSendToAll())
            ? false : $formData->getSendToAll()) : $form->has('send_to_all') && $formData['send_to_all']);

        if ($isSendToAll) {
            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();
            $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
            $userManager->select();
            $userList = $userManager->get();

            $this->addRecipients($recipients, $userList);
        }

        $isSendToAttendees = (get_class($formData) == Send::class ? (is_null($formData->getSendToAttendees())
            ? false : $formData->getSendToAttendees()) : $form->has('send_to_attendees') && $formData['send_to_attendees']);

        // form option: send_to_attendees
        if ($isSendToAttendees) {
            if ($item instanceof \cs_dates_item) {
                $attendees = $item->getParticipantsItemList();
                $this->addRecipients($recipients, $attendees);
            }
        }

        // form option: send_to_assigned
        $isSendToAssigned = (get_class($formData) == Send::class ? (is_null($formData->getSendToAttendees())
            ? false : $formData->getSendToAttendees()) : $form->has('send_to_assigned') && $formData['send_to_assigned']);

        if ($isSendToAssigned) {
            if ($item instanceof \cs_todo_item) {
                $processors = $item->getProcessorItemList();
                $this->addRecipients($recipients, $processors);
            }
        }

        // form option: send_to_group_all - if group rubric is not active
        $isSendToGroupAll = (get_class($formData) == Send::class ? (is_null($formData->getSendToGroupAll())
            ? false : $formData->getSendToGroupAll()) : $form->has('send_to_group_all') && $formData['send_to_group_all']);

        if ($isSendToGroupAll) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
            $userList = $currentContextItem->getUserList();

            $this->addRecipients($recipients, $userList);
        }

        // form option: send_to_groups
        $isSendToGroups = (get_class($formData) == Send::class ? (is_null($formData->getSendToGroups())
            ? false : $formData->getSendToGroups()) : $form->has('send_to_groups') && !empty($formData['send_to_groups']));

        if ($isSendToGroups && $form->has('send_to_groups')) {
            $labelManager = $this->legacyEnvironment->getLabelManager();
            $groups = $labelManager->getItemList($formData->getSendToGroups());


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

    /**
     * Adds the given files as attachments to the given message.
     * @param File[] $files The array of File objects which shall be added as attachments to the given message.
     * @param \Swift_Message $message The message to which the given files shall be added as attachments.
     * @return \Swift_Message The message with added attachments.
     */
    public function addAttachments(array $files, \Swift_Message $message): \Swift_Message
    {
        if (empty($files)) {
            return $message;
        }

        foreach ($files as $file) {
            $filePath = $file->getFilePath();
            $attachFile = $file->getChecked();
            if (!$attachFile || empty($filePath)) {
                continue;
            }

            $attachment = \Swift_Attachment::fromPath($filePath);

            $fileName = $file->getFilename();
            if (!empty($fileName)) {
                $attachment->setFilename($fileName);
            }

            $message->attach($attachment);
        }

        return $message;
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