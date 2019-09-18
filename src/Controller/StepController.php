<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 21:58
 */

namespace App\Controller;

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
    public function xhrDeleteAction(
        Request $request,
        $roomId)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        $action = $this->get('commsy.action.delete.step');
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