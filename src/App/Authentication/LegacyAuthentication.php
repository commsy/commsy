<?php

namespace App\Authentication;

use App\Services\LegacyEnvironment;

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
        if (isset($_COOKIE['SID'])) {
            $sid = $_COOKIE['SID'];
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionItem = $sessionManager->get($sid);

            if ($sessionItem) {
                // authenticate the legacy way, this will also setup the current user item
                $authentication = $this->legacyEnvironment->getAuthenticationObject();
                // $authentication->setModule($current_module);
                // $authentication->setFunction($current_function);
                
                return $authentication->check(
                    $sessionItem->getValue('user_id'),
                    $sessionItem->getValue('auth_source')
                );
            }
        }

        return false;
    }
}