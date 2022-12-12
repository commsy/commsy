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

use EtherpadLite\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UnitEnum;

class EtherpadService
{
    private bool|string|int|float|UnitEnum|array|null $baseUrl = null;

    private ?Client $client = null;

    public function __construct(ParameterBagInterface $params)
    {
        $this->baseUrl = $params->get('commsy.etherpad.base_url');

        // get configuration params
        $apiKey = $params->get('commsy.etherpad.api_key');
        $apiUrl = $params->get('commsy.etherpad.api_url');

        // init etherpad client
        if ('' !== $apiKey && '' !== $apiUrl) {
            $this->client = new Client($apiKey, $apiUrl);
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
