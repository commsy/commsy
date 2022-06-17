<?php

namespace App\Utils;

use App\Form\Model\File;
use App\Form\Model\Send;
use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;


class MailAssistant
{
    private cs_environment $legacyEnvironment;

    private Environment $twig;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Environment $twig
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->twig = $twig;
    }

    public function prepareMessage($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        return $this->twig->render('mail/send.html.twig', [
            'contextItem' => $currentContextItem,
            'currentUser' => $currentUser,
            'item' => $item,
            'content' => [],
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

    public function getUserContactMessage(
        FormInterface $form,
        $item,
        $moderatorIds,
        UserService $userService
    ): Email {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = [
            'to' => [],
            'bcc' => [],
        ];

        $recipients['to'][$item->getEmail()] = $item->getFullName();
        if(!is_null($moderatorIds)){
            $moderatorIds = explode(', ', $moderatorIds);
            foreach($moderatorIds as $moderatorId){
                $moderator = $userService->getUser($moderatorId);
                $recipients['to'][$moderator->getEmail()] = $moderator->getFullName();
            }
        }

        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[] = new Address($currentUserEmail, $currentUserName);
        }

        $formDataSubject = $formData['subject'];
        $formDataMessage = $formData['message'];

        $message = (new Email())
            ->subject($formDataSubject)
            ->html($formDataMessage ?: '')
            ->replyTo(...$replyTo);

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

        $allRecipients = array_merge($to, $toCC, $toBCC);
        $message->bcc(...$this->convertArrayToAddresses($allRecipients));

        return $message;
    }

    public function getAccountIndexActionMessage(
        FormInterface $form,
        $item
    ): Email {
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
            $replyTo[] = new Address($currentUserEmail, $currentUserName);
        }

        $formDataSubject = $formData->getSubject();

        $formDataMessage = $formData->getMessage();

        $message = (new Email())
            ->subject($formDataSubject)
            ->html($formDataMessage)
            ->replyTo(...$replyTo);

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

        if (!empty($to)) {
            $message->to(...$this->convertArrayToAddresses($to));
        }

        if (!empty($toCC)) {
            $message->cc(...$this->convertArrayToAddresses($toCC));
        }

        if (!empty($toBCC)) {
            $message->bcc(...$this->convertArrayToAddresses($toBCC));
        }

        return $message;
    }

    public function getAccountIndexPasswordMessage(
        FormInterface $form,
        $item)
    : Email {
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
            $replyTo[] = new Address($currentUserEmail, $currentUserName);
        }

        $formDataSubject = $formData->getSubject();

        $formDataMessage = $formData->getMessage();

        $message = (new Email())
            ->subject($formDataSubject)
            ->html($formDataMessage)
            ->replyTo(...$replyTo);

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
            $message->to(...$this->convertArrayToAddresses($to));
        }

        if (!empty($toCC)) {
            $message->cc(...$this->convertArrayToAddresses($toCC));
        }

        if (!empty($toBCC)) {
            $message->bcc(...$this->convertArrayToAddresses($toBCC));
        }

        return $message;
    }

    public function getItemSendMessage(FormInterface $form, \cs_item $item): Email
    {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $recipients = $this->getRecipients($form, $item);
        $to = $recipients['to'];
        $toBCC = $recipients['bcc'];

        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        $currentUserName = $currentUser->getFullName();
        if ($currentUser->isEmailVisible()) {
            $replyTo[] = new Address($currentUserEmail, $currentUserName);
        }

        $formDataSubject = (get_class($formData) == Send::class ? (is_null($formData->getSubject())
            ? false : $formData->getSubject()) : $formData['subject']);

        $formDataMessage = (get_class($formData) == Send::class ? (is_null($formData->getMessage())
            ? false : $formData->getMessage()) : $formData['message']);

        $message = (new Email())
            ->subject($formDataSubject)
            ->html($formDataMessage)
            ->replyTo(...$replyTo);

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

        $allRecipients = array_merge($to, $toCC, $toBCC);
        $message->bcc(...$this->convertArrayToAddresses($allRecipients));

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
     * @param Email $message The message to which the given files shall be added as attachments.
     * @return Email The message with added attachments.
     */
    public function addAttachments(array $files, Email $message): Email
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

            $fileName = $file->getFilename();
            $message->attachFromPath($filePath, !empty($fileName) ? $fileName : null);
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

    /**
     * @param array $recipients
     * @return array
     */
    public function convertArrayToAddresses(array $recipients): array
    {
        $addresses = [];

        foreach ($recipients as $email => $name) {
            $addresses[] = new Address($email, $name);
        }

        return $addresses;
    }
}