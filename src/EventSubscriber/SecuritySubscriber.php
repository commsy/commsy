<?php

namespace App\EventSubscriber;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\PortalGuessService;
use cs_environment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecuritySubscriber implements EventSubscriberInterface
{
    /**
     * @var cs_environment|LegacyEnvironment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var PortalGuessService
     */
    private PortalGuessService $portalGuessService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RouterInterface $router,
        Security $security,
        PortalGuessService $portalGuessService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
        $this->security = $security;
        $this->portalGuessService = $portalGuessService;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($event->getRequest()->attributes->get('_route') === 'app_account_personal') {
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

        if ($request->attributes->get('_route') === 'app_security_simultaneouslogin') {
            return;
        }

        /** @var Account $account */
        $account = $this->security->getUser();

        if ($account === null || $account->getUsername() === 'root') {
            return;
        }

        $portal = $this->portalGuessService->fetchPortal($request);
        if ($portal === null) {
            return;
        }

        if ($account->getContextId() !== $portal->getId()) {
            $event->setResponse(new RedirectResponse($this->router->generate('app_security_simultaneouslogin', [
                'portalId' => $portal->getId(),
            ])));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'security.interactive_login' => 'onSecurityInteractiveLogin',
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
