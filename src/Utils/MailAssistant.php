<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Form\Model\File;
use App\Form\Model\Send;
use App\Mail\Mailer;
use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_environment;
use cs_item;
use cs_list;
use cs_todo_item;
use cs_user_item;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailAssistant
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private Mailer $mailer,
        private Environment $twig
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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

    public function showGroupRecipients($item)
    {
        $groupArray = $this->getChoicesByLabelType('group');

        if ($this->legacyEnvironment->inProjectRoom() && !empty($groupArray)) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContextItem->withRubric('group') && !$currentContextItem->withRubric('project')) {
                return true;
            }
        }

        return false;
    }

    public function showInstitutionRecipients($item)
    {
        $institutionArray = $this->getChoicesByLabelType('institution');

        if ($this->legacyEnvironment->inCommunityRoom() && !empty($institutionArray)) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContextItem->withRubric('institution')) {
                return true;
            }
        }

        return false;
    }

    public function showGroupAllRecipients($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($currentContextItem->isProjectRoom() && !$currentContextItem->withRubric('group')) {
            return true;
        }

        if ($currentContextItem->isCommunityRoom() && !$currentContextItem->withRubric('group')) {
            return true;
        }

        return false;
    }

    public function getGroupChoices($item)
    {
        return $this->getChoicesByLabelType('group');
    }

    public function getInstitutionChoices($item)
    {
        return $this->getChoicesByLabelType('institution');
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

    public function handleItemSendMessage(
        FormInterface $form,
        cs_item $item,
        string $from
    ): int
    {
        $recipientCount = 0;
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $formData = $form->getData();

        $replyTo = [];
        if ($currentUser->isEmailVisible()) {
            $replyTo[] = new Address($currentUser->getEmail(), $currentUser->getFullName());
        }

        $formDataSubject = (Send::class == $formData::class ? (is_null($formData->getSubject()) ? false : $formData->getSubject()) : $formData['subject']);
        $formDataMessage = (Send::class == $formData::class ? (is_null($formData->getMessage()) ? false : $formData->getMessage()) : $formData['message']);

        $message = (new Email())
            ->subject($formDataSubject)
            ->html($formDataMessage)
            ->replyTo(...$replyTo);

        // form option: files
        $formDataFiles = (Send::class == $formData::class ? (is_null($formData->getFiles()) ? false : $formData->getFiles()) : $formData['files']);
        if ($formDataFiles) {
            $message = $this->addAttachments($formDataFiles, $message);
        }

        // form option: copy_to_sender
        $isSendToCreator = (Send::class == $formData::class ? (is_null($formData->getSendToCreator()) ? false : $formData->getSendToCreator()) : $form->has('send_to_creator') && $formData['send_to_creator']);
        if ($isSendToCreator) {
            $recipientCount++;
            $itemCreator = $item->getCreatorItem();
            $creatorMessage = clone $message;
            $creatorMessage->to(new Address($itemCreator->getEmail(), $itemCreator->getFullName()));
            $this->mailer->sendEmailObject($creatorMessage, $from);
        }

        $isCopyToSender = (Send::class == $formData::class ? (is_null($formData->getCopyToSender()) ? false : $formData->getCopyToSender()) : $form->has('copy_to_sender') && $formData['copy_to_sender']);
        if ($isCopyToSender) {
            $recipientCount++;
            $senderMessage = clone $message;
            $senderMessage->to(new Address($currentUser->getEmail(), $currentUser->getFullName()));
            $this->mailer->sendEmailObject($senderMessage, $from);
        }

        $recipients = $this->getRecipients($form, $item);

        // form option: additional_recipients
        $isAdditionalRecipients = (Send::class == $formData::class ? !is_null($formData->getAdditionalRecipients()) : $form->has('additional_recipients'));
        if ($isAdditionalRecipients) {
            $formDataAdditionalRecipients = (Send::class == $formData::class
                ? ($formData->getAdditionalRecipients()) : $formData['additional_recipients']);
            $additionalRecipients = array_filter($formDataAdditionalRecipients);

            if (!empty($additionalRecipients)) {
                $recipients = array_merge($recipients, array_combine($additionalRecipients, $additionalRecipients));
            }
        }

        foreach ($recipients as $email => $name) {
            $recipientCount++;
            $message->to(new Address($email, $name));
            $this->mailer->sendEmailObject($message, $from);
        }

        return $recipientCount;
    }

    private function getRecipients(FormInterface $form, $item): array
    {
        $recipients = new cs_list();

        $formData = $form->getData();
        $isSendToAll = (Send::class == $formData::class ? (is_null($formData->getSendToAll()) ? false : $formData->getSendToAll()) : $form->has('send_to_all') && $formData['send_to_all']);

        if ($isSendToAll) {
            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();
            $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
            $userManager->select();

            $recipients->addList($userManager->get());
        }

        $isSendToAttendees = (Send::class == $formData::class ? (is_null($formData->getSendToAttendees()) ? false : $formData->getSendToAttendees()) : $form->has('send_to_attendees') && $formData['send_to_attendees']);

        // form option: send_to_attendees
        if ($isSendToAttendees) {
            if ($item instanceof cs_dates_item) {
                $recipients->addList($item->getParticipantsItemList());
            }
        }

        // form option: send_to_assigned
        $isSendToAssigned = (Send::class == $formData::class ? (is_null($formData->getSendToAttendees()) ? false : $formData->getSendToAttendees()) : $form->has('send_to_assigned') && $formData['send_to_assigned']);

        if ($isSendToAssigned) {
            if ($item instanceof cs_todo_item) {
                $recipients->addList($item->getProcessorItemList());
            }
        }

        // form option: send_to_group_all - if group rubric is not active
        $isSendToGroupAll = (Send::class == $formData::class ? (is_null($formData->getSendToGroupAll()) ? false : $formData->getSendToGroupAll()) : $form->has('send_to_group_all') && $formData['send_to_group_all']);

        if ($isSendToGroupAll) {
            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
            $recipients->addList($currentContextItem->getUserList());
        }

        // form option: send_to_groups
        $isSendToGroups = (Send::class == $formData::class ? (is_null($formData->getSendToGroups())
            ? false : $formData->getSendToGroups()) : $form->has('send_to_groups') && !empty($formData['send_to_groups']));

        if ($isSendToGroups && $form->has('send_to_groups')) {
            $labelManager = $this->legacyEnvironment->getLabelManager();
            $groups = $labelManager->getItemList($formData->getSendToGroups());

            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->resetLimits();
            $userManager->setUserLimit();

            foreach ($groups as $group) {
                $userManager->setGroupLimit($group->getItemID());
                $userManager->select();

                $recipients->addList($userManager->get());
            }
        }

        $recipientArray = [];
        foreach ($recipients as $recipient) {
            /** @var cs_user_item $recipient */
            if (!array_key_exists($recipient->getEmail(), $recipientArray)) {
                $recipientArray[$recipient->getEmail()] = $recipient->getFullName();
            }
        }

        return $recipientArray;
    }

    /**
     * Adds the given files as attachments to the given message.
     *
     * @param File[] $files   the array of File objects which shall be added as attachments to the given message
     * @param Email  $message the message to which the given files shall be added as attachments
     *
     * @return Email the message with added attachments
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

    /** Retrieves all form choices by label type in the current context.
     *   @param $type: typ of label, e.g. 'topic', 'group' or 'institution'
     *
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

        $choiceArray = [];
        if ($labelList->getCount() > 0) {
            $labelItem = $labelList->getFirst();

            while ($labelItem) {
                $choiceArray[$labelItem->getName()] = $labelItem->getItemID();

                $labelItem = $labelList->getNext();
            }
        }

        return $choiceArray;
    }
}
