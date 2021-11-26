<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class LoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var Security 
     */
    private Security $security;

    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') === 'app_migration_password') {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();

        if (!$account instanceof Account) {
            return;
        }

        if ($account->hasLegacyPassword()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_migration_password')));
        }
    }
}
