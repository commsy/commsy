<?php

namespace App\Mail\Helper;

use App\Mail\Mailer;
use App\Utils\MailAssistant;
use cs_user_item;
use InvalidArgumentException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ContactFormHelper
{
    public function __construct(
        private MailAssistant $mailAssistant,
        private Mailer $mailer
    ) {
    }

    public function handleContactFormSending(
        string $subject,
        string $message,
        string $from,
        cs_user_item $currentUser,
        array $files,
        array $recipients,
        string $additionalRecipient,
        bool $copyToSender
    ): int {
        $recipientCount = 0;
        $message = (new Email())
            ->subject($subject)
            ->html($message);

        // reply to
        if ($currentUser->isEmailVisible()) {
            $message->replyTo(new Address($currentUser->getEmail(), $currentUser->getFullName()));
        }

        // files
        if (!empty($files)) {
            $message = $this->mailAssistant->addAttachments($files, $message);
        }

        // copy to sender
        if ($copyToSender) {
            $recipientCount++;
            $senderMessage = clone $message;
            $senderMessage->to(new Address($currentUser->getEmail(), $currentUser->getFullName()));
            $this->mailer->sendEmailObject($senderMessage, $from);
        }

        // to
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof cs_user_item) {
                throw new InvalidArgumentException();
            }

            $message->addTo(new Address($recipient->getEmail(), $recipient->getFullName()));
        }

        // cc
        if (!empty($additionalRecipient)) {
            $message->addCc(new Address($additionalRecipient));
        }

        $recipientCount += count($message->getTo()) + count($message->getCc());

        $this->mailer->sendEmailObject($message, $from);

        return $recipientCount;
    }
}
