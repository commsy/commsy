<?php

namespace App\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;


class EtherpadService
{
    private $client;

    private $container;

    private $baseUrl;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->baseUrl = $this->container->getParameter('commsy.etherpad.base_url');

        // get configuration params
        $apiKey = $this->container->getParameter('commsy.etherpad.api_key');
        $apiUrl = $this->container->getParameter('commsy.etherpad.api_url');

        // init etherpad client
        $this->client = new \EtherpadLite\Client($apiKey, $apiUrl);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    
}