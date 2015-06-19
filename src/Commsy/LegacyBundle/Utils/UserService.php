<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class UserService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getUser($userId)
    {
        $userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
        $user = $userManager->getItem($userId);
        return $user;
    }
}