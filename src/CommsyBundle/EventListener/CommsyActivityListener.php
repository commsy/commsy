<?php

namespace CommsyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Liip\ThemeBundle\ActiveTheme;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CommsyActivityListener
{
    private $roomService;

    private $legacyEnvironment;

    public function __construct(RoomService $roomService, LegacyEnvironment $legacyEnvironment)
    {
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            if (!$request->isXmlHttpRequest()) {

                $countRequest = true;
                if (preg_match('~\/room\/(\d)+\/user\/(\d)+\/image~', $request->getUri())) {
                    $countRequest = false;
                } else if (preg_match('~\/room\/(\d)+\/theme\/background~', $request->getUri())) {
                    $countRequest = false;
                } else if (preg_match('~\/room\/(\d)+\/logo~', $request->getUri())) {
                    $countRequest = false;
                }

                if ($countRequest)
                    $environment = $this->legacyEnvironment->getEnvironment();

                $activity_points = 1;
                $context_item_current = $environment->getCurrentContextItem();
                if (isset($context_item_current)) {
                    $context_item_current->saveActivityPoints($activity_points);
                    if ($context_item_current->isProjectRoom()
                        or $context_item_current->isCommunityRoom()
                        or $environment->inPrivateRoom()
                        or $environment->inGroupRoom()
                    ) {

                        // archiving
                        $context_item_current->saveLastLogin();

                        $current_portal_item = $environment->getCurrentPortalItem();
                        if (isset($current_portal_item)) {
                            $current_portal_item->saveActivityPoints($activity_points);
                            unset($current_portal_item);
                        }
                    }
                }
            }
        }
    }
}