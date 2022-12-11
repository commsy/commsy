<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Services;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class LegacyEnvironment
{
    /**
     * @var \cs_environment|null
     */
    private $environment;

    /**
     * @param string $projectDir
     */
    public function __construct(
        private $projectDir,
        /**
         * Symfony service container.
         */
        private Container $serviceContainer,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack
    ) {
    }

    public function getEnvironment(): \cs_environment
    {
        if (null === $this->environment) {
            $legacyDir = $this->projectDir.'/legacy';
            set_include_path(get_include_path().PATH_SEPARATOR.$legacyDir);

            global $cs_color;
            global $db;
            include_once 'etc/cs_constants.php';
            include_once 'functions/misc_functions.php';

            global $symfonyContainer;
            $symfonyContainer = $this->serviceContainer;

            include_once 'classes/cs_environment.php';
            global $environment;
            $environment = new \cs_environment();
            $this->environment = $environment;

            // try to find the current room id from the request and set context in legacy environment
            $contextId = $this->guessContextId();
            $this->environment->setCurrentContextID($contextId);
        }

        return $this->environment;
    }

    /**
     * This method tries to guess the current context id by analysing the client request.
     * If no context id could be found, we will fall back to 99 (the "server context").
     *
     * @return int context id
     */
    private function guessContextId()
    {
        $requestStack = $this->requestStack;
        $currentRequest = $requestStack->getCurrentRequest();

        // current request could be empty
        if ($currentRequest) {
            // check attributes
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                return $attributes->get('roomId');
            }

            // check request uri
            $requestUri = $currentRequest->getRequestUri();

            if (preg_match('/(room|dashboard|portal)\/(\d+)/', $requestUri, $matches)) {
                $roomId = $matches[2];

                return $roomId;
            }
        }

        return 99;
    }
}
