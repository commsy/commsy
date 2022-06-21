<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use App\Utils\ItemService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    private $legacyEnvironment;

    private $itemService;

    private $urlGenerator;

    /**
     * @param ItemService $itemService
     * @param UrlGeneratorInterface $urlGenerator
     * @param LegacyEnvironment $legacyEnvironment
     */
    public function __construct(
        ItemService $itemService,
        UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->itemService = $itemService;
        $this->urlGenerator = $urlGenerator;
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
        );
    }

    /**
     * Catches all legacy requests and hands them over to legacy kernel
     *
     * @param RequestEvent $event
     * @throws Exception
     */
    public function onKernelRequest(RequestEvent $event)
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
                $cid = $currentRequest->query->get('cid');
                $contextItem = $this->itemService->getTypedItem($cid);
                if ($contextItem instanceof \cs_room_item) {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_room_home', [
                        'roomId' => $cid,
                    ])));
                }
            }
        }

         // set user language
        $currentRequest = $event->getRequest();
        $currentRequest->setLocale($this->legacyEnvironment->getSelectedLanguage());
    }
}