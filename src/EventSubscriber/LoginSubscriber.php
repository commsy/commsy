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

namespace App\EventSubscriber;

use App\Account\AccountManager;
use App\Entity\Account;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

readonly class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private AccountManager $accountManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }

        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            return;
        }

        /*
         * Make sure that there is a valid user object and as a result an authentication token in the token storage.
         * If missing, ->isGranted() will cause development-only urls like _wdt/ to
         * throw an AuthenticationCredentialsNotFoundException
         */
        if (
            'app_migration_password' === $event->getRequest()->attributes->get('_route') ||
            $this->security->isGranted('IS_IMPERSONATOR')
        ) {
            return;
        }

        if ($account->hasLegacyPassword()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_migration_password')));
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var Account $account */
        $account = $event->getUser();

        if ($account instanceof Account) {
            $this->accountManager->resetInactivity($account);
        }
    }
}
