<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 18:36
 */

namespace App\Mail;


use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Psr\Log\LoggerInterface;
use Swift_RfcComplianceException;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageBuilder
{
    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var EngineInterface $templating
     */
    private EngineInterface $templating;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var string $emailFrom
     */
    private string $emailFrom;

    public function __construct(
        TranslatorInterface $translator,
        EngineInterface $templating,
        LoggerInterface $logger,
        string $emailFrom
    ) {
        $this->translator = $translator;
        $this->templating = $templating;
        $this->logger = $logger;
        $this->emailFrom = $emailFrom;
    }

    /**
     * @param MessageInterface $message
     * @param string $fromSenderName
     * @param Recipient $recipient The recipient
     * @param array $replyTo Reply to in the form of email => name
     * @return \Swift_Message|null
     *
     * @†hrows \Exception
     */
    public function generateSwiftMessage(
        MessageInterface $message,
        string $fromSenderName,
        Recipient $recipient,
        array $replyTo = []
    ): ?\Swift_Message {
        $subject = $this->translator->trans($message->getSubject(), $message->getTranslationParameters(), 'mail');
        $swiftMessage = new \Swift_Message();

        try {
            // Validation
            array_walk($replyTo, function ($name, $email) {
                $this->validateEmail($email);
            });
            $this->validateEmail($this->emailFrom);

            $locale = $this->translator->getLocale();
            $this->translator->setLocale($recipient->getLanguage());

            $swiftMessage
                ->setSubject($subject)
                ->setFrom([$this->emailFrom => $fromSenderName])
                ->setReplyTo($replyTo)
                ->setTo([$recipient->getEmail() => $recipient->getFirstname() . ' ' . $recipient->getLastname()])
                ->setBody($this->templating->render($message->getTemplateName(), $message->getParameters()), 'text/html')
            ;

            // Restore the previous locale
            $this->translator->setLocale($locale);
        } catch (Swift_RfcComplianceException $e) {
            $this->logger->warning('Swift Message cannot be generated, RFC violation.', [$e->getMessage()]);
            return null;
        }

        return $swiftMessage;
    }

    /**
     * @param string $email The email address to validate
     * @throws Swift_RfcComplianceException
     */
    private function validateEmail(string $email)
    {
        $validator = new EmailValidator();
        if (!$validator->isValid($email, new RFCValidation())) {
            throw new Swift_RfcComplianceException('Invalid email given: ' .  $email);
        }
    }
}