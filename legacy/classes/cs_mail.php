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

use App\Mail\Mailer;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class cs_mail
{
    private $errors = [];
    private bool $asHTML = false;

    /**
     * @var mixed|null
     */
    private $message;
    /**
     * @var mixed|null
     */
    private $subject;
    private $fromEmail;
    /**
     * @var mixed|null
     */
    private $fromName;
    private $replyToEmail;
    /**
     * @var mixed|null
     */
    private $replyToName;
    private $recipients;
    private $ccRecipients;
    private $bccRecipients;

    /** set_to information.
     *
     * set the recipients. the email-adresses should be divided by ","
     *
     * @param string $recipients
     */
    public function set_to($recipients)
    {
        $this->recipients = $recipients;
    }

    /** set_cc_to information.
     *
     * set the recipients
     *
     * @param string $recipients
     */
    public function set_cc_to($recipients)
    {
        if (is_array($recipients)) {
            $recipients = implode(', ', $recipients);
        }

        $this->ccRecipients = $recipients;
    }

    /** set_bcc_to information.
     *
     * set the recipients
     *
     * @param string $recipients
     */
    public function set_bcc_to($recipients)
    {
        if (is_array($recipients)) {
            $recipients = implode(', ', $recipients);
        }

        $this->bccRecipients = $recipients;
    }

    /** set_from_email information.
     *
     * set the from-email in the header of the mail
     *
     * @param string $fromEmail
     */
    public function set_from_email($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /** set_from_name information.
     *
     * set the from-name in the header of the mail
     *
     * @param string $fromName
     */
    public function set_from_name($fromName)
    {
        $fromName = str_replace(',', '', $fromName);
        $fromName = str_replace(':', '', $fromName);

        $this->fromName = encode(AS_MAIL, $fromName);
    }

    /** set_reply_to_name information.
     *
     * set the reply_to-name in the header of the mail
     *
     * @param string $replyToName
     */
    public function set_reply_to_name($replyToName)
    {
        $replyToName = str_replace(',', '', $replyToName);

        $this->replyToName = encode(AS_MAIL, $replyToName);
    }

    /** set_reply_to information.
     *
     * set the reply_to in the header of the mail
     *
     * @param string $replyToEmail
     */
    public function set_reply_to_email($replyToEmail)
    {
        $this->replyToEmail = $replyToEmail;
    }

    /** set_subject information.
     *
     * set the subject for the mail
     *
     * @param string $subject
     */
    public function set_subject($subject)
    {
        $this->subject = encode(AS_MAIL, $subject);
    }

    /** set_message information.
     *
     * set the subject for the mail
     *
     * @param string $message
     */
    public function set_message($message)
    {
        $this->message = encode(AS_MAIL, $message);
    }

    public function setSendAsHTML()
    {
        $this->asHTML = true;
    }

    /**
     * Send the mail.
     *
     * @param string $recipients
     * @param string $headers
     * @param string $body
     * @param string $return
     *
     * @return bool
     */
    public function send($recipients = '', $headers = '', $body = '', $return = '')
    {
        $message = new Email();

        // body
        if ($this->asHTML) {
            $message->html($this->message);
        } else {
            $message->text($this->message);
        }

        // reply
        if (isset($this->replyToName)) {
            $cleanedReplyToEmails = $this->filterValidEmails([$this->replyToEmail]);
            if (!empty($cleanedReplyToEmails)) {
                $message->replyTo(new Address($cleanedReplyToEmails[0], $this->replyToName));
            }
        } else {
            if (isset($this->replyToEmail)) {
                $cleanedReplyToEmails = $this->filterValidEmails([$this->replyToEmail]);
                if (!empty($cleanedReplyToEmails)) {
                    $message->replyTo(new Address($cleanedReplyToEmails[0]));
                }
            } else {
                $cleanedFromEmails = $this->filterValidEmails([$this->fromEmail]);
                if (!empty($cleanedFromEmails)) {
                    $message->replyTo(new Address($cleanedFromEmails[0]));
                }
            }
        }

        // subject
        if (isset($this->subject)) {
            $message->subject($this->subject);
        } else {
            $message->subject('');
        }

        // to
        $to = explode(',', $this->cleanRecipients($this->recipients));
        $to = array_filter($to, function ($email) {
            $validator = new EmailValidator();

            return $validator->isValid($email, new RFCValidation());
        });
        if (!$to) {
            return false;
        }

        $message->to(...$to);

        // cc
        if (isset($this->ccRecipients)) {
            $cc = explode(',', $this->cleanRecipients($this->ccRecipients));
            if ($cc) {
                $message->cc(...$cc);
            }
        }

        // bcc
        if (isset($this->bccRecipients)) {
            $bcc = explode(',', $this->cleanRecipients($this->bccRecipients));
            if ($bcc) {
                $message->bcc(...$bcc);
            }
        }

        global $symfonyContainer;

        /** @var Mailer $mailer */
        $mailer = $symfonyContainer->get(Mailer::class);

        return $mailer->sendEmailObject($message, $this->fromName ?? 'CommSy');
    }

    public function getErrorArray()
    {
        return $this->errors;
    }

    private function filterValidEmails(array $emails)
    {
        $validator = new EmailValidator();

        $validEmails = [];
        foreach ($emails as $email) {
            if ($validator->isValid($email, new RFCValidation())) {
                $validEmails[] = $email;
            }
        }

        return $validEmails;
    }

    private function cleanRecipients($value)
    {
        $retour = $value;
        $retour = str_replace(', ', ',', $retour);

        if (mb_substr_count($retour, '@') != mb_substr_count($retour, ',') + 1) {
            $retour_array = explode(',', $retour);
            $retour2_array = [];
            $mail_address = '';

            foreach ($retour_array as $value) {
                if (strstr($value, '@')) {
                    $mail_address .= ' '.$value;
                    $retour2_array[] = $mail_address;
                    $mail_address = '';
                } else {
                    $mail_address .= ' '.$value;
                }
            }
            $retour = implode(',', $retour2_array);
        }

        return $retour;
    }
}
