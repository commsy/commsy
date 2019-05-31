<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

class cs_mail
{
    private $errors = [];
    private $asHTML = false;

    private $message;
    private $subject;
    private $fromEmail;
    private $fromName;
    private $replyToEmail;
    private $replyToName;
    private $recipients;
    private $ccRecipients;
    private $bccRecipients;

    /** set_to information
     *
     * set the recipients. the email-adresses should be divided by ","
     *
     * @param string $recipients
     */
    public function set_to($recipients)
    {
        $this->recipients = $recipients;
    }

    /** set_cc_to information
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

    /** set_bcc_to information
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

    /** set_from_email information
     *
     * set the from-email in the header of the mail
     *
     * @param string $fromEmail
     */
    public function set_from_email($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /** set_from_name information
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

    /** set_reply_to_name information
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

    /** set_reply_to information
     *
     * set the reply_to in the header of the mail
     *
     * @param string $replyToEmail
     */
    public function set_reply_to_email($replyToEmail)
    {
        $this->replyToEmail = $replyToEmail;
    }

    /** set_subject information
     *
     * set the subject for the mail
     *
     * @param string $subject
     */
    public function set_subject($subject)
    {
        $this->subject = encode(AS_MAIL, $subject);
    }

    /** set_message information
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
     * Send the mail
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
        $message = new \Swift_Message();

        // body
        if ($this->asHTML) {
            $message->setBody($this->message, 'text/html');
        } else {
            $message->setBody($this->message, 'text/plain');
        }

        // from
        if (isset($this->fromName)) {
            $cleanedFromEmails = $this->filterValidEmails([$this->fromEmail]);
            if (!empty($cleanedFromEmails)) {
                $message->setFrom([$cleanedFromEmails[0] => $this->fromName]);
            }
        } else {
            $cleanedFromEmails = $this->filterValidEmails([$this->fromEmail]);
            if (!empty($cleanedFromEmails)) {
                $message->setFrom([$cleanedFromEmails[0]]);
            }
        }

        // reply
        if (isset($this->replyToName)) {
            $cleanedReplyToEmails = $this->filterValidEmails([$this->replyToEmail]);
            if (!empty($cleanedReplyToEmails)) {
                $message->setReplyTo([$cleanedReplyToEmails[0] => $this->replyToName]);
            }
        } else {
            if (isset($this->replyToEmail)) {
                $cleanedReplyToEmails = $this->filterValidEmails([$this->replyToEmail]);
                if (!empty($cleanedReplyToEmails)) {
                    $message->setReplyTo([$cleanedReplyToEmails[0]]);
                }
            } else {
                $cleanedFromEmails = $this->filterValidEmails([$this->fromEmail]);
                if (!empty($cleanedFromEmails)) {
                    $message->setReplyTo([$cleanedFromEmails[0]]);
                }
            }
        }

        // subject
        if (isset($this->subject)) {
            $message->setSubject($this->subject);
        } else {
            $message->setSubject('');
        }

        // to
        $to = explode(',', $this->cleanRecipients($this->recipients));
        if (!$to) {
            return false;
        }

        $message->setTo($to);

        // cc
        if (isset($this->ccRecipients)) {
            $cc = explode(',', $this->cleanRecipients($this->ccRecipients));
            if ($cc) {
                $message->setCc($cc);
            }
        }

        // bcc
        if (isset($this->bccRecipients)) {
            $bcc = explode(',', $this->cleanRecipients($this->bccRecipients));
            if ($bcc) {
                $message->setBcc($bcc);
            }
        }

        global $symfonyContainer;
        $mailer = $symfonyContainer->get('mailer');
        $transport = $symfonyContainer->get('swiftmailer.mailer.default.transport.real');

        $success = $mailer->send($message);

        $spool = $mailer->getTransport()->getSpool();
        $spool->flushQueue($transport);

        return $success;
    }

    public function getErrorArray()
    {
        return $this->errors;
    }

    private function filterValidEmails(array $emails)
    {
        $validator = new \Egulias\EmailValidator\EmailValidator();

        $validEmails = [];
        foreach ($emails as $email) {
            if ($validator->isValid($email, new \Egulias\EmailValidator\Validation\RFCValidation())) {
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
                    $mail_address .= ' ' . $value;
                    $retour2_array[] = $mail_address;
                    $mail_address = '';
                } else {
                    $mail_address .= ' ' . $value;
                }
            }
            $retour = implode(',', $retour2_array);
        }

        return $retour;
    }
}