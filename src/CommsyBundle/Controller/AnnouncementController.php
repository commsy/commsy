<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\AnnouncementFilterType;

use \ZipArchive;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class AnnouncementController extends Controller
{
    /**
     * @Route("/room/{roomId}/announcement/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0,  $sort = 'date', Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new AnnouncementFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        return array(
            'roomId' => $roomId,
            'announcements' => $announcements,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'ratingList' => $ratingList
       );
    }
    
    /**
     * @Route("/room/{roomId}/announcement")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }



       // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new AnnouncementFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $itemsCountArray = $announcementService->getCountArray($roomId);




        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new AnnouncementFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();


        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'announcement',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
        );
    }


    /**
     * @Route("/room/{roomId}/announcement/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm('annotation');
        
        return array(
            'roomId' => $roomId,
            'announcement' => $infoArray['announcement'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'announcementList' => $infoArray['announcementList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
       );
    }


    
    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $itemService = $this->get('commsy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        $announcement = $announcementService->getAnnouncement($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $announcement;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($announcement->getContextId());        
        $numTotalMember = $roomItem->getAllUsers();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
           $id_array[] = $current_user->getItemID();
           $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array,$announcement->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($announcement->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $announcement->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count/$all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count/$all_user_count) * 100);
        $readerService = $this->get('commsy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        $reader = $readerService->getLatestReader($announcement->getItemId());
        if ( empty($reader) ) {
           $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $announcement->getModificationDate() ) {
           $readerList[$announcement->getItemId()] = 'changed';
        }
        
        $modifierList[$announcement->getItemId()] = $itemService->getAdditionalEditorsForItem($announcement);
        
        $announcements = $announcementService->getListAnnouncements($roomId);
        $announcementList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundAnnouncement = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($announcements as $tempAnnouncement) {
            if (!$foundAnnouncement) {
                if ($counterBefore > 5) {
                    array_shift($announcementList);
                } else {
                    $counterBefore++;
                }
                $announcementList[] = $tempAnnouncement;
                if ($tempAnnouncement->getItemID() == $announcement->getItemID()) {
                    $foundAnnouncement = true;
                }
                if (!$foundAnnouncement) {
                    $prevItemId = $tempAnnouncement->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $announcementList[] = $tempAnnouncement;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempAnnouncement->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($announcements)) {
            if ($prevItemId) {
                $firstItemId = $announcements[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $announcements[sizeof($announcements)-1]->getItemId();
            }
        }
        // mark annotations as readed
        $annotationList = $announcement->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);
        
        $infoArray['announcement'] = $announcement;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['announcementList'] = $announcementList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($announcements);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();

        
        return $infoArray;
    }

    /**
     * @Route("/room/{roomId}/announcement/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $announcementData = array();
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $transformer = $this->get('commsy_legacy.transformer.announcement');
        
        // create new announcement item
        $announcementItem = $announcementService->getNewannouncement();
        $announcementItem->setTitle('['.$translator->trans('insert title').']');
        $announcementItem->setBibKind('none');
        $announcementItem->setDraftStatus(1);
        $announcementItem->save();

 
        return $this->redirectToRoute('commsy_announcement_detail', array('roomId' => $roomId, 'itemId' => $announcementItem->getItemId()));

    }


    /**
     * @Route("/room/{roomId}/announcement/new")
     * @Template()
     */
    public function newAction($roomId, Request $request)
    {

    }


    /**
     * @Route("/room/{roomId}/announcement/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $transformer = $this->get('commsy_legacy.transformer.announcement');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $announcementItem = NULL;
        
        if ($item->getItemType() == 'announcement') {
            // get announcement from announcementService
            $announcementItem = $announcementService->getannouncement($itemId);
            if (!$announcementItem) {
                throw $this->createNotFoundException('No announcement found for id ' . $roomId);
            }
            $formData = $transformer->transform($announcementItem);
            $form = $this->createForm('announcement', $formData, array(
                'action' => $this->generateUrl('commsy_announcement_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ))
            ));
        } 
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $announcementItem = $transformer->applyTransformation($announcementItem, $form->getData());

                // update modifier
                $announcementItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $announcementItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_announcement_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }
        
        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),

        );
    }



    /**
     * @Route("/room/{roomId}/announcement/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $transformer = $this->get('commsy_legacy.transformer.announcement');
        
        $tempItem = NULL;
        
        $tempItem = $announcementService->getannouncement($itemId);
        
        $itemArray = array($tempItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $infoArray = $this->getDetailInfo($roomId, $itemId);
        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList,
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'showRating' => $infoArray['showRating'],
        );
    }
 

    /**
     * @Route("/room/{roomId}/announcement/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
        if ($action == 'markread') {
            $announcementService = $this->get('commsy_legacy.announcement_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $announcementService->getAnnouncement($id);
                $versionId = $item->getVersionID();
                $noticedManager->markNoticed($id, $versionId);
                $readerManager->markRead($id, $versionId);
                $annotationList =$item->getAnnotationList();
                if ( !empty($annotationList) ){
                    $annotationItem = $annotationList->getFirst();
                    while($annotationItem){
                       $noticedManager->markNoticed($annotationItem->getItemID(),'0');
                       $annotationItem = $annotationList->getNext();
                    }
                }
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'copy') {
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'save') {
            $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
            $response->headers->set('Content-Disposition', $contentDisposition);
            
            return $response;
        } else if ($action == 'delete') {
            $announcementService = $this->get('commsy_legacy.announcement_service');
            foreach ($selectedIds as $id) {
                $item = $announcementService->getAnnouncement($id);
                $item->delete();
            }
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
        }
        
        $response = new JsonResponse();
 /*       $response->setData(array(
            'message' => $message,
            'status' => $status
        ));
  */      
        $response->setData(array(
            'message' => $message,
            'timeout' => '5550',
            'layout'   => 'cs-notify-message'
        ));
        return $response;
    }

}
