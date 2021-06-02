<?php


namespace App\Utils;


use App\Entity\Portal;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PortalGuessService
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        ItemService $itemService
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->itemService = $itemService;
    }

    /**
     * Return the portal context entity or null
     *
     * @return Portal|null
     */
    public function fetchPortal(Request $request): ?Portal
    {
        if ($request !== null) {
            $portalId = $request->attributes->get('portalId')
                ?? $this->fetchContextId($request);
            if ($portalId !== null) {
                return $this->entityManager->getRepository(Portal::class)->find($portalId);
            }

            $roomId = $request->attributes->get('roomId');
            if ($roomId !== null) {
                /** @var Room $room */
                $room = $this->entityManager->getRepository(Room::class)->find($roomId);
                if ($room !== null) {
                    return $this->entityManager->getRepository(Portal::class)->find($room->getContextId());
                }
            }

            $itemId = $request->attributes->get('itemId');
            if ($itemId !== null) {
                $item = $this->itemService->getItem($itemId);
                if ($item !== null) {
                    return $this->entityManager->getRepository(Portal::class)->find($item->getContextID());
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
        if ($request !== null) {
            $contextId = $request->attributes->get('context');
            if ($contextId !== null) {
                return $contextId;
            }

            $roomId = $request->attributes->get('roomId');
            if ($roomId !== null) {
                /** @var Room $room */
                $room = $this->entityManager->getRepository(Room::class)->find($roomId);
                if ($room !== null) {
                    return $room->getContextId();
                }
            }

            $portalId = $request->attributes->get('portalId');
            if ($portalId !== null) {
                return $portalId;
            }
        }

        return null;
    }
}