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

        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function enableWordpress($roomId)
    {
        $result = false;
        if (!$this->isWordpressEnabled($roomId)) {

        }
        return $result;
    }

    public function disableWordpress($roomId)
    {
        $result = false;
        if ($this->isWordpressEnabled($roomId)) {

        }
        return $result;
    }

    public function isWordpressEnabled($roomId){

        return false;
    }
}