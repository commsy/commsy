<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 21:58
 */

namespace App\Controller;


use App\Action\TodoStatus\TodoStatusAction;
use App\Utils\ItemService;
use App\Utils\TodoService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class StepController extends BaseController
{
    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/step/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.step');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/step/xhr/changesatatus/{itemId}", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrChangeStatusAction($roomId, $itemId, Request $request, ItemService $itemService, TodoService $todoService)
    {
        $room = $this->getRoom($roomId);
        $roomToDoItems = $todoService->getTodosById($roomId, []);

        foreach($roomToDoItems as $roomToDoItem){
           $steps = $roomToDoItem->getStepItemList()->_data;
           foreach($steps as $step){
               if(strcmp($step->getItemID(), $itemId) == 0){
                   $items = [$roomToDoItem];
                   $room = $roomToDoItem->getContextItem();
               }
           }
        }

        $payload = $request->request->get('payload');
        if (!isset($payload['status'])) {
            throw new \Exception('new status string not provided');
        }
        $newStatus = $payload['status'];

        $action = $this->get(TodoStatusAction::class);
        $action->setNewStatus($newStatus);
        return $action->execute($room, $items);
    }
    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_step_item[]
     */
    protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        $todoService = $this->get('commsy_legacy.todo_service');

        if (count($itemIds) == 1) {
            return [$todoService->getStep($itemIds[0])];
        }

        return [];
    }
}