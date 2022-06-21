<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 18:36
 */

namespace App\Mail;


use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageBuilder
{
    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var string $emailFrom
     */
    private string $emailFrom;

    public function __construct(
        TranslatorInterface $translator,
        string $emailFrom
    ) {
        $this->translator = $translator;
        $this->emailFrom = $emailFrom;
    }

    /**
     * @param Email $email
     * @param string $fromSenderName
     * @return Email
     */
    public function generateFromEmail(
        Email $email,
        string $fromSenderName
    ): Email {
        $email->from(new Address($this->emailFrom, $fromSenderName));

        return $email;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param string $fromSenderName
     * @param Recipient $recipient
     * @param array $replyTo
     * @param array $cc
     * @return Email
     */
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
                $recipient->getFirstname() . ' ' . $recipient->getLastname()
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

    /**
     * @param MessageInterface $message
     * @param string $fromSenderName
     * @param Recipient $recipient
     * @param array $replyTo
     * @return Email
     */
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
                $recipient->getFirstname() . ' ' . $recipient->getLastname()
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