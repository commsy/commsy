<?php

namespace EtherpadBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;


class EtherpadService
{
    private $client;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

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

    
}