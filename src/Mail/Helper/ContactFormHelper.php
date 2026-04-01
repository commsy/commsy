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


namespace App\Mail\Helper;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Utils\MailAssistant;
use cs_user_item;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
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
        array $recipientUsers,
        string $additionalRecipient,
        bool $copyToSender
    ): EmailSendStatus {
        $email = (new Email())
            ->subject($subject)
            ->html($message);

        // reply to
        $validator = new EmailValidator();
        $currentUserEmail = $currentUser->getEmail();
        if ($validator->isValid($currentUserEmail, new RFCValidation())) {
            if ($currentUser->isEmailVisible()) {
                $email->replyTo(new Address($currentUserEmail, $currentUser->getFullName()));
            }
        }

        // files
        if (!empty($files)) {
            $email = $this->mailAssistant->addAttachments($files, $email);
        }

        $recipients = [];

        // copy to sender
        if ($copyToSender) {
            $recipients[] = RecipientFactory::createRecipient($currentUser);
        }

        // to
        foreach ($recipientUsers as $user) {
            if (!$user instanceof cs_user_item) {
                throw new InvalidArgumentException();
            }

            $recipients[] = RecipientFactory::createRecipient($user);
        }

        if (!empty($additionalRecipient)) {
            $recipients[] = RecipientFactory::createFromRaw($additionalRecipient);
        }

        // send email to each recipient individually
        return $this->mailer->sendEmailObject($email, $from, $recipients);
    }
}
