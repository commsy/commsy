<?php

namespace Commsy\LegacyBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class LegacyEnvironment
{
    private $environment;

    /**
     * Path to the legacy application "commsy_legacy.php front controller"
     * @var String
     */
    private $legacyAppPath;

    /**
     * Symfony service container
     */
    private $serviceContainer;

    /**
     * @param String             $legacyAppPath
     */
    public function __construct($legacyAppPath, Container $container)
    {
        $this->legacyAppPath = $legacyAppPath;
        $this->serviceContainer = $container;
    }

    public function getEnvironment()
    {
        if ($this->environment === null) {
            $legacyDir = dirname($this->legacyAppPath) . '/../legacy';
            set_include_path(get_include_path() . PATH_SEPARATOR . $legacyDir);

            global $cs_color;
            global $db;
            include_once('etc/cs_constants.php');
            include_once('etc/cs_config.php');
            include_once('functions/misc_functions.php');

            global $symfonyContainer;
            $symfonyContainer = $this->serviceContainer;

            include_once('classes/cs_environment.php');
            global $environment;
            $environment = new \cs_environment();
            $this->environment = $environment;

            // session
            global $session;

            if (isset($_COOKIE['SID'])) {
                $sessionId = $_COOKIE['SID'];
                $sessionManager = $this->environment->getSessionManager();
                $session = $sessionManager->get($sessionId);
            }

            // try to find the current room id from the request and set context in legacy environment
            $requestStack = $this->serviceContainer->get('request_stack');
            $currentRequest = $requestStack->getCurrentRequest();
            if ($currentRequest) {
                // check attributes
                $attributes = $currentRequest->attributes;
                if ($attributes->has('roomId')) {
                    $this->environment->setCurrentContextID($attributes->get('roomId'));
                } else {
                    // check server bag
                    $requestUri = $currentRequest->getRequestUri();
                    
                    if (preg_match('/room\/(\d+)/', $requestUri, $matches)) {
                        $roomId = $matches[1];
                        $this->environment->setCurrentContextID($roomId);
                    }
                }
            }
        }

        return $this->environment;
    }
}