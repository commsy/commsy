<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Crypto\SMimeSigner;
use Symfony\Component\Mime\Email;

class MailerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // From the mailer documentation
            // Signing and encrypting messages require their contents to be fully rendered. For example,
            // the content of templated emails is rendered by a MessageListener. So, if you want to sign and/or
            // encrypt such a message, you need to do it in a MessageEvent listener run after it (you need to set
            // a negative priority to your listener).
            MessageEvent::class => ['signMessage', -10]
        ];
    }

    public function signMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        if (!$message instanceof Email) {
            return;
        }

        $cert = $this->parameterBag->get('commsy.email.smime.cert');
        $key = $this->parameterBag->get('commsy.email.smime.key');

        if (!empty($cert) && !empty($key)) {
            if (file_exists($cert) && file_exists($key)) {
                $signer = new SMimeSigner($cert, $key);
                $signedMail = $signer->sign($message);

                $event->setMessage($signedMail);
            }
        }
    }
}
