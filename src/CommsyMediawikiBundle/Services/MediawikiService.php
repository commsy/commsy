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

    public function enableWiki($roomId)
    {
        $result = false;
        if (!$this->isWikiEnabled($roomId)) {
            $url = $this->wikiApiUrl . '?action=commsy&function=enablewiki&session-id=' . $this->legacyEnvironment->getEnvironment()->getSessionID() . '&context-id=' . $roomId . '&format=json';
            $restClient = $this->container->get('circle.restclient');
            $json = json_decode($restClient->get($url)->getContent());
            if (!isset($json->commsy->error)) {
                if ($json->commsy->result == 'wiki enabled') {
                    $result = true;
                }
            }
        }
        return $result;
    }

    public function disableWiki($roomId)
    {
        $result = false;
        if ($this->isWikiEnabled($roomId)) {
            $url = $this->wikiApiUrl . '?action=commsy&function=disablewiki&session-id=' . $this->legacyEnvironment->getEnvironment()->getSessionID() . '&context-id=' . $roomId . '&format=json';
            $restClient = $this->container->get('circle.restclient');
            $json = json_decode($restClient->get($url)->getContent());
            if (!isset($json->commsy->error)) {
                if ($json->commsy->result == 'wiki enabled') {
                    $result = true;
                }
            }
        }
        return $result;
    }

    public function isWikiEnabled($roomId){
        $url = $this->wikiApiUrl; //.'?action=commsy&function=iswikienabled&session-id='.$this->legacyEnvironment->getEnvironment()->getSessionID().'&context-id='.$roomId.'&format=json';
        $restClient = $this->container->get('circle.restclient');

        //$response = $restClient->get($url);
        //$response = file_get_contents($url);

        $json = json_decode($restClient->get($url)->getContent());
        if (!isset($json->commsy->error)) {
            if ($json->commsy->result == 'wiki is enabled') {
                return true;
            }
        }
        return false;
    }
}