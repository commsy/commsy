<?php

namespace App\Services;

use EtherpadLite\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class EtherpadService
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var Client
     */
    private $client;

    public function __construct(ParameterBagInterface $params)
    {
        $this->baseUrl = $params->get('commsy.etherpad.base_url');

        // get configuration params
        $apiKey = $params->get('commsy.etherpad.api_key');
        $apiUrl = $params->get('commsy.etherpad.api_url');

        // init etherpad client
        if ($apiKey !== '' && $apiUrl !== '') {
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