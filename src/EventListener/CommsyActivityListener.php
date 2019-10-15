<?php

namespace App\EventListener;

use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Liip\ThemeBundle\ActiveTheme;

use App\Utils\RoomService;
use App\Services\LegacyEnvironment;

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

    public function onKernelRequest(GetResponseEvent $event)
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

                    $activity_points = 1;
                    $context_item_current = $environment->getCurrentContextItem();
                    if (isset($context_item_current)) {
                        $context_item_current->saveActivityPoints($activity_points);
                        if ($context_item_current->isProjectRoom()
                            or $context_item_current->isCommunityRoom()
                            or $environment->inPrivateRoom()
                            or $environment->inGroupRoom()
                        ) {

                            // archiving
                            $context_item_current->saveLastLogin();

                            $portalId = $context_item_current->getContextID();
                            $portalRespository = $this->entityManager->getRepository(Portal::class);
                            $portal = $portalRespository->find($portalId);
                            if ($portal) {
                                $portal->setActivity($portal->getActivity() + 1);
                                $this->entityManager->persist($portal);
                                $this->entityManager->flush();
                            }
                        }
                    }
                }
            }
        }
    }
}