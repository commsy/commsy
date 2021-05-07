<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 22:41
 */

namespace App\Controller;


use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteSection;
use App\Utils\MaterialService;
use App\Utils\RoomService;
use cs_room_item;
use cs_step_item;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class SectionController extends BaseController
{
    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################
    /**
     * @var MaterialService
     */
    private MaterialService $materialService;

    /**
     * @var DeleteAction
     */
    private DeleteAction $deleteAction;

    /**
     * SectionController constructor.
     * @param MaterialService $materialService
     * @param DeleteSection $deleteSection
     * @param DeleteAction $deleteAction
     * @param RoomService $roomService
     */
    public function __construct(MaterialService $materialService, DeleteSection $deleteSection, DeleteAction $deleteAction, RoomService $roomService)
    {
        parent::__construct($roomService);
        $this->materialService = $materialService;
        $this->deleteAction = $deleteAction;
        $this->deleteAction->setDeleteStrategy($deleteSection);
    }


    /**
     * @Route("/room/{roomId}/section/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        return $this->deleteAction->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_step_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if (count($itemIds) == 1) {
            return [$this->materialService->getSection($itemIds[0])];
        }

        return [];
    }
}