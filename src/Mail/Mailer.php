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

use App\Mail\Helper\EmailSendStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;

readonly class Mailer
{
    public function __construct(
        private MessageBuilder  $messageBuilder,
        private MailerInterface $symfonyMailer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sends the given email object individually to each recipient.
     *
     * @param Email        $email The email object to send
     * @param string       $fromSenderName The sender's name, defaults to 'CommSy'
     * @param ?Recipient[] $recipients The recipients for the given email; defaults to null in which case any existing
     * email recipient(s) retrieved via `$email->getTo` will be used; if a non-empty list of $recipients is given, this
     * will set `$email->to` thus overriding any existing email recipient(s)
     *
     * @return EmailSendStatus The email delivery status denoting e.g. whether the email was sent successfully
     * (`EmailSendStatus->isSuccess == true`) or not (`EmailSendStatus->isSuccess == false`)
     */
    public function sendEmailObject(
        Email $email,
        string $fromSenderName = 'CommSy',
        ?array $recipients = null
    ): EmailSendStatus {
        $deliveredRecipients = [];
        $failedRecipients = [];

        // NOTE: if there are no $recipients given explicitly, and $email itself already has some recipients set,
        // this ensures that the email is sent to each one individually
        if (empty($recipients)) {
            $recipients = RecipientFactory::createRecipientsFromEmail($email);
        }

        // only handle unique recipients
        $uniqueRecipients = [];
        foreach ($recipients as $recipient) {
            $uniqueRecipients[$recipient->getEmail()] = $recipient;
        }

        if (empty($uniqueRecipients)) {
            throw new NotFoundHttpException('Message cannot be generated: No recipients specified.');
        }

        // send an individual email to each of the unique recipients
        foreach ($uniqueRecipients as $recipient) {
            $success = true;

            try {
                $emailObject = clone $email;
                $emailObject = $this->messageBuilder->generateFromEmail($emailObject, $fromSenderName);

                $address = new Address($recipient->getEmail(), $recipient->getFullName());
                $emailObject->to($address);

                $this->symfonyMailer->send($emailObject);
            } catch (RfcComplianceException $e) {
                $this->logger->warning('Message cannot be generated, RFC violation.', [$e->getMessage()]);
                $success = false;
            } catch (TransportExceptionInterface) {
                $success = false;
            }

            if ($success) {
                $deliveredRecipients[$recipient->getEmail()] = $recipient;
            } else {
                $failedRecipients[$recipient->getEmail()] = $recipient;
            }
        }

        return new EmailSendStatus(!empty($uniqueRecipients) && $failedRecipients === [], count($uniqueRecipients), $deliveredRecipients, $failedRecipients);
    }

    /**
     * Creates an email object from the given parameters and sends it to the given recipient.
     *
     * @param string           $subject The email's subject
     * @param string           $message The email's message
     * @param Recipient        $recipient The recipient for the email
     * @param string           $fromSenderName The sender's name, defaults to 'CommSy'
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     * @param Address|string[] $cc List of Cc addresses (Address objects or string-based email addresses)
     *
     * @return EmailSendStatus The email delivery status denoting e.g. whether the email was sent successfully
     * (`EmailSendStatus->isSuccess == true`) or not (`EmailSendStatus->isSuccess == false`)
     */
    public function sendRaw(
        string $subject,
        string $message,
        Recipient $recipient,
        string $fromSenderName = 'CommSy',
        array $replyTo = [],
        array $cc = []
    ): EmailSendStatus {
        $email = $this->messageBuilder->generateFromString(
            $subject,
            $message,
            $fromSenderName,
            $recipient,
            $replyTo,
            $cc
        );

        return $this->sendEmailObject($email, $fromSenderName);
    }

    /**
     * Creates an email object from the given parameters and sends it to all recipients.
     *
     * @param string           $subject The email's subject
     * @param string           $message The email's message
     * @param Recipient[]      $recipients The recipients for the email
     * @param string           $fromSenderName The sender's name, defaults to 'CommSy'
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     * @param Address|string[] $cc List of Cc addresses (Address objects or string-based email addresses)
     *
     * @return EmailSendStatus The email delivery status denoting e.g. whether the email was sent successfully
     * (`EmailSendStatus->isSuccess == true`) or not (`EmailSendStatus->isSuccess == false`)
     */
    public function sendMultipleRaw(
        string $subject,
        string $message,
        array $recipients,
        string $fromSenderName = 'CommSy',
        array $replyTo = [],
        array $cc = []
    ): EmailSendStatus {
        $email = $this->messageBuilder->generateFromString(
            $subject,
            $message,
            $fromSenderName,
            null,
            $replyTo,
            $cc
        );

        return $this->sendEmailObject($email, $fromSenderName, $recipients);
    }

    /**
     * Sends the given message as an email to the given recipient.
     *
     * @param MessageInterface $message The message to send
     * @param Recipient        $recipient The recipient for the email
     * @param string           $fromSenderName The sender's name, defaults to 'CommSy'
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     *
     * @return EmailSendStatus The email delivery status denoting e.g. whether the email was sent successfully
     * (`EmailSendStatus->isSuccess == true`) or not (`EmailSendStatus->isSuccess == false`)
     */
    public function send(
        MessageInterface $message,
        Recipient $recipient,
        string $fromSenderName = 'CommSy',
        array $replyTo = []
    ): EmailSendStatus {
        $email = $this->messageBuilder->generateFromMessage($message, $fromSenderName, $recipient, $replyTo);

        return $this->sendEmailObject($email, $fromSenderName);
    }

    /**
     * Sends the given message as an email to all recipients.
     *
     * @param MessageInterface $message The message to send
     * @param Recipient[]      $recipients The recipients for the email
     * @param string           $fromSenderName The sender's name, defaults to 'CommSy'
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     *
     * @return EmailSendStatus The email delivery status denoting e.g. whether the email was sent successfully
     * (`EmailSendStatus->isSuccess == true`) or not (`EmailSendStatus->isSuccess == false`)
     */
    public function sendMultiple(
        MessageInterface $message,
        array $recipients,
        string $fromSenderName = 'CommSy',
        array $replyTo = []
    ): EmailSendStatus {
        $email = $this->messageBuilder->generateFromMessage($message, $fromSenderName, null, $replyTo);

        return $this->sendEmailObject($email, $fromSenderName, $recipients);
    }
}
