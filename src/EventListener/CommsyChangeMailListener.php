<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class CommsyChangeMailListener
{

    private $legacyEnvironment;

    private $router;

    public function __construct(LegacyEnvironment $legacyEnvironment, Router $router)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            if (!$request->isXmlHttpRequest()) {
                if (!preg_match('~\/room\/(\d)+\/user\/(\d)+\/personal~', $request->getUri())) {

                    $environment = $this->legacyEnvironment->getEnvironment();

                    $currentUser = $environment->getCurrentUserItem();
                    $portalUser = $currentUser->getRelatedPortalUserItem();

                    $privateRoom = $currentUser->getOwnRoom();
                    $privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();

                    if ($privateRoom && $privateRoomUser) {
                        if ($portalUser) {
                            if ($portalUser->hasToChangeEmail()) {
                                // generate route to profile
                                $route = $this->router->generate('commsy_profile_personal', [
                                    'roomId' => $privateRoom->getItemId(),
                                    'itemId' => $privateRoomUser->getItemId()
                                ]);

                                // redirect user to account mail settings
                                $event->setResponse(new RedirectResponse($route));
                            }
                        }
                    }
                }
            }
        }
    }
}