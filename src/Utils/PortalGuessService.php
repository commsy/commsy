<?php


namespace App\Utils;


use App\Entity\Portal;
use App\Entity\Room;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PortalGuessService
{
    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var RoomRepository
     */
    private RoomRepository $roomRepository;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    public function __construct(
        PortalRepository $portalRepository,
        RoomRepository $roomRepository,
        ItemService $itemService
    ) {
        $this->portalRepository = $portalRepository;
        $this->roomRepository = $roomRepository;
        $this->itemService = $itemService;
    }

    /**
     * Return the portal context entity or null
     *
     * @param Request $request
     * @return Portal|null
     */
    public function fetchPortal(Request $request): ?Portal
    {
        $portalId = $request->attributes->get('portalId')
            ?? $this->fetchContextId($request);
        if ($portalId !== null) {
            return $this->portalRepository->find($portalId);
        }

        $roomId = $request->attributes->get('roomId');
        if ($roomId !== null) {
            /** @var Room $room */
            $room = $this->roomRepository->find($roomId);
            if ($room !== null) {
                return $this->portalRepository->find($room->getContextId());
            }
        }

        $itemId = $request->attributes->get('itemId');
        if ($itemId !== null) {
            $item = $this->itemService->getItem($itemId);
            if ($item !== null) {
                return $this->portalRepository->find($item->getContextID());
            }
        }

        return null;
    }

    /**
     * Returns the contextId or null
     * @param Request $request
     * @return int|null
     */
    public function fetchContextId(Request $request): ?int
    {
        $contextId = $request->attributes->get('context');
        if ($contextId !== null) {
            return (int) $contextId;
        }

        $roomId = $request->attributes->get('roomId');
        if ($roomId !== null) {
            /** @var Room $room */
            $room = $this->roomRepository->find($roomId);
            if ($room !== null) {
                return $room->getContextId();
            }
        }

        $portalId = $request->attributes->get('portalId');
        if ($portalId !== null) {
            return $portalId;
        }

        return null;
    }
}