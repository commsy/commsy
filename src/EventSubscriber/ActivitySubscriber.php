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

use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use App\Services\CurrentContextResolver;
use App\Utils\RequestLogging;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records portal and room activity once the response is out.
 *
 * Everything here writes as a statement rather than through the unit of work, so the
 * bookkeeping does not drag along whatever the finished request left dirty.
 */
class ActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentContextResolver $currentContextResolver,
        private readonly PortalRepository $portalRepository,
        private readonly RoomRepository $roomRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($event->isMainRequest()) {
            $request = $event->getRequest();
            if (!$request->isXmlHttpRequest()) {
                foreach (RequestLogging::ROOM_CONTEXT_IGNORE_REGEX_ARRAY as $regex) {
                    if (preg_match($regex, $request->getUri())) {
                        return;
                    }
                }

                $currentContextItem = $this->currentContextResolver->getContextItem();

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

                        $this->roomRepository->markActive($currentContextItem->getItemId());

                        $portalId = $currentContextItem->getContextID();
                        $this->updatePortalActivity($portalId);
                    }
                }
            }
        }
    }

    private function updatePortalActivity(int $portalId): void
    {
        $this->portalRepository->incrementActivity($portalId);
    }
}
