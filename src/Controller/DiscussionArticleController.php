<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 23:20
 */

namespace App\Controller;


use App\Action\Delete\DeleteAction;
use cs_discussionarticle_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class DiscussionArticleController extends BaseController
{
    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/discussion_article/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param DeleteAction $action
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        int $roomId)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_discussionarticle_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        $discussionService = $this->get('commsy_legacy.discussion_service');

        if (count($itemIds) == 1) {
            return [$discussionService->getArticle($itemIds[0])];
        }

        return [];
    }
}