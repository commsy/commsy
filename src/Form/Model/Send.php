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

namespace App\Form\Model;

class Send
{
    /**
     * @var mixed|null
     */
    private $subject;

    /**
     * @var mixed|null
     */
    private $message;

    /**
     * @var mixed|null
     */
    private $sendToAttendees;

    /**
     * @var mixed|null
     */
    private $sendToAssigned;

    /**
     * @var mixed|null
     */
    private $sendToGroupAll;

    /**
     * @var mixed|null
     */
    private $sendToGroups;

    /**
     * @var mixed|null
     */
    private $sendToInstitutions;

    /**
     * @var mixed|null
     */
    private $sendToAll;

    /**
     * @var mixed|null
     */
    private $sendToSelected;

    /**
     * @var mixed|null
     */
    private $sendToCreator;

    /**
     * @var mixed|null
     */
    private $copyToSender;

    /**
     * @var mixed|null
     */
    private $additionalRecipients;

    /**
     * @var mixed|null
     */
    private $upload;

    /**
     * @var mixed|null
     */
    private $files;

    public function getSubject(): mixed
    {
        return $this->subject;
    }

    public function setSubject(mixed $subject): void
    {
        $this->subject = $subject;
    }

    public function getMessage(): mixed
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }

    public function getSendToAttendees(): mixed
    {
        return $this->sendToAttendees;
    }

    public function setSendToAttendees(mixed $sendToAttendees): void
    {
        $this->sendToAttendees = $sendToAttendees;
    }

    public function getSendToAssigned(): mixed
    {
        return $this->sendToAssigned;
    }

    public function setSendToAssigned(mixed $sendToAssigned): void
    {
        $this->sendToAssigned = $sendToAssigned;
    }

    public function getSendToGroupAll(): mixed
    {
        return $this->sendToGroupAll;
    }

    public function setSendToGroupAll(mixed $sendToGroupAll): void
    {
        $this->sendToGroupAll = $sendToGroupAll;
    }

    public function getSendToGroups(): mixed
    {
        return $this->sendToGroups;
    }

    public function setSendToGroups(mixed $sendToGroups): void
    {
        $this->sendToGroups = $sendToGroups;
    }

    public function getSendToInstitutions(): mixed
    {
        return $this->sendToInstitutions;
    }

    public function setSendToInstitutions(mixed $sendToInstitutions): void
    {
        $this->sendToInstitutions = $sendToInstitutions;
    }

    public function getSendToAll(): mixed
    {
        return $this->sendToAll;
    }

    public function setSendToAll(mixed $sendToAll): void
    {
        $this->sendToAll = $sendToAll;
    }

    public function getSendToSelected(): mixed
    {
        return $this->sendToSelected;
    }

    public function setSendToSelected(mixed $sendToSelected): void
    {
        $this->sendToSelected = $sendToSelected;
    }

    public function getSendToCreator(): mixed
    {
        return $this->sendToCreator;
    }

    public function setSendToCreator(mixed $sendToCreator): void
    {
        $this->sendToCreator = $sendToCreator;
    }

    public function getCopyToSender(): mixed
    {
        return $this->copyToSender;
    }

    public function setCopyToSender(mixed $copyToSender): void
    {
        $this->copyToSender = $copyToSender;
    }

    public function getAdditionalRecipients(): mixed
    {
        return $this->additionalRecipients;
    }

    public function setAdditionalRecipients(mixed $additionalRecipients): void
    {
        $this->additionalRecipients = $additionalRecipients;
    }

    public function getUpload(): mixed
    {
        return $this->upload;
    }

    public function setUpload(mixed $upload): void
    {
        $this->upload = $upload;
    }

    public function getFiles(): mixed
    {
        return $this->files;
    }

    public function setFiles(mixed $files): void
    {
        $this->files = $files;
    }
}
