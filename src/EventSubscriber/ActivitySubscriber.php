<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\Portal;
use App\Room\RoomManager;
use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ActivitySubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly EntityManagerInterface $entityManager,
        private readonly RoomManager $roomManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        if ($event->isMainRequest()) {
            $request = $event->getRequest();
            if (!$request->isXmlHttpRequest()) {
                $countRequest = true;
                if (preg_match('~\/room\/(\d)+\/user\/(\d)+\/image~', $request->getUri())) {
                    $countRequest = false;
                } elseif (preg_match('~\/room\/(\d)+\/theme\/background~', $request->getUri())) {
                    $countRequest = false;
                } elseif (preg_match('~\/room\/(\d)+\/logo~', $request->getUri())) {
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
                            $currentContextItem->saveActivityPoints(1);

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
}
