<?php

namespace CommsyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Liip\ThemeBundle\ActiveTheme;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CommsyLoginListener
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        /*
           restrict logging to the following requests:
           "/room/<id>"
           "/room/<id>/<rubric>"
           "/room/<id>/<rubric>/<id>"
           "/dashboard/<id>"
        */
        $request = $event->getRequest();
        $logRequest = false;
        if (preg_match('~\/room\/(\d)+$~', $request->getUri())) {
            $logRequest = true;
        } else if (preg_match('~\/room\/(\d)+\/([a-z])+$~', $request->getUri())) {
            $logRequest = true;
        } else if (preg_match('~\/room\/(\d)+\/([a-z])+\/(\d)+$~', $request->getUri())) {
            $logRequest = true;
        } else if (preg_match('~\/dashboard\/(\d)+$~', $request->getUri())) {
            $logRequest = true;
        }
        
        if ($logRequest) {
            $user = $this->legacyEnvironment->getEnvironment()->getCurrentUser();
            if ($user->isUser() and !$user->isRoot()) {
                $user->updateLastLogin();

                $portalUser = $user->getRelatedCommSyUserItem();
                if ($portalUser) {
                    $portalUser->updateLastLogin();

                    if ($portalUser->getMailSendNextLock() || $portalUser->getMailSendBeforeLock() || $portalUser->getNotifyLockDate()) {
                        $portalUser->resetInactivity();
                    }
                }
            }
        }
    }
}