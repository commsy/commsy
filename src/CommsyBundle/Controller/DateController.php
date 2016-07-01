<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\AnnotationType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Filter\DateFilterType;

class DateController extends Controller
{    
    /**
     * @Route("/room/{roomId}/date/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(DateFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $dates = $dateService->getListDates($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($dates as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'dates' => $dates,
            'readerList' => $readerList
        );
    }

    /**
     * @Route("/room/{roomId}/date/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $selectAll = $request->request->get('selectAll');
        $selectAllStart = $request->request->get('selectAllStart');
        
        if ($selectAll == 'true') {
            $entries = $this->feedAction($roomId, $max = 1000, $start = $selectAllStart, $request);
            foreach ($entries['materials'] as $key => $value) {
                $selectedIds[] = $value->getItemId();
            }
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');

        $result = [];
        
        if ($action == 'markread') {
	        $dateService = $this->get('commsy_legacy.date_service');
	        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
    	        $item = $dateService->getDate($id);
    	        $versionId = $item->getVersionID();
    	        $noticedManager->markNoticed($id, $versionId);
    	        $readerManager->markRead($id, $versionId);

    	        $annotationList =$item->getAnnotationList();
    	        if ( !empty($annotationList) ){
    	            $annotationItem = $annotationList->getFirst();
    	            while($annotationItem){
    	               $noticedManager->markNoticed($annotationItem->getItemID(),$versionId);
    	               $readerManager->markRead($annotationItem->getItemID(),$versionId);
    	               $annotationItem = $annotationList->getNext();
    	            }
    	        }
	        }
	        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'copy') {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $sessionItem = $legacyEnvironment->getSessionItem();

            $currentClipboardIds = array();
            if ($sessionItem->issetValue('clipboard_ids')) {
                $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
            }

            foreach ($selectedIds as $itemId) {
                if (!in_array($itemId, $currentClipboardIds)) {
                    $currentClipboardIds[] = $itemId;
                    $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
                }
            }

            $result = [
                'count' => sizeof($currentClipboardIds)
            ];

            $sessionManager = $legacyEnvironment->getSessionManager();
            $sessionManager->save($sessionItem);

            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'save') {
            /* $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
            $response->headers->set('Content-Disposition', $contentDisposition);
            
            return $response; */
            
            $downloadService = $this->get('commsy_legacy.download_service');
        
            $zipFile = $downloadService->zipFile($roomId, $selectedIds);
    
            $response = new BinaryFileResponse($zipFile);
            $response->deleteFileAfterSend(true);
    
            $filename = 'CommSy_Date.zip';
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
            $response->headers->set('Content-Disposition', $contentDisposition);
    
