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
        error_log(print_r('create wiki', true));
        
        /* $url = $this->wikiApiUrl.'?action=commsy&function=createwiki&session-id=123&context-id=123&format=json';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        // If using JSON...
        $data = json_decode($response);
        
        error_log(print_r($url, true));
        error_log(print_r($response, true));
        error_log(print_r($data, true)); */
        
        $url = $this->wikiApiUrl.'?action=commsy&function=createwiki&session-id=123&context-id=123&format=json';
        
        $restClient = $this->container->get('circle.restclient');
        
        $json = $restClient->get($url)->getContent();
        
        error_log(print_r($json, true));
    }
}