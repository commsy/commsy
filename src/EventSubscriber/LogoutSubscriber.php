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

use App\Entity\Account;
use App\Entity\AuthSourceShibboleth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private HttpUtils $httpUtils,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        
        /** @var Account $account */
        $account = $this->security->getUser();
        if ($account) {
            $authSource = $account->getAuthSource();

            if ($authSource instanceof AuthSourceShibboleth) {
                $logoutUrl = $authSource->getLogoutUrl();
                if ($logoutUrl) {
                    $event->setResponse(new RedirectResponse($logoutUrl));
                }
            } else {
                // Redirect to portal login if we find the id in the session
                $session = $request->getSession();
                if ($session->has('context')) {
                    $context = 99 === $session->get('context') ? 'server' : $session->get('context');
                    $loginUrl = $this->urlGenerator->generate('app_login', [
                        'context' => $context,
                    ]);

                    $event->setResponse($this->httpUtils->createRedirectResponse($request, $loginUrl));
                }
            }
        }

        $event->setResponse($this->httpUtils->createRedirectResponse($request, '/'));
    }
}
