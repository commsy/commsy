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
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

readonly class SecuritySubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RouterInterface $router,
        private Security $security,
        private RequestContext $requestContext
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
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

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ('app_security_simultaneouslogin' === $request->attributes->get('_route')) {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();
        if (!$account instanceof Account || 'root' === $account->getUsername()) {
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
            LoginSuccessEvent::class => 'onLoginSuccess',
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
