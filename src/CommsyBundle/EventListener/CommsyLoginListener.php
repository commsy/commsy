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
        $user = $this->legacyEnvironment->getEnvironment()->getCurrentUser();
        if ($user->isUser() and !$user->isRoot()) {
            $user->updateLastLogin();
        }
    }
}