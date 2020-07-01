<?php


namespace App\Entity;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AccountIndexSendMergeMail extends AbstractType
{

    private $recipients;

    private $sender;

    private $subject;

    private $message;

    private $names;

    private $copyCCToModertor;

    private $copyBCCToModerator;

    private $copyCCToSender;

    private $copyBCCToSender;

    private $bcc;

    /**
     * @return mixed
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @param mixed $recipients
     */
    public function setRecipients($recipients): void
    {
        $this->recipients = $recipients;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender): void
    {
        $this->sender = $sender;
    }

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
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param mixed $names
     */
    public function setNames($names): void
    {
        $this->names = $names;
    }

    /**
     * @return mixed
     */
    public function getCopyCCToModertor()
    {
        return $this->copyCCToModertor;
    }

    /**
     * @param mixed $copyCCToModertor
     */
    public function setCopyCCToModertor($copyCCToModertor): void
    {
        $this->copyCCToModertor = $copyCCToModertor;
    }

    /**
     * @return mixed
     */
    public function getCopyBCCToModerator()
    {
        return $this->copyBCCToModerator;
    }

    /**
     * @param mixed $copyBCCToModerator
     */
    public function setCopyBCCToModerator($copyBCCToModerator): void
    {
        $this->copyBCCToModerator = $copyBCCToModerator;
    }

    /**
     * @return mixed
     */
    public function getCopyCCToSender()
    {
        return $this->copyCCToSender;
    }

    /**
     * @param mixed $copyCCToSender
     */
    public function setCopyCCToSender($copyCCToSender): void
    {
        $this->copyCCToSender = $copyCCToSender;
    }

    /**
     * @return mixed
     */
    public function getCopyBCCToSender()
    {
        return $this->copyBCCToSender;
    }

    /**
     * @param mixed $copyBCCToSender
     */
    public function setCopyBCCToSender($copyBCCToSender): void
    {
        $this->copyBCCToSender = $copyBCCToSender;
    }

    /**
     * @return mixed
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param mixed $bcc
     */
    public function setBcc($bcc): void
    {
        $this->bcc = $bcc;
    }


}