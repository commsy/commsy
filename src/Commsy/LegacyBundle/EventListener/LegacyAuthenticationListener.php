<?php

namespace Commsy\LegacyBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Commsy\LegacyBundle\Authentication\LegacyAuthentication;

/**
 * Class LegacyAuthenticationListener
 *
 * @package EventListener;
 */
class LegacyAuthenticationListener implements EventSubscriberInterface
{
    private $legacyAuthentication;

    public function __construct(LegacyAuthentication $legacyAuthentication)
    {
        $this->legacyAuthentication = $legacyAuthentication;
    }

    /**
     * {@inheritDocs}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 400),
        );
    }

    /**
     * @param  GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // // the legacy kernel only deals with master requests
        // if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
        //     return;
        // }
        $this->legacyAuthentication->authenticate();
    }
}