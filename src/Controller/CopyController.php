<?php

namespace App\Controller;

use App\Action\Copy\InsertAction;
use App\Action\Copy\RemoveAction;
use App\Services\CopyService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Filter\CopyFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CopyController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class CopyController extends BaseController
{
    /**
     * @Route("/room/{roomId}/copy/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param CopyService $copyService
     * @param RoomService $roomService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        CopyService $copyService,
        RoomService $roomService,
        LegacyEnvironment $environment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query parameters (AJAX)
        $copyFilter = $request->get('copyFilter');
        if (!$copyFilter) {
            $copyFilter = $request->query->get('copy_filter');
        }
        
        $roomItem = $this->loadRoom($environment, $roomId);

        if ($roomItem->isPrivateRoom()) {
            $rubrics = [
                "announcement" => "announcement",
                "material" => "material",
                "discussion" => "discussion",
                "date" => "date",
                "todo" => "todo",
            ];
        } else {
            $rubrics = $roomService->getRubricInformation($roomId);
            $rubrics = array_combine($rubrics, $rubrics);
        }

        if ($copyFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($roomItem);
    
            // manually bind values from the request
            $filterForm->submit($copyFilter);
    
            // apply filter
            $copyService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $entries = $copyService->getListEntries($roomId, $max, $start, $sort);

        $stackRubrics = ['date', 'material', 'discussion', 'todo'];

        $allowedActions = array();
        foreach ($entries as $item) {
            if (in_array($item->getItemType(), $rubrics)) {
                $allowedActions[$item->getItemID()][] = 'insert';
            }
            if (in_array($item->getItemType(), $stackRubrics)) {
                $allowedActions[$item->getItemID()][] = 'insertStack';
            }
            $allowedActions[$item->getItemID()][] = 'remove';
        }

        return [
            'roomId' => $roomId,
            'entries' => $entries,
            'allowedActions' => $allowedActions,
        ];
    }

    /**
     * @Route("/room/{roomId}/copy")
     * @Template()
     * @param Request $request
     * @param CopyService $copyService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        CopyService $copyService,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $roomItem = $this->loadRoom($environment, $roomId);
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions
            $copyService->setFilterConditions($filterForm);
        }

        // get number of items
        $itemsCountArray = $copyService->getCountArray($roomId);
        
        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'copies',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => null,
            'roomname' => $roomItem->getTitle(),
        ];
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/copy/xhr/insert", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrInsertAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(InsertAction::class);
        return $action->execute($room, $items);
    }

//    public function xhrInsertStackAction($roomId, Request $request)
//    {
//        $privateRoomItem = $legacyEnvironment->getCurrentUser()->getOwnRoom();
//        $legacyEnvironment->changeContextToPrivateRoom($privateRoomItem->getItemID());
//
//        $errorArray = [];
//        if (!empty($selectedIds)) {
//            foreach ($selectedIds as $id) {
//
//                // get item to copy
//                $item = $itemService->getItem($id);
//
//                // for now, we only copy materials, dates, discussions and todos
//                if (in_array($item->getItemType(), array(CS_MATERIAL_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_TODO_TYPE))) {
//
//                    // archive
//                    $toggleArchive = false;
//                    if ($item->isArchived() and !$legacyEnvironment->isArchiveMode()) {
//                        $toggleArchive = true;
//                        $legacyEnvironment->toggleArchiveMode();
//                    }
//
//                    // archive
//                    $importItem = $itemService->getTypedItem($id);
//
//                    // archive
//                    if ($toggleArchive) {
//                        $legacyEnvironment->toggleArchiveMode();
//                    }
//
//                    // archive
//                    $copy = $importItem->copy();
//
//                    $err = $copy->getErrorArray();
//                    if (!empty($err)) {
//                        $errorArray[$copy->getItemID() ] = $err;
//                    }
//                }
//            }
//        }
//
//        if (!empty($errorArray)) {
//            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i> '.implode(', ', $errorArray);
//        } else {
//            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('inserted %count% entries in my stack',count($selectedIds), array('%count%' => count($selectedIds)));
//        }
//    }

    /**
     * @Route("/room/{roomId}/copy/xhr/remove", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrRemoveAction(
        Request $request,
        int $roomId)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(RemoveAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        $copyService = $this->get('commsy.copy_service');

        if ($selectAll) {
            if ($request->query->has('copy_filter')) {
                $currentFilter = $request->query->get('copy_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $copyService->setFilterConditions($filterForm);
            }

            return $copyService->getListEntries($roomItem->getItemID());
        } else {
            return $copyService->getCopiesById($roomItem->getItemID(), $itemIds);
        }
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        if ($room->isPrivateRoom()) {
            $rubrics = [
                "announcement" => "announcement",
                "material" => "material",
                "discussion" => "discussion",
                "date" => "date",
                "todo" => "todo",
            ];
        } else {
            $roomService = $this->get('commsy_legacy.room_service');
            $rubrics = $roomService->getRubricInformation($room->getItemID());
            $rubrics = array_combine($rubrics, $rubrics);
        }

        return $this->createForm(CopyFilterType::class, [], [
            'action' => $this->generateUrl('app_copy_list', [
                'roomId' => $room->getItemID(),
            ]),
            'rubrics' => $rubrics,
        ]);
    }

    private function loadRoom(
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('The requested room does not exist');
            }
        }
        return $roomItem;
    }
}
