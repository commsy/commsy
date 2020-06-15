<?php


namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Send
{

    private $subject;

    private $message;

    private $sendToAttendees;

    private $sendToAssigned;

    private $sendToGroupAll;

    private $sendToGroups;

    private $sendToInstitutions;

    private $sendToAll;

    private $sendToSelected;

    private $sendToCreator;

    private $copyToSender;

    private $additionalRecipients;

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject): void
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

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
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

    /**
     * @param mixed $sendToAttendees
     */
    public function setSendToAttendees($sendToAttendees): void
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

    /**
     * @param mixed $sendToAssigned
     */
    public function setSendToAssigned($sendToAssigned): void
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

    /**
     * @param mixed $sendToGroupAll
     */
    public function setSendToGroupAll($sendToGroupAll): void
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

    /**
     * @param mixed $sendToGroups
     */
    public function setSendToGroups($sendToGroups): void
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

    /**
     * @param mixed $sendToInstitutions
     */
    public function setSendToInstitutions($sendToInstitutions): void
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

    /**
     * @param mixed $sendToAll
     */
    public function setSendToAll($sendToAll): void
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

    /**
     * @param mixed $sendToSelected
     */
    public function setSendToSelected($sendToSelected): void
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

    /**
     * @param mixed $sendToCreator
     */
    public function setSendToCreator($sendToCreator): void
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

    /**
     * @param mixed $copyToSender
     */
    public function setCopyToSender($copyToSender): void
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

    /**
     * @param mixed $additionalRecipients
     */
    public function setAdditionalRecipients($additionalRecipients): void
    {
        $this->additionalRecipients = $additionalRecipients;
    }

}