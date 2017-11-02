<?php

namespace CommsyBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class PrintService
{    
    private $legacyEnvironment;
    
    private $serviceContainer;
    
    private $requestStack;
    
    public function __construct(LegacyEnvironment $legacyEnvironment, Container $container, RequestStack $requestStack)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        
        $this->serviceContainer = $container;
        
        $this->requestStack = $requestStack;
    }

    public function printDetail($html, $debug = false)
    {
        $debug = $this->requestStack->getCurrentRequest()->get('debug');
        
        $this->setOptions();

        if (!$debug) {
            return new Response(
                $this->serviceContainer->get('knp_snappy.pdf')->getOutputFromHtml($html),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="print.pdf"'
                ]
            );
        } else {
            return new Response($html);
        }
    }
    
    public function printList($html, $debug = false)
    {
        $debug = $this->requestStack->getCurrentRequest()->get('debug');
        
        $this->setOptions();

        if (!$debug) {
            return new Response(
                $this->serviceContainer->get('knp_snappy.pdf')->getOutputFromHtml($html),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="print.pdf"',
                ]
            );
        } else {
            return new Response($html);
        }
    }
    
    function setOptions() {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();
        
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('footer-line',true);
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('footer-spacing', 1);
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('footer-center',"[page] / [toPage]");
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('header-line', true);
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('header-spacing', 1 );
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('header-right', date("d.m.y"));
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('header-left', $roomItem->getTitle());
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('header-center', "Commsy");
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('images',true);
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('load-media-error-handling','ignore');
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('load-error-handling','ignore');

        if ($this->serviceContainer->hasParameter('commsy.settings.proxy_ip')) {
            if ($this->serviceContainer->hasParameter('commsy.settings.proxy_port')) {
                $proxyIp = $this->serviceContainer->getParameter('commsy.settings.proxy_ip');
                $proxyPort = $this->serviceContainer->getParameter('commsy.settings.proxy_port');
                $proxy = 'http://' . $proxyIp . ':' . $proxyPort;

                $this->serviceContainer->get('knp_snappy.pdf')->setOption('proxy', $proxy);
            }
        }
        
        // set cookie for authentication - needed to request images
        $this->serviceContainer->get('knp_snappy.pdf')->setOption('cookie', [
            'SID' => $this->legacyEnvironment->getSessionID(),
        ]);
    }
}