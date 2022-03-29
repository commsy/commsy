<?php

namespace App\EventListener;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class CommsyActivityListener
{
    private $roomService;

    private $legacyEnvironment;

    private $entityManager;

    public function __construct(
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager
    ) {
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->entityManager = $entityManager;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            if (!$request->isXmlHttpRequest()) {

                $countRequest = true;
                if (preg_match('~\/room\/(\d)+\/user\/(\d)+\/image~', $request->getUri())) {
                    $countRequest = false;
                } else if (preg_match('~\/room\/(\d)+\/theme\/background~', $request->getUri())) {
                    $countRequest = false;
                } else if (preg_match('~\/room\/(\d)+\/logo~', $request->getUri())) {
                    $countRequest = false;
                }

                if ($countRequest) {
                    $environment = $this->legacyEnvironment->getEnvironment();
                    $currentContextItem = $environment->getCurrentContextItem();

                    if ($currentContextItem) {
                        if ($currentContextItem->isPortal()) {
                            $this->updatePortalActivity($currentContextItem->getItemID());
                        }

                        if (
                            $currentContextItem->isProjectRoom() ||
                            $currentContextItem->isCommunityRoom() ||
                            $currentContextItem->isPrivateRoom() ||
                            $currentContextItem->isGroupRoom()
                        ) {
                            $currentContextItem->saveLastLogin();
                            $currentContextItem->saveActivityPoints(1);

                            $portalId = $currentContextItem->getContextID();
                            $this->updatePortalActivity($portalId);
                        }
                    }
                }
            }
        }
    }

    private function updatePortalActivity(int $portalId)
    {
        $portalRespository = $this->entityManager->getRepository(Portal::class);
        $portal = $portalRespository->find($portalId);
        if ($portal) {
            $portal->setActivity($portal->getActivity() + 1);
            $this->entityManager->persist($portal);
            $this->entityManager->flush();
        }
    }
}