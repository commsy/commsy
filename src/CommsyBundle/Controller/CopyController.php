<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Action\Copy\InsertAction;
use CommsyBundle\Action\Copy\RemoveAction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\CopyFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CopyController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class CopyController extends BaseController
{
    /**
     * @Route("/room/{roomId}/copy/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0,  $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query parameters (AJAX)
        $copyFilter = $request->get('copyFilter');
        if (!$copyFilter) {
            $copyFilter = $request->query->get('copy_filter');
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('The requested room does not exist');
            }
        }

        // get the copy service
        $copyService = $this->get('commsy.copy_service');

        if ($roomItem->isPrivateRoom()) {
            $rubrics = [
                "announcement" => "announcement",
                "material" => "material",
                "discussion" => "discussion",
                "date" => "date",
                "todo" => "todo",
            ];
        } else {
            $roomService = $this->get('commsy_legacy.room_service');
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
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('The requested room does not exist');
            }
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the copy service
        $copyService = $this->get('commsy.copy_service');

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
     * @throws \Exception
     */
    public function xhrInsertAction($roomId, Request $request)
    {
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
     * @throws \Exception
     */
    public function xhrRemoveAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(RemoveAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
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
     * @param \cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
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
            'action' => $this->generateUrl('commsy_copy_list', [
                'roomId' => $room->getItemID(),
            ]),
            'rubrics' => $rubrics,
        ]);
    }
}
