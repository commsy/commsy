<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 18:31
 */

namespace App\Mail;


use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class Mailer
{
    /**
     * @var MessageBuilder $messageBuilder
     */
    private MessageBuilder $messageBuilder;

    /**
     * @var MailerInterface
     */
    private MailerInterface $symfonyMailer;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    public function __construct(
        MessageBuilder $messageBuilder,
        MailerInterface $symfonyMailer,
        LoggerInterface $logger
    ) {
        $this->messageBuilder = $messageBuilder;
        $this->symfonyMailer = $symfonyMailer;
        $this->logger = $logger;
    }

    /**
     * @param Email $email
     * @param string $fromSenderName
     * @return bool
     */
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
        } catch (TransportExceptionInterface $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param Recipient $recipient
     * @param string $fromSenderName
     * @param array $replyTo
     * @param array $cc
     * @return bool
     */
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
        } catch (TransportExceptionInterface $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array $recipients
     * @param string $fromSenderName
     * @param array $replyTo
     * @param array $cc
     * @return bool
     */
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
            $withErrors = $withErrors || ($send === false);
        }

        return !$withErrors;
    }

    /**
     * Sends the given message to all recipients.
     *
     * @param MessageInterface $message The message to send
     * @param string $fromSenderName The from name of the sender
     * @param Recipient recipient The recipient
     * @param array $replyTo Reply to in the form of email => name
     *
     * @return bool The success status
     */
    public function send(
        MessageInterface $message,
        string $fromSenderName,
        Recipient $recipient,
        array $replyTo = []
    ): bool {
        try {
            $email = $this->messageBuilder->generateFromMessage($message, $fromSenderName, $recipient, $replyTo);
            $this->symfonyMailer->send($email);
        } catch (RfcComplianceException $e) {
            $this->logger->warning('Message cannot be generated, RFC violation.', [$e->getMessage()]);
            return false;
        } catch (TransportExceptionInterface $e) {
            return false;
        }

        return true;
    }

    /**
     * @param MessageInterface $message
     * @param array $recipients
     * @param string $fromSenderName
     * @param array $replyTo
     * @return bool
     */
    public function sendMultiple(
        MessageInterface $message,
        array $recipients,
        string $fromSenderName = 'CommSy',
        array $replyTo = []
    ): bool {
        $withErrors = false;

        foreach ($recipients as $recipient) {
            $send = $this->send($message, $fromSenderName, $recipient, $replyTo);
            $withErrors = $withErrors || ($send === false);
        }

        return !$withErrors;
    }
}