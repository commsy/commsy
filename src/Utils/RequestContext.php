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

namespace App\Utils;

use App\Entity\Portal;
use App\Entity\Room;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Request;

final readonly class RequestContext
{
    public function __construct(
        private PortalRepository $portalRepository,
        private RoomRepository $roomRepository,
        private ItemService $itemService,
        private FileService $fileService
    ) {
    }

    /**
     * Return the room context entity or null.
     */
    public function fetchRoom(Request $request): ?Room
    {
        $contextId = $this->fetchContextId($request);

        if (null !== $contextId) {
            $room = $this->roomRepository->find($contextId);
            if ($room) {
                return $room;
            }
        }

        return null;
    }

    /**
     * Return the portal context entity or null.
     */
    public function fetchPortal(Request $request): ?Portal
    {
        $contextId = $this->fetchContextId($request);

        if (null !== $contextId) {
            /** @var Portal $portal */
            $portal = $this->portalRepository->findPortalById($contextId);
            if ($portal) {
                return $portal;
            }

            $item = $this->itemService->getItem($contextId);
            if (null === $item) {
                $itemId = $request->attributes->get('itemId');
                if (null !== $itemId) {
                    $item = $this->itemService->getItem($itemId);
                }
            }
            if (null !== $item) {
                $portal = $this->portalRepository->findPortalById($item->getContextID());
                if ($portal) {
                    return $portal;
                }
            }
        }

        return null;
    }

    /**
     * Returns the contextId or null.
     */
    public function fetchContextId(Request $request): ?int
    {
        $contextId = $request->attributes->get('context');
        if (null !== $contextId) {
            return (int) $contextId;
        }

        $roomId = $request->attributes->get('roomId');
        if (null !== $roomId) {
            return (int) $roomId;
        }

        $portalId = $request->attributes->get('portalId');
        if (null !== $portalId) {
            return $portalId;
        }

        $fileId = $request->attributes->get('fileId');
        if (null !== $fileId) {
            $file = $this->fileService->getFile($fileId);
            return $file?->getContextID();
        }

        return null;
    }
}
