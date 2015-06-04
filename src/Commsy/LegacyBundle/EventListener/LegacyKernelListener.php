<?php

namespace Commsy\LegacyBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class LegacyKernelListener
 *
 * Listens to kernel request events and hands execution to the legacy kernel.
 * This allows to write new parts of the system using Symfony and every old route found
 * will be handled by the legacy application.
 *
 * @package EventListener;
 */
class LegacyKernelListener implements EventSubscriberInterface
{
    /**
     * The legacy kernel
     * @var HttpKernelInterface
     */
    private $legacyKernel;

    /**
     * @param HttpKernelInterface $legacyKernel
     */
    public function __construct(HttpKernelInterface $legacyKernel)
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * {@inheritDocs}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 512),
        );
    }

    /**
     * Catches all legacy requests and hands them over to legacy kernel
     * 
     * @param  GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // the legacy kernel only deals with master requests
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        // Let the wrapped legacy kernel handle the legacy request.
        // Setting a response in the event will directly jump to the response event.
        $request = $event->getRequest();
        if ($request->query->has('cid')) {
            $response = $this->legacyKernel->handle($request);

            $event->setResponse($response);
        }
    }
}