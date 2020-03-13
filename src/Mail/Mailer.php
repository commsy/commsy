<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 18:31
 */

namespace App\Mail;


class Mailer
{
    /**
     * @var MessageBuilder $messageBuilder
     */
    private $messageBuilder;

    /**
     * @var \Swift_Mailer $swiftMailer
     */
    private $swiftMailer;

    public function __construct(MessageBuilder $messageBuilder, \Swift_Mailer $swiftMailer  )
    {
        $this->messageBuilder = $messageBuilder;
        $this->swiftMailer = $swiftMailer;
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
    public function send(MessageInterface $message, string $fromSenderName, Recipient $recipient, array $replyTo = []): bool
    {
        $swiftMessage = $this->messageBuilder->generateSwiftMessage($message, $fromSenderName, $recipient, $replyTo);

        if ($swiftMessage) {
            $successfullRecipients = $this->swiftMailer->send($swiftMessage);

            return $successfullRecipients > 0;
        }

        return false;
    }

    public function sendMultiple(MessageInterface $message, array $recipients, string $fromSenderName = 'CommSy', array $replyTo = []): bool
    {
        $withErrors = false;

        foreach ($recipients as $recipient) {
            $swiftMessage = $this->messageBuilder->generateSwiftMessage($message, $fromSenderName, $recipient, $replyTo);

            if ($swiftMessage) {
                $successfullRecipients = $this->swiftMailer->send($swiftMessage);

                $withErrors = $withErrors || ($successfullRecipients == 0);
            }
        }

        return !$withErrors;
    }

    /**
     * Sends the given message to all recipients on by one. This will generate a separate mail for each recipient.
     *
     * @param MessageInterface $message The message to send
     * @param string $fromSenderName The from name of the sender
     * @param Recipient[] Recipients in the form of email => name
     * @param array $replyTo Reply to in the form of email => name
     *
     * @return bool The success status
     */
    public function sendDetached(MessageInterface $message, string $fromSenderName, array $recipients, array $replyTo = []): bool
    {
        $withErrors = false;

        foreach ($recipients as $recipient) {
            $swiftMessage = $this->messageBuilder->generateSwiftMessage($message, $fromSenderName, [$recipient], $replyTo);

            if ($swiftMessage) {
                $successfullRecipients = $this->swiftMailer->send($swiftMessage);

                $withErrors = $withErrors || ($successfullRecipients == 0);
            }
        }

        return !$withErrors;
    }


    public function forceFlush()
    {
    }
}