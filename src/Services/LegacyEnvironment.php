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

use cs_environment;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class LegacyEnvironment
{
    public function __construct(
        private string $projectDir,
        private RequestStack $requestStack,
        private Container $serviceContainer,
        private cs_environment $csEnvironment
    ) {
        $legacyDir = $this->projectDir.'/legacy';
        set_include_path(get_include_path().PATH_SEPARATOR.$legacyDir);

        include_once 'etc/cs_constants.php';
        include_once 'functions/misc_functions.php';
        include_once 'classes/cs_environment.php';

        global $symfonyContainer;
        $symfonyContainer = $this->serviceContainer;

        // try to find the current room id from the request and set context in legacy environment
        $contextId = $this->guessContextId();
        $this->csEnvironment->setCurrentContextID($contextId);

        global $environment;
        $environment = $this->csEnvironment;
    }

    public function getEnvironment(): cs_environment
    {
        return $this->csEnvironment;
    }

    /**
     * This method tries to guess the current context id by analysing the client request.
     * If no context id could be found, we will fall back to 99 (the "server context").
     */
    private function guessContextId(): int
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

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
