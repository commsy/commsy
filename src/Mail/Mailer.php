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

namespace App\Mail;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class Mailer
{
    public function __construct(private MessageBuilder $messageBuilder, private MailerInterface $symfonyMailer, private LoggerInterface $logger)
    {
    }

    public function sendEmailObject(
        Email $email,
        string $fromSenderName = 'CommSy'
    ): bool {
        try {
            $email = $this->messageBuilder->generateFromEmail($email, $fromSenderName);
            $this->symfonyMailer->send($email);
        } catch (RfcComplianceException $e) {
            $this->logger->warning('Message cannot be generated, RFC violation.', [$e->getMessage()]);

            return false;
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    public function sendRaw(
        string $subject,
        string $message,
        Recipient $recipient,
        string $fromSenderName = 'CommSy',
        array $replyTo = [],
        array $cc = []
    ): bool {
        try {
            $email = $this->messageBuilder->generateFromString(
                $subject,
                $message,
                $fromSenderName,
                $recipient,
                $replyTo,
                $cc
            );
            $this->symfonyMailer->send($email);
        } catch (RfcComplianceException $e) {
            $this->logger->warning('Message cannot be generated, RFC violation.', [$e->getMessage()]);

            return false;
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    public function sendMultipleRaw(
        string $subject,
        string $message,
        array $recipients,
        string $fromSenderName = 'CommSy',
        array $replyTo = [],
        array $cc = []
    ): bool {
        $withErrors = false;

        foreach ($recipients as $recipient) {
            $send = $this->sendRaw($subject, $message, $recipient, $fromSenderName, $replyTo, $cc);
            $withErrors = $withErrors || (false === $send);
        }

        return !$withErrors;
    }

    /**
     * Sends the given message to all recipients.
     *
     * @param MessageInterface $message        The message to send
     * @param Recipient        $recipient      The recipient
     * @param string           $fromSenderName The from name of the sender
     * @param array            $replyTo        Reply to in the form of email => name
     *
     * @return bool The success status
     */
    public function send(
        MessageInterface $message,
        Recipient $recipient,
        string $fromSenderName = 'CommSy',
        array $replyTo = []
    ): bool {
        try {
            $email = $this->messageBuilder->generateFromMessage($message, $fromSenderName, $recipient, $replyTo);
            $this->symfonyMailer->send($email);
        } catch (RfcComplianceException $e) {
            $this->logger->warning('Message cannot be generated, RFC violation.', [$e->getMessage()]);

            return false;
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    public function sendMultiple(
        MessageInterface $message,
        array $recipients,
        string $fromSenderName = 'CommSy',
        array $replyTo = []
    ): bool {
        $withErrors = false;

        foreach ($recipients as $recipient) {
            $send = $this->send($message, $recipient, $fromSenderName, $replyTo);
            $withErrors = $withErrors || (false === $send);
        }

        return !$withErrors;
    }
}
