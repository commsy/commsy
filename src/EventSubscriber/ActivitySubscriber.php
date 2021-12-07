<?php

namespace App\EventSubscriber;

use App\Entity\Portal;
use App\Room\RoomManager;
use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var RoomManager
     */
    private RoomManager $roomManager;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
        RoomManager $roomManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
        $this->roomManager = $roomManager;
    }

    public function onKernelRequest(RequestEvent $event)
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
                    $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

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
                            $room = $this->roomManager->getRoom($currentContextItem->getItemId());
                            if ($room) {
                                $this->roomManager->resetInactivity($room, false, true, true);
                            }

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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}