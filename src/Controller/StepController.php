<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 21:58
 */

namespace App\Controller;

use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteStep;
use cs_room_item;
use cs_step_item;
use Exception;
use Symfony\Component\HttpFoundation\Response;

use App\Action\TodoStatus\TodoStatusAction;
use App\Utils\TodoService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class StepController extends BaseController
{
    private TodoService $todoService;

    /**
     * @required
     * @param TodoService $todoService
     */
    public function setTodoService(TodoService $todoService): void
    {
        $this->todoService = $todoService;
    }



    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/step/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        DeleteStep $deleteStep,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        $action->setDeleteStrategy($deleteStep);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/step/xhr/changesatatus/{itemId}", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrChangeStatusAction($roomId, $itemId, Request $request, TodoService $todoService)
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
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_step_item[]
     */
    protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        if (count($itemIds) == 1) {
            return [$this->todoService->getStep($itemIds[0])];
        }

        return [];
    }
}