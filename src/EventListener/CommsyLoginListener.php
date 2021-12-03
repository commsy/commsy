<?php

namespace App\EventListener;

use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class CommsyLoginListener
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function onKernelRequest(RequestEvent $event)
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
            $user = $this->legacyEnvironment->getCurrentUser();
            if ($user->isUser() and !$user->isRoot()) {
                $user->updateLastLogin();

                // The portal user is no longer updated here
                // This is now done by the LoginSubscriber and stored in the Account
            }
        }
    }
}