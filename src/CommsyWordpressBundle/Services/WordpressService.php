<?php

namespace CommsyWordpressBundle\Services;

use Symfony\Component\Form\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class WordpressService
{

    protected $legacyEnvironment;
    
    public function __construct(ContainerInterface $container, LegacyEnvironment $legacyEnvironment)
    {
        $this->container = $container;

        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function enableWordpress($roomId)
    {
        $result = false;
        if (!$this->isWordpressEnabled($roomId)) {
            // create blog for room id
        }
        return $result;
    }

    public function disableWordpress($roomId)
    {
        $result = false;
        if ($this->isWordpressEnabled($roomId)) {
            // remove blog for room id
        }
        return $result;
    }

    public function isWordpressEnabled($roomId){
        // ask wordpress if blog for room id exists

        $client = $this->getSoapClient();

        $session = $this->legacyEnvironment->getSessionItem();
        $sessionId = $session->getSessionID();

        $result = $client->existsBlog($sessionId, $roomId);

        return $result;
    }

    private function getSoapClient () {
        $c_proxy_ip = $this->container->getParameter('commsy.settings.proxy_ip');
        $c_proxy_port = $this->container->getParameter('commsy.settings.proxy_port');

        $options = [];
        $options['cache_wsdl'] = 0;
        $options['features'] = 2;
        $options['trace'] = 1;
        if ($c_proxy_ip) {
            $options['proxy_host'] = $c_proxy_ip;
        }
        if ($c_proxy_port) {
            $options['proxy_port'] = $c_proxy_port;
        }
        $retour = null;
        try {
            $retour = new \SoapClient($this->getSoapWsdlUrl(), $options);
        } catch (SoapFault $sf) {
            include_once 'functions/error_functions.php';
            trigger_error('SOAP Error: '.$sf->faultstring, E_USER_ERROR);
        }

        return $retour;
    }

    private function getSoapWsdlUrl()
    {
        $wordpress_path_url = $this->container->getParameter('extensions.wordpress.url');

        return $wordpress_path_url . '/?wsdl';
    }
}