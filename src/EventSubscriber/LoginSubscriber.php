<?php

namespace App\EventSubscriber;

use App\Account\AccountManager;
use App\Entity\Account;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

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

    private AccountManager $accountManager;

    public function __construct(
        Security $security,
        UrlGeneratorInterface $urlGenerator,
        AccountManager $accountManager
    ) {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->accountManager = $accountManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            return;
        }

        /**
         * Make sure that there is a valid user object and as a result an authentication token in the token storage.
         * If missing, ->isGranted() will cause development-only urls like _wdt/ to
         * throw an AuthenticationCredentialsNotFoundException
         */
        if (
            $event->getRequest()->attributes->get('_route') === 'app_migration_password' ||
            $this->security->isGranted('ROLE_PREVIOUS_ADMIN')
        ) {
            return;
        }

        if ($account->hasLegacyPassword()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_migration_password')));
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Account $account */
        $account = $this->security->getUser();

        if ($account) {
            $this->accountManager->resetInactivity($account);
        }
    }
}
