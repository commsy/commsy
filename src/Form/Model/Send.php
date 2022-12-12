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

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject(mixed $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getSendToAttendees()
    {
        return $this->sendToAttendees;
    }

    public function setSendToAttendees(mixed $sendToAttendees): void
    {
        $this->sendToAttendees = $sendToAttendees;
    }

    /**
     * @return mixed
     */
    public function getSendToAssigned()
    {
        return $this->sendToAssigned;
    }

    public function setSendToAssigned(mixed $sendToAssigned): void
    {
        $this->sendToAssigned = $sendToAssigned;
    }

    /**
     * @return mixed
     */
    public function getSendToGroupAll()
    {
        return $this->sendToGroupAll;
    }

    public function setSendToGroupAll(mixed $sendToGroupAll): void
    {
        $this->sendToGroupAll = $sendToGroupAll;
    }

    /**
     * @return mixed
     */
    public function getSendToGroups()
    {
        return $this->sendToGroups;
    }

    public function setSendToGroups(mixed $sendToGroups): void
    {
        $this->sendToGroups = $sendToGroups;
    }

    /**
     * @return mixed
     */
    public function getSendToInstitutions()
    {
        return $this->sendToInstitutions;
    }

    public function setSendToInstitutions(mixed $sendToInstitutions): void
    {
        $this->sendToInstitutions = $sendToInstitutions;
    }

    /**
     * @return mixed
     */
    public function getSendToAll()
    {
        return $this->sendToAll;
    }

    public function setSendToAll(mixed $sendToAll): void
    {
        $this->sendToAll = $sendToAll;
    }

    /**
     * @return mixed
     */
    public function getSendToSelected()
    {
        return $this->sendToSelected;
    }

    public function setSendToSelected(mixed $sendToSelected): void
    {
        $this->sendToSelected = $sendToSelected;
    }

    /**
     * @return mixed
     */
    public function getSendToCreator()
    {
        return $this->sendToCreator;
    }

    public function setSendToCreator(mixed $sendToCreator): void
    {
        $this->sendToCreator = $sendToCreator;
    }

    /**
     * @return mixed
     */
    public function getCopyToSender()
    {
        return $this->copyToSender;
    }

    public function setCopyToSender(mixed $copyToSender): void
    {
        $this->copyToSender = $copyToSender;
    }

    /**
     * @return mixed
     */
    public function getAdditionalRecipients()
    {
        return $this->additionalRecipients;
    }

    public function setAdditionalRecipients(mixed $additionalRecipients): void
    {
        $this->additionalRecipients = $additionalRecipients;
    }

    /**
     * @return mixed
     */
    public function getUpload()
    {
        return $this->upload;
    }

    public function setUpload(mixed $upload): void
    {
        $this->upload = $upload;
    }

    /**
     * @return mixed
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function setFiles(mixed $files): void
    {
        $this->files = $files;
    }
}
