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
use App\Services\LegacyEnvironment;
use App\Utils\RequestContext;
use cs_environment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecuritySubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly RequestContext $requestContext
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ('app_account_personal' === $event->getRequest()->attributes->get('_route')) {
            return;
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $portalUser = $currentUser->getRelatedPortalUserItem();

        $privateRoom = $currentUser->getOwnRoom();
        $privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();

        if ($privateRoom && $privateRoomUser) {
            if ($portalUser) {
                if ($portalUser->hasToChangeEmail()) {
                    // generate route to profile
                    $route = $this->router->generate('app_account_personal', [
                        'portalId' => $portalUser->getContextID(),
                    ]);

                    // redirect user to account mail settings
                    $event->setResponse(new RedirectResponse($route));
                }
            }
        }
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ('app_security_simultaneouslogin' === $request->attributes->get('_route')) {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();

        if (null === $account || 'root' === $account->getUsername()) {
            return;
        }

        $portal = $this->requestContext->fetchPortal($request);
        if (null === $portal) {
            return;
        }

        if ($account->getContextId() !== $portal->getId()) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_security_simultaneouslogin', [
                'portalId' => $portal->getId(),
            ])));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
