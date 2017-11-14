<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\CopyFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CopyController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_SEE', roomId)")
 */
class CopyController extends Controller
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
            $defaultFilterValues = [];
            $filterForm = $this->createForm(CopyFilterType::class, $defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_copy_list', array(
                    'roomId' => $roomId,
                )),
                'rubrics' => $rubrics,
            ));
    
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

        $defaultFilterValues = [];
        $filterForm = $this->createForm(CopyFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_copy_list', array(
                'roomId' => $roomId,
            )),
            'rubrics' => $rubrics,
        ));

        // get the copy service
        $copyService = $this->get('commsy.copy_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
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

    /**
     * @Route("/room/{roomId}/copy/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $translator = $this->get('translator');
        $itemService = $this->get('commsy_legacy.item_service');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
        $result = [];
        
        if ($action == 'insert') {
            $errorArray = [];
            
            // archive
            if ($legacyEnvironment->isArchiveMode()) {
                $errorArray[] = $translator->trans('copy items in archived workspaces is not allowed');
            }
                
            // archive
            elseif ($legacyEnvironment->inPortal()) {
                $error_array[] = $translator->trans('copy items in portal is not allowed');
            } else if ($legacyEnvironment->getCurrentUserItem()->isOnlyReadUser()) {
                $error_array[] = $translator->trans('copy items as read only user is not allowed');
            } elseif (!empty($selectedIds)) {
                foreach ($selectedIds as $id) {
                    
                    // get item to copy
                    $item = $itemService->getItem($id);
                    
                    // archive
                    $toggleArchive = false;
                    if ($item->isArchived() and !$legacyEnvironment->isArchiveMode()) {
                        $toggleArchive = true;
                        $legacyEnvironment->toggleArchiveMode();
                    }
                    
                    // archive
                    $importItem = $itemService->getTypedItem($id);
                    
                    // archive
                    if ($toggleArchive) {
                        $legacyEnvironment->toggleArchiveMode();
                    }
                    
                    // archive
                    $copy = $importItem->copy();
                    
                    $err = $copy->getErrorArray();
                    if (!empty($err)) {
                        $errorArray[$copy->getItemID()] = $err;
                    } else {
                       $readerManager = $legacyEnvironment->getReaderManager();
                       $readerManager->markRead($copy->getItemID(), $copy->getVersionID());
                       $noticedManager = $legacyEnvironment->getNoticedManager();
                       $noticedManager->markNoticed($copy->getItemID(), $copy->getVersionID());
                    }
                }
            }
            
            if (!empty($errorArray)) {
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i> '.implode(', ', $errorArray);
            } else {
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('inserted %count% entries in this room',count($selectedIds), array('%count%' => count($selectedIds)), 'messages');
            }
        } else if ($action == 'insertStack') {
            $privateRoomItem = $legacyEnvironment->getCurrentUser()->getOwnRoom();
            $legacyEnvironment->changeContextToPrivateRoom($privateRoomItem->getItemID());
                
            $errorArray = [];
            if (!empty($selectedIds)) {
                foreach ($selectedIds as $id) {
                    
                    // get item to copy
                    $item = $itemService->getItem($id);
                    
                    // for now, we only copy materials, dates, discussions and todos
                    if (in_array($item->getItemType(), array(CS_MATERIAL_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_TODO_TYPE))) {
                        
                        // archive
                        $toggleArchive = false;
                        if ($item->isArchived() and !$legacyEnvironment->isArchiveMode()) {
                            $toggleArchive = true;
                            $legacyEnvironment->toggleArchiveMode();
                        }
                        
                        // archive
                        $importItem = $itemService->getTypedItem($id);
                        
                        // archive
                        if ($toggleArchive) {
                            $legacyEnvironment->toggleArchiveMode();
                        }
                        
                        // archive
                        $copy = $importItem->copy();
                        
                        $err = $copy->getErrorArray();
                        if (!empty($err)) {
                            $errorArray[$copy->getItemID() ] = $err;
                        }
                    }
                }
            }
            
            if (!empty($errorArray)) {
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i> '.implode(', ', $errorArray);
            } else {
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('inserted %count% entries in my stack',count($selectedIds), array('%count%' => count($selectedIds)));
            }
        } else if ($action == 'remove') {
            $copyService = $this->get('commsy.copy_service');

            $countArray = $copyService->removeEntries($roomId, $selectedIds);
            $result['count'] = $countArray['countAll'];
            $result['countSelected'] = $countArray['count'];

            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('removed %count% entries from list',count($selectedIds), array('%count%' => count($selectedIds)), 'messages');
        } 
        
        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }

}
