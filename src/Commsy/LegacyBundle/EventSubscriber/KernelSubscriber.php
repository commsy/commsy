<?php

namespace Commsy\LegacyBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Commsy\LegacyBundle\Authentication\LegacyAuthentication;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

/**
 * Class KernelSubscriber
 *
 * Listens to kernel request events and hands execution to the legacy kernel.
 * This allows to write new parts of the system using Symfony and every old route found
 * will be handled by the legacy application.
 *
 * @package EventSubscriber;
 */
class KernelSubscriber implements EventSubscriberInterface
{
    /**
     * The legacy kernel
     * @var HttpKernelInterface
     */
    private $legacyKernel;

    private $legacyEnvironment;

    private $legacyAuthentication;

    /**
     * @param HttpKernelInterface $legacyKernel
     */
    public function __construct(HttpKernelInterface $legacyKernel, LegacyAuthentication $legacyAuthentication,LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyKernel = $legacyKernel;
        $this->legacyAuthentication = $legacyAuthentication;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * {@inheritDocs}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => [
                'onKernelRequest',
                512,
            ],
            KernelEvents::FINISH_REQUEST => [
                'onKernelRequestFinished',
                100,
            ],
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
        $currentRequest = $event->getRequest();
        if ($currentRequest->query->has('cid')) {
            $pathInfo = $currentRequest->getPathInfo();
            if (strlen($pathInfo) > 1) {
                $url = $currentRequest->getSchemeAndHttpHost() . '?cid=' . $currentRequest->query->get('cid');
                $response = new RedirectResponse($url);
                $event->setResponse($response);
            } else {
                $response = $this->legacyKernel->handle($currentRequest);

                $event->setResponse($response);
            }
        } else {
            // some services will handle authentication themselves or can bypass, like soap, rss, ...
            $currentRequest = $event->getRequest();
            $requestUri = $currentRequest->getRequestUri();

            if (preg_match('/(soap|rss|_profiler|_wdt|room\/\d+\/user\/\d+\/image)/', $requestUri, $matches)) {
                $isAuthenticated = true;
            } else {
                $isAuthenticated = $this->legacyAuthentication->authenticate();
            }

            // if not authenticated by the legacy code, redirect back to portal
            if (!$isAuthenticated) {
                // check if we currently have a portal item (not in server context)
                $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

                if ($portalItem) {
                    $sessionManager = $this->legacyEnvironment->getSessionManager();

                    $userSessionItem = $this->legacyEnvironment->getSessionItem();
                    if (!$userSessionItem) {
                        // if we have no session yet, create one
                        require_once('classes/cs_session_item.php');
                        $userSessionItem = new \cs_session_item();
                        $userSessionItem->createSessionID('guest');
                        $userSessionItem->setValue('commsy_id', $portalItem->getItemID());
                    }

                    // persist the requested url in session, so we can redirect the user after login
                    $userSessionItem->setValue('login_redirect', $requestUri);

                    $sessionManager->save($userSessionItem);

                    $url = $event->getRequest()->getBaseUrl() . '?cid=' . $portalItem->getItemID();

                    // if this is a room url, send us to room detail view on portal
                    if (preg_match('/room\/([0-9]+)/', $requestUri, $roomIdMatch)) {
                        $url .= '&mod=home&fct=index&room_id=' . $roomIdMatch[1];
                    }

                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
            }
        }
         // set user language
        $currentRequest = $event->getRequest();
        $currentRequest->setLocale($this->legacyEnvironment->getSelectedLanguage());
    }

    public function onKernelRequestFinished(FinishRequestEvent $event) {
        // only deal with master requests
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        $session = $this->legacyEnvironment->getSessionItem();

        if ($session) {
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionManager->update($session);
        }
    }
}