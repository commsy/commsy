<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['updateActivity', 0],
            ],
        ];
    }

    public function updateActivity(ControllerEvent $event)
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