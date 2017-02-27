<?php

namespace CommsyMediawikiBundle\Services;

use Symfony\Component\Form\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class MediawikiService
{
    protected $wikiApiUrl;

    protected $legacyEnvironment;
    
    public function __construct(ContainerInterface $container, LegacyEnvironment $legacyEnvironment)
    {
        $this->container = $container;
        $this->wikiApiUrl = $this->container->getParameter('commsy.mediawiki.url').$this->container->getParameter('commsy.mediawiki.apiPath');

        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function createWiki($roomId)
    {
        $url = $this->wikiApiUrl.'?action=commsy&function=createwiki&session-id='.$this->legacyEnvironment->getEnvironment()->getSessionID().'&context-id='.$roomId.'&format=json';
        
        $restClient = $this->container->get('circle.restclient');
        
        $json = $restClient->get($url)->getContent();
    }

    public function deleteWiki($roomId)
    {
        $url = $this->wikiApiUrl.'?action=commsy&function=deletewiki&session-id='.$this->legacyEnvironment->getEnvironment()->getSessionID().'&context-id='.$roomId.'&format=json';

        $restClient = $this->container->get('circle.restclient');

        $json = $restClient->get($url)->getContent();
    }
}