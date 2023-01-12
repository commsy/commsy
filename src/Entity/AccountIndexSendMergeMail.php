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

namespace App\Entity;

use Symfony\Component\Form\AbstractType;

class AccountIndexSendMergeMail extends AbstractType
{
    /**
     * @var mixed|null
     */
    private $recipients;

    /**
     * @var mixed|null
     */
    private $sender;

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
    private $names;

    /**
     * @var mixed|null
     */
    private $copyCCToModertor;

    /**
     * @var mixed|null
     */
    private $copyBCCToModerator;

    /**
     * @var mixed|null
     */
    private $copyCCToSender;

    /**
     * @var mixed|null
     */
    private $copyBCCToSender;

    /**
     * @var mixed|null
     */
    private $bcc;

    /**
     * @return mixed
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    public function setRecipients(mixed $recipients): void
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

    public function setSender(mixed $sender): void
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
    public function getNames()
    {
        return $this->names;
    }

    public function setNames(mixed $names): void
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

    public function setCopyCCToModertor(mixed $copyCCToModertor): void
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

    public function setCopyBCCToModerator(mixed $copyBCCToModerator): void
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

    public function setCopyCCToSender(mixed $copyCCToSender): void
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

    public function setCopyBCCToSender(mixed $copyBCCToSender): void
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

    public function setBcc(mixed $bcc): void
    {
        $this->bcc = $bcc;
    }
}
