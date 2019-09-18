<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 27.01.18
 * Time: 11:40
 */

namespace App\Controller;


use App\Action\ActionFactory;
use App\Utils\RoomService;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseController extends AbstractController
{

    private $roomService;
    function __construct(
        RoomService $service
    ) {
        $this->roomService = $service;
        //$this->roomService = $this->get('commsy_legacy.room_service');
    }

    /**
     * @param cs_room_item $room
     * @param Request $request
     * @return array
     * @throws Exception
     */
    protected function getItemsForActionRequest(
        cs_room_item $room,
        Request $request) : array
    {
        // input processing
        if (!$request->request->has('action')) {
            throw new Exception('no action provided');
        }
        $action = $request->request->get('action');

        $selectAll = false;
        if ($request->request->has('selectAll')) {
            $selectAll = $request->request->get('selectAll') === 'true';
        }

        $positiveItemIds = [];
        $negativeItemIds = [];
        if (!$selectAll) {
            if (!$request->request->has('positiveItemIds')) {
                throw new Exception('select all is not set, but no "positiveItemIds" were provided');
            }

            $positiveItemIds = $request->request->get('positiveItemIds');
        } else {
            if ($request->request->has('negativeItemIds')) {
                $negativeItemIds = $request->request->get('negativeItemIds');
            }
        }

        // determine items to proceed on
        /** @var cs_item[] $items */
        $items = $this->getItemsByFilterConditions($request, $room, $selectAll, $positiveItemIds);
        if ($selectAll) {
            $items = array_filter($items, function (cs_item $item) use ($negativeItemIds) {
                return !in_array($item->getItemId(), $negativeItemIds);
            });
        }

        return $items;
    }

    /**
     * @param int $roomId
     * @return cs_room_item
     */
    protected function getRoom(int $roomId): cs_room_item
    {
        /** @var cs_room_item $roomItem */
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        return $roomItem;
    }

    abstract protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = []);
}