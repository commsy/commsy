<?php

namespace CommsyMediawikiBundle\Services;

use Symfony\Component\Form\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediawikiService
{
    protected $wikiApiUrl;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->wikiApiUrl = $this->container->getParameter('commsy.mediawiki.url').$this->container->getParameter('commsy.mediawiki.apiPath');
    }

    public function createWiki($roomId)
    {
        $url = $this->wikiApiUrl.'?action=commsy&function=createwiki&session-id=123&context-id=123&format=json';
        
        $restClient = $this->container->get('circle.restclient');
        
        $json = $restClient->get($url)->getContent();
    }
}