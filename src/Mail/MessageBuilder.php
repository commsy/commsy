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

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageBuilder
{
    public function __construct(private TranslatorInterface $translator, private string $emailFrom)
    {
    }

    public function generateFromEmail(
        Email $email,
        string $fromSenderName
    ): Email {
        $email->from(new Address($this->emailFrom, $fromSenderName));

        return $email;
    }

    public function generateFromString(
        string $subject,
        string $message,
        string $fromSenderName,
        Recipient $recipient,
        array $replyTo = [],
        array $cc = []
    ): Email {
        $email = (new Email())
            ->subject($subject)
            ->from(new Address($this->emailFrom, $fromSenderName))
            ->html($message);

        // To
        if (!empty($recipient->getFirstname()) || !empty($recipient->getLastname())) {
            $email->to(new Address(
                $recipient->getEmail(),
                $recipient->getFirstname().' '.$recipient->getLastname()
            ));
        } else {
            $email->to(new Address($recipient->getEmail()));
        }

        // Reply-To
        if (!empty($replyTo)) {
            $email->replyTo(...$replyTo);
        }

        // Cc
        if (!empty($cc)) {
            $email->cc(...$cc);
        }

        return $email;
    }

    public function generateFromMessage(
        MessageInterface $message,
        string $fromSenderName,
        Recipient $recipient,
        array $replyTo = []
    ): Email {
        $locale = $this->translator->getLocale();
        $this->translator->setLocale($recipient->getLanguage());

        $email = (new TemplatedEmail())
            ->from(new Address($this->emailFrom, $fromSenderName));

        // Subject
        $subject = $this->translator->trans($message->getSubject(), $message->getTranslationParameters(), 'mail');
        $email->subject($subject);

        // To
        if (!empty($recipient->getFirstname()) || !empty($recipient->getLastname())) {
            $email->to(new Address(
                $recipient->getEmail(),
                $recipient->getFirstname().' '.$recipient->getLastname()
            ));
        } else {
            $email->to(new Address($recipient->getEmail()));
        }

        // Reply-To
        if (!empty($replyTo)) {
            $email->replyTo(...$replyTo);
        }

        // Body
        $email->htmlTemplate($message->getTemplateName());
        $email->context($message->getParameters());

        // Restore the previous locale
        $this->translator->setLocale($locale);

        return $email;
    }
}
