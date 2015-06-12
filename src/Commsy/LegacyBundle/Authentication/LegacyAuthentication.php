<?php

namespace Commsy\LegacyBundle\Authentication;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

/**
 * Class LegacyAuthentication
 */
class LegacyAuthentication
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * TODO: this is also executed in legacy context
     */
    public function authenticate()
    {
        // get the legacy session item
        $sid = $_COOKIE['SID'];
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionItem = $sessionManager->get($sid);

        if ($sessionItem) {
            // authenticate the legacy way, this will also setup the current user item
            $authentication = $this->legacyEnvironment->getAuthenticationObject();
            // $authentication->setModule($current_module);
            // $authentication->setFunction($current_function);
            
            $isAuthenticated = $authentication->check(
                $sessionItem->getValue('user_id'),
                $sessionItem->getValue('auth_source')
            );

            // TODO: send back to portal if not authenticated
        }
    }
}