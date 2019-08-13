<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TermsOfUseSubscriber implements EventSubscriberInterface
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(LegacyEnvironment $legacyEnvironment, UrlGeneratorInterface $urlGenerator)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Return early if this is not a master request
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // First check for potal tou
        $portal = $this->legacyEnvironment->getCurrentPortalItem(); // Will return null on server
        if ($portal && $portal->withAGB()) {
            $portalUser = $this->legacyEnvironment->getCurrentUserItem()->getRelatedPortalUserItem();

            $portalToUDate = new \DateTime($portal->getAGBChangeDate());
            $userAcceptedData = new \DateTime($portalUser->getAGBAcceptanceDate());

            if (!$portalUser->isRoot() && $userAcceptedData < $portalToUDate) {
                // Redirect to tou site
                if ($event->getRequest()->attributes->get('_route') !== 'app_tou_accept' &&
                    $event->getRequest()->attributes->get('_route') !== 'app_profile_deleteaccount' &&
                    $event->getRequest()->attributes->get('_route') !== 'app_logout_logout') {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_tou_accept', [
                        'roomId' => $portal->getItemID(),
                        'redirect' => $event->getRequest()->getRequestUri(),
                    ])));
                }
            }
        }

        $currentContext = $this->legacyEnvironment->getCurrentContextItem();
        if ($currentContext->isProjectRoom() || $currentContext->isCommunityRoom() ||$currentContext->isGroupRoom()) {
            if ($currentContext->withAGB()) {
                $contextUser = $this->legacyEnvironment->getCurrentUserItem();

                $contextToUDate = new \DateTime($currentContext->getAGBChangeDate());
                $userAcceptedData = new \DateTime($contextUser->getAGBAcceptanceDate());

                if (!$contextUser->isRoot() && $userAcceptedData < $contextToUDate) {
                    // Redirect to tou site
                    if ($event->getRequest()->attributes->get('_route') !== 'app_tou_accept' &&
                        $event->getRequest()->attributes->get('_route') !== 'app_profile_deleteroomprofile' &&
                        $event->getRequest()->attributes->get('_route') !== 'app_logout_logout') {
                        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_tou_accept', [
                            'roomId' => $currentContext->getItemID(),
                            'redirect' => $event->getRequest()->getRequestUri(),
                        ])));
                    }
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
           'kernel.request' => 'onKernelRequest',
        ];
    }
}