            return $response;
        } else if ($action == 'delete') {
            $dateService = $this->get('commsy_legacy.date_service');
  		    foreach ($selectedIds as $id) {
  		        $item = $dateService->getDate($id);
  		        $item->delete();
  		    }
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
        }

        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }

    /**
     * @Route("/room/{roomId}/date")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(DateFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $dateService->getCountArray($roomId);

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray
        );
    }

       /**
     * @Route("/room/{roomId}/date/print")
     */
    public function printlistAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(DateFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $dates = $dateService->getListDates($roomId,$max = 1000, $start = 0, $sort = 'date');

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($dates as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }


        $itemsCountArray = $dateService->getCountArray($roomId);


        $html = $this->renderView('CommsyBundle:Date:listPrint.html.twig', [
            'roomId' => $roomId,
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray,
            'dates' => $dates,
            'readerList' => $readerList,
        ]);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->get('knp_snappy.pdf')->setOption('footer-line',true);
        $this->get('knp_snappy.pdf')->setOption('footer-spacing', 1);
        $this->get('knp_snappy.pdf')->setOption('footer-center',"[page] / [toPage]");
        $this->get('knp_snappy.pdf')->setOption('header-line', true);
        $this->get('knp_snappy.pdf')->setOption('header-spacing', 1 );
        $this->get('knp_snappy.pdf')->setOption('header-right', date("d.m.y"));
        $this->get('knp_snappy.pdf')->setOption('header-left', $roomItem->getTitle());
        $this->get('knp_snappy.pdf')->setOption('header-center', "Commsy");
        $this->get('knp_snappy.pdf')->setOption('images',true);

        // set cookie for authentication - needed to request images
        $this->get('knp_snappy.pdf')->setOption('cookie', [
            'SID' => $legacyEnvironment->getSessionID(),
        ]);

        //return new Response($html);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="print.pdf"',
            ]
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/calendar")
     * @Template()
     */
    public function calendarAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(DateFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_calendar', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date'
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/calendardashboard")
     * @Template()
     */
    public function calendardashboardAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        return array(
            'roomId' => $roomId,
            'module' => 'date'
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $dateService = $this->get('commsy_legacy.date_service');
        $itemService = $this->get('commsy.item_service');
        
        $date = $dateService->getDate($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $date;
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

        
        $itemArray = array($date);

        $current_context = $legacyEnvironment->getCurrentContextItem();
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($date->getContextId());        
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
		$readerManager->getLatestReaderByUserIDArray($id_array,$date->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $date->getModificationDate() ) {
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
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy.category_service')->getTags($roomId);
            $dateCategories = $date->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        return array(
            'roomId' => $roomId,
            'date' => $dateService->getDate($itemId),
            'readerList' => $readerList,
            'modifierList' => $modifierList,
            'user' => $legacyEnvironment->getCurrentUserItem(),
            'annotationForm' => $form->createView(),
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
            'draft' => $itemService->getItem($itemId)->isDraft(),
            'showCategories' => $current_context->withTags(),
            'showHashtags' => $current_context->withBuzzwords(),
            'roomCategories' => $categories,
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/events")
     */
    public function eventsAction($roomId, Request $request)
    {
        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        $listDates = $dateService->getCalendarEvents($roomId, $_GET['start'], $_GET['end']);

        $events = array();
        foreach ($listDates as $date) {
            $start = $date->getStartingDay();
            if ($date->getStartingTime() != '') {
                $start .= 'T'.$date->getStartingTime().'Z';
            }
            $end = $date->getEndingDay();
            if ($end == '') {
                $end = $date->getStartingDay();
            }
            if ($date->getEndingTime() != '') {
                $end .= 'T'.$date->getEndingTime().'Z';
            } 
            
            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = array();
            while ($participantItem) {
                $participantsNameArray[] = $participantItem->getFullname();
                $participantItem = $participantsList->getNext();    
            }
            $participantsDisplay = 'keine Zuordnung';
            if (!empty($participantsNameArray)) {
                implode(',', $participantsNameArray);
            }
            
            
            $events[] = array('itemId' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $date->getColor(),
                              'editable' => $date->isPublic(),
                              'description' => $date->getDateDescription(),
                              'place' => $date->getPlace(),
                              'participants' => $participantsDisplay,
                              'contextId' => '',
                              'contextTitle' => '',
                             );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/eventsdashboard")
     */
    public function eventsdashboardAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy.room_service');
        $dateService = $this->get('commsy_legacy.date_service');
        $userService = $this->get("commsy.user_service");
        $user = $userService->getPortalUserFromSessionId();
        $userList = $user->getRelatedUserList()->to_array();

        $listDates = array();
        foreach ($userList as $tempUser) {
            $listDates = array_merge($listDates, $dateService->getCalendarEvents($tempUser->getContextId(), $_GET['start'], $_GET['end']));
        }

        $events = array();
        foreach ($listDates as $date) {
            $start = $date->getStartingDay();
            if ($date->getStartingTime() != '') {
                $start .= 'T'.$date->getStartingTime().'Z';
            }
            $end = $date->getEndingDay();
            if ($end == '') {
                $end = $date->getStartingDay();
            }
            if ($date->getEndingTime() != '') {
                $end .= 'T'.$date->getEndingTime().'Z';
            } 
            
            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = array();
            while ($participantItem) {
                $participantsNameArray[] = $participantItem->getFullname();
                $participantItem = $participantsList->getNext();    
            }
            $participantsDisplay = 'keine Zuordnung';
            if (!empty($participantsNameArray)) {
                implode(',', $participantsNameArray);
            }
            
            $context = $roomService->getRoomItem($date->getContextId());

            $events[] = array('itemId' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $date->getColor(),
                              'editable' => $date->isPublic(),
                              'description' => $date->getDateDescription(),
                              'place' => $date->getPlace(),
                              'participants' => $participantsDisplay,
                              'contextId' => $context->getItemId(),
                              'contextTitle' => $context->getTitle(),
                             );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/create/{dateDescription}")
     * @Template()
     */
    public function createAction($roomId, $dateDescription, Request $request)
    {
        $translator = $this->get('translator');
        
        $dateService = $this->get('commsy_legacy.date_service');

        // create new material item
        $dateItem = $dateService->getNewDate();
        $dateItem->setTitle('['.$translator->trans('insert title').']');
        $dateItem->setDraftStatus(1);
        $dateItem->setPrivateEditing('1');

        if ($dateDescription != 'now') {
            $dateDescriptionArray = date_parse(urldecode($dateDescription));
        } else {
            $dateDescriptionArray = date_parse(date('Y-m-d H:i:s'));
        }
        
        $year = $dateDescriptionArray['year'];
        $month = $dateDescriptionArray['month'];
        if ($month < 10) {
            $month = '0'.$month;
        }
        $day = $dateDescriptionArray['day'];
        if ($day < 10) {
            $day = '0'.$day;
        }
        $hour = $dateDescriptionArray['hour'];
        if ($hour < 10) {
            $hour = '0'.$hour;
        }
        $minute = $dateDescriptionArray['minute'];
        if ($minute < 10) {
            $minute = '0'.$minute;
        }
        $second = $dateDescriptionArray['second'];
        if ($second < 10) {
            $second = '0'.$second;
        }
        
        $dateItem->setStartingDay($year.'-'.$month.'-'.$day);
        $dateItem->setStartingTime($hour.':'.$minute.':'.$second);
        
        $dateItem->setDateTime_start($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
        $dateItem->setDateTime_end($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);

        $dateItem->save();

        return $this->redirectToRoute('commsy_date_detail', array('roomId' => $roomId, 'itemId' => $dateItem->getItemId()));
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/calendaredit")
     */
    public function calendareditAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');
        
        $dateService = $this->get('commsy_legacy.date_service');
        $date = $dateService->getDate($itemId);
        
        $requestContent = json_decode($request->getContent());
        
        $startTimeArray = explode('T', $requestContent->event->start);
        $endTimeArray = explode('T', $requestContent->event->end);
        
        $date->setStartingDay($startTimeArray[0]);
        
        if (isset($startTimeArray[1])) {
            $date->setStartingTime($startTimeArray[1]);
        } else {
            $date->setStartingTime('');
        }
        
        if (isset($endTimeArray[0])) {
            $date->setEndingDay($endTimeArray[0]);
        } else {
            $date->setEndingDay('');
        }
        
        if (isset($endTimeArray[1])) {
            $date->setEndingTime($endTimeArray[1]);
        } else {
            $date->setEndingTime('');
        }
        
        $date->setDateTime_start(str_ireplace('T', ' ', $requestContent->event->start));
        if ($requestContent->event->end != '') {
            $date->setDateTime_end(str_ireplace('T', ' ', $requestContent->event->end));    
        }
        
        $date->save();
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->trans('date changed');
        
        $start = $date->getStartingDay();
        if ($date->getStartingTime() != '') {
            $start .= 'T'.$date->getStartingTime().'Z';
        }
        $end = $date->getEndingDay();
        if ($end == '') {
            $end = $date->getStartingDay();
        }
        if ($date->getEndingTime() != '') {
            $end .= 'T'.$date->getEndingTime().'Z';
        }
        
        return new JsonResponse(array('message' => $message,
                                      'timeout' => '5550',
                                      'layout' => 'cs-notify-message',
                                      'data' => array('itemId' => $date->getItemId(),
                                          'title' => $date->getTitle(),
                                          'start' => $start,
                                          'end' => $end,
                                          'color' => $date->getColor(),
                                          'editable' => $date->isPublic(),
                                          'description' => $date->getDateDescription(),
                                          'place' => $date->getPlace(),
                                          'participants' => $participantsDisplay
                                      ),
                                    ));
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $materialItem = NULL;
        
        if ($item->getItemType() == 'material') {
            // get material from MaterialService
            $materialItem = $materialService->getMaterial($itemId);
            if (!$materialItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($materialItem);
            $form = $this->createForm(MaterialType::class, $formData, array(
                'action' => $this->generateUrl('commsy_material_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ))
            ));
        } else if ($item->getItemType() == 'section') {
            // get section from MaterialService
            $materialItem = $materialService->getSection($itemId);
            if (!$materialItem) {
                throw $this->createNotFoundException('No section found for id ' . $roomId);
            }
            $formData = $transformer->transform($materialItem);
            $form = $this->createForm(SectionType::class, $formData, array());
        }
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $materialItem = $transformer->applyTransformation($materialItem, $form->getData());

                // update modifier
                $materialItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $materialItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_material_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }
        
        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }
    
    private function getTagDetailArray ($baseCategories, $itemCategories) {
        $result = array();
        $tempResult = array();
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $tempArray = array();
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }
    
        /**
     * @Route("/room/{roomId}/date/{itemId}/editdetails")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editdetailsAction($roomId, $itemId, Request $request)
    {
        
    }

    /**
     * @Route("/room/{roomId}/date/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {
        $dateService = $this->get('commsy_legacy.date_service');
        $itemService = $this->get('commsy.item_service');
        
        $date = $dateService->getDate($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $date;
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

        
        $itemArray = array($date);

        $current_context = $legacyEnvironment->getCurrentContextItem();
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($date->getContextId());        
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
        $readerManager->getLatestReaderByUserIDArray($id_array,$date->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $date->getModificationDate() ) {
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
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy.category_service')->getTags($roomId);
            $dateCategories = $date->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $html = $this->renderView('CommsyBundle:Date:detailPrint.html.twig', [
            'roomId' => $roomId,
            'date' => $dateService->getDate($itemId),
            'readerList' => $readerList,
            'modifierList' => $modifierList,
            'user' => $legacyEnvironment->getCurrentUserItem(),
            'annotationForm' => $form->createView(),
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
            'draft' => $itemService->getItem($itemId)->isDraft(),
            'showCategories' => $current_context->withTags(),
            'showHashtags' => $current_context->withBuzzwords(),
            'roomCategories' => $categories,
        ]);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->get('knp_snappy.pdf')->setOption('footer-line',true);
        $this->get('knp_snappy.pdf')->setOption('footer-spacing', 1);
        $this->get('knp_snappy.pdf')->setOption('footer-center',"[page] / [toPage]");
        $this->get('knp_snappy.pdf')->setOption('header-line', true);
        $this->get('knp_snappy.pdf')->setOption('header-spacing', 1 );
        $this->get('knp_snappy.pdf')->setOption('header-right', date("d.m.y"));
        $this->get('knp_snappy.pdf')->setOption('header-left', $roomItem->getTitle());
        $this->get('knp_snappy.pdf')->setOption('header-center', "Commsy");
        $this->get('knp_snappy.pdf')->setOption('images',true);
        $this->get('knp_snappy.pdf')->setOption('load-media-error-handling','ignore');
        $this->get('knp_snappy.pdf')->setOption('load-error-handling','ignore');

        // set cookie for authentication - needed to request images
        $this->get('knp_snappy.pdf')->setOption('cookie', [
            'SID' => $legacyEnvironment->getSessionID(),
        ]);


       // return new Response($html);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="print.pdf"'
            ]
        );
    }
}
