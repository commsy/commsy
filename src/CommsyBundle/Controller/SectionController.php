<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 22:41
 */

namespace CommsyBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class SectionController extends BaseController
{
    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/section/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.section');
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
        $materialService = $this->get('commsy_legacy.material_service');

        if (count($itemIds) == 1) {
            return [$materialService->getSection($itemIds[0])];
        }

        return [];
    }
}