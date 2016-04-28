<?php
namespace EtherpadBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
           'kernel.view' => array(
               array('materialEdit', 0),
           )
        );
    }

    public function materialEdit(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (array_key_exists('isMaterial', $result)) {
            // get etherpad configuration
            $enabled = $this->container->getParameter('commsy.etherpad.enabled');

            if ($result['isMaterial'] && $enabled) {
                $result['useEtherpad'] = true;
                $event->setControllerResult($result);
            }
        }
        
        
    }

}