<?php


namespace App\Utils;


use App\Entity\Portal;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Request;

class RequestContext
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
        $contextId = $this->fetchContextId($request);

        if ($contextId !== null) {
            $portal = $this->portalRepository->find($contextId);
            if ($portal) {
                return $portal;
            }

            $room = $this->roomRepository->find($contextId);
            if ($room !== null) {
                $portal = $this->portalRepository->find($room->getContextId());
                if ($portal) {
                    return $portal;
                }
            }

            $item = $this->itemService->getItem($contextId);
            if ($item !== null) {
                $portal = $this->portalRepository->find($item->getContextID());
                if ($portal) {
                    return $portal;
                }
            }

            $itemId = $request->attributes->get('itemId');
            if ($itemId !== null) {
                $item = $this->itemService->getItem($itemId);
                if ($item !== null) {
                    return $this->portalRepository->find($item->getContextID());
                }
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
            return (int) $roomId;
        }

        $portalId = $request->attributes->get('portalId');
        if ($portalId !== null) {
            return $portalId;
        }

        return null;
    }
}