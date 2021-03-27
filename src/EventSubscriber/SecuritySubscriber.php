<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecuritySubscriber implements EventSubscriberInterface
{
    private $legacyEnvironment;

    private $router;

    public function __construct(LegacyEnvironment $legacyEnvironment, RouterInterface $router)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->router = $router;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($event->getRequest()->attributes->get('_route') === 'app_profile_personal') {
            return;
        }

        $environment = $this->legacyEnvironment->getEnvironment();

        $currentUser = $environment->getCurrentUserItem();
        $portalUser = $currentUser->getRelatedPortalUserItem();

        $privateRoom = $currentUser->getOwnRoom();
        $privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();

        if ($privateRoom && $privateRoomUser) {
            if ($portalUser) {
                if ($portalUser->hasToChangeEmail()) {
                    // generate route to profile
                    $route = $this->router->generate('app_profile_personal', [
                        'portalId' => $portalUser->getContextID(),
                    ]);

                    // redirect user to account mail settings
                    $event->setResponse(new RedirectResponse($route));
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'security.interactive_login' => 'onSecurityInteractiveLogin',
        ];
    }
}
