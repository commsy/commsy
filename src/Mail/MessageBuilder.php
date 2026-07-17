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

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class MessageBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private string $emailFrom,
        private LoggerInterface $logger,
        private LocaleSwitcher $localeSwitcher,
        #[Autowire(param: 'locale')]
        private string $defaultLocale,
    ) {
    }

    /**
     * Sets the From address for the given email object to this class's default From address and returns it.
     *
     * @param Email  $email The email to be modified
     * @param string $fromSenderName The sender's name
     *
     * @return Email The modified email object
     */
    public function generateFromEmail(
        Email $email,
        string $fromSenderName
    ): Email {
        $email = $this->setFromAddress($email, $this->emailFrom, $fromSenderName);

        return $email;
    }

    /**
     * Creates an email object from the given subject, HTML string and sender/recipient parameters.
     *
     * @param string           $subject The email's subject
     * @param string           $htmlMessage The email's message
     * @param string           $fromSenderName The sender's name
     * @param Recipient|null   $recipient The recipient for the email, defaults to null
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     * @param Address|string[] $cc List of Cc addresses (Address objects or string-based email addresses)
     *
     * @return Email The generated email object
     */
    public function generateFromString(
        string $subject,
        string $htmlMessage,
        string $fromSenderName,
        ?Recipient $recipient = null,
        array $replyTo = [],
        array $cc = []
    ): Email {
        $email = (new Email())
            ->subject($subject)
            ->html($htmlMessage);

        // From
        $email = $this->setFromAddress($email, $this->emailFrom, $fromSenderName);

        // To
        if ($recipient) {
            $email = $this->setToAddress($email, $recipient->getEmail(), $recipient->getFullName());
        }

        // Reply-To
        $email = $this->addReplyToAddresses($email, $replyTo);

        // Cc
        $email = $this->addCcAddresses($email, $cc);

        return $email;
    }

    /**
     * Creates an email object from the given message object and sender/recipient parameters.
     *
     * @param MessageInterface $message The email's message
     * @param string           $fromSenderName The sender's name
     * @param Recipient        $recipient The recipient for the email
     * @param Address|string[] $replyTo List of Reply to addresses (Address objects or string-based email addresses)
     *
     * @return Email The generated email object
     */
    public function generateFromMessage(
        MessageInterface $message,
        string $fromSenderName,
        Recipient $recipient,
        array $replyTo = []
    ): Email {
        // From
        $email = $this->setFromAddress(new TemplatedEmail(), $this->emailFrom, $fromSenderName);

        // To
        $email = $this->setToAddress($email, $recipient->getEmail(), $recipient->getFullName());

        // Reply-To
        $email = $this->addReplyToAddresses($email, $replyTo);

        // Render subject and body in the recipient's language. The LocaleSwitcher makes the
        // recipient locale the translator's current locale, which both the subject translation
        // and the mail-text renderer (read in getParameters()) pick up by default -- no need to
        // push a language onto the legacy environment.
        $effectiveLocale = 'browser' === $recipient->getLanguage() ? $this->defaultLocale : $recipient->getLanguage();
        $email->locale($effectiveLocale);

        $this->localeSwitcher->runWithLocale($effectiveLocale, function () use ($message, $email) {
            $email->subject($this->translator->trans($message->getSubject(), $message->getTranslationParameters(), 'mail'));
            $email->htmlTemplate($message->getTemplateName());
            $email->context($message->getParameters());
        });

        return $email;
    }

    /**
     * Adds the given address as From address to the given email.
     *
     * @param Email  $email The email to be modified
     * @param string $emailAddress The email address of the sender
     * @param string $emailName The sender's name, defaults to an empty string
     *
     * @return Email The modified email object
     */
    private function setFromAddress(Email $email, string $emailAddress, string $emailName = ''): Email
    {
        $address = $this->createAddress($emailAddress, $emailName);
        if ($address) {
            $email->from($address);
        }

        return $email;
    }

    /**
     * Adds the given address as To address to the given email.
     *
     * @param Email  $email The email to be modified
     * @param string $emailAddress The email address of the recipient
     * @param string $emailName The recipient's name, defaults to an empty string
     *
     * @return Email The modified email object
     */
    private function setToAddress(Email $email, string $emailAddress, string $emailName = ''): Email
    {
        $address = $this->createAddress($emailAddress, $emailName);
        if ($address) {
            $email->to($address);
        }

        return $email;
    }

    /**
     * Adds the given addresses as Reply to addresses to the given email.
     *
     * @param Email            $email The email object whose Reply to addresses shall be amended
     * @param Address|string[] $emailAddresses List of Reply to addresses (Address objects or string-based email addresses)
     *
     * @return Email The modified email object
     */
    private function addReplyToAddresses(Email $email, array $emailAddresses): Email
    {
        foreach ($emailAddresses as $emailAddress) {
            $address = $emailAddress instanceof Address ? $emailAddress : $this->createAddress($emailAddress);
            if ($address) {
                $email->addReplyTo($address);
            }
        }

        return $email;
    }

    /**
     * Adds the given addresses as Cc addresses to the given email.
     *
     * @param Email            $email The email object whose Cc addresses shall be amended
     * @param Address|string[] $emailAddresses List of Cc addresses (Address objects or string-based email addresses)
     *
     * @return Email The modified email object
     */
    private function addCcAddresses(Email $email, array $emailAddresses): Email
    {
        foreach ($emailAddresses as $emailAddress) {
            $address = $emailAddress instanceof Address ? $emailAddress : $this->createAddress($emailAddress);
            if ($address) {
                $email->addCc($address);
            }
        }

        return $email;
    }

    /**
     * For an email object's sender or recipient, creates an address object from the given
     * email address and (optional) name.
     *
     * @param string $emailAddress The email address of the sender/recipient
     * @param string $emailName The sender's/recipient's name, defaults to an empty string
     *
     * @return Address|null The newly created address object, or null if email validation failed
     */
    private function createAddress(string $emailAddress, string $emailName = ''): ?Address
    {
        $validator = new EmailValidator();
        if (!$validator->isValid($emailAddress, new RFCValidation())) {
            $logMessage = sprintf('Address cannot be generated due to RFC violation for email address "%s"', $emailAddress);
            $logMessage .= !empty($emailName) ? sprintf(' ("%s").', $emailName) : '.';
            $this->logger->warning($logMessage);

            return null;
        }

        return new Address($emailAddress, $emailName);
    }
}
