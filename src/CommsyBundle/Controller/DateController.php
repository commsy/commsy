<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\DateType;
use CommsyBundle\Form\Type\DateDetailsType;
use CommsyBundle\Form\Type\AnnotationType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Filter\DateFilterType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;

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

        $readerService = $this->get('commsy_legacy.reader_service');

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
        $itemService = $this->get('commsy_legacy.item_service');
        
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
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
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
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
            
            $color = '';
            if ($date->getColor() != '') {
                $color = $this->container->getParameter('commsy.themes.'.str_ireplace('-', '_', $date->getColor()));
            }
            
            $recurringDescription = '';
            if ($date->getRecurrencePattern() != '') {
                $translator = $this->get('translator');
                
                $recurrencePattern = $date->getRecurrencePattern();
                
                if (isset($recurrencePattern['recurringEndDate'])) {
                    $endDate = new \DateTime($recurrencePattern['recurringEndDate']);
                }
                
                if ($recurrencePattern['recurring_select'] == 'RecurringDailyType') {
                    $recurringDescription = $translator->trans('dailyDescription', array('%day%' => $recurrencePattern['recurring_sub']['recurrenceDay'], '%date%' => $endDate->format('d.m.Y')), 'date');
                } else if ($recurrencePattern['recurring_select'] == 'RecurringWeeklyType') {
                    $daysOfWeek = array();
                    foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                        $daysOfWeek[] = $translator->trans($day, array(), 'date');
                    }
                    $recurringDescription = $translator->trans('weeklyDescription', array('%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'], '%daysOfWeek%' => implode(', ', $daysOfWeek), '%date%' => $endDate->format('d.m.Y')), 'date');
                } else if ($recurrencePattern['recurring_select'] == 'RecurringMonthlyType') {
                    $tempDayOfMonthInterval = $translator->trans('first', array(), 'date');
                    if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 2) {
                        $tempDayOfMonthInterval = $translator->trans('second', array(), 'date');
                    } else if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 3) {
                        $tempDayOfMonthInterval = $translator->trans('third', array(), 'date');
                    } else if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 4) {
                        $tempDayOfMonthInterval = $translator->trans('fourth', array(), 'date');
                    } else if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 5) {
                        $tempDayOfMonthInterval = $translator->trans('fifth', array(), 'date');
                    } else if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 'last') {
                        $tempDayOfMonthInterval = $translator->trans('last', array(), 'date');
                    }
                    $recurringDescription = $translator->trans('monthlyDescription', array('%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'], '%day%' => $tempDayOfMonthInterval, '%dayOfWeek%' => $translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], array(), 'date'), '%date%' => $endDate->format('d.m.Y')), 'date');
                } else if ($recurrencePattern['recurring_select'] == 'RecurringYearlyType') {
                    $recurringDescription = $translator->trans('yearlyDescription', array('%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], '%month%' => $translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'], array(), 'date'), '%date%' => $endDate->format('d.m.Y')), 'date');
                }
            }
            
            $events[] = array('itemId' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $color,
                              'editable' => $date->isPublic(),
                              'description' => $date->getDateDescription(),
                              'place' => $date->getPlace(),
                              'participants' => $participantsDisplay,
                              'contextId' => '',
                              'contextTitle' => '',
                              'recurringDescription' => $recurringDescription,
                             );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/eventsdashboard")
     */
    public function eventsdashboardAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $dateService = $this->get('commsy_legacy.date_service');
        $userService = $this->get("commsy_legacy.user_service");
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
            
            $color = '';
            if ($date->getColor() != '') {
                $color = $this->container->getParameter('commsy.themes.'.str_ireplace('-', '_', $date->getColor()));
            }
            
            $context = $roomService->getRoomItem($date->getContextId());

            $events[] = array('itemId' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $color,
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
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $dateService = $this->get('commsy_legacy.date_service');
        $transformer = $this->get('commsy_legacy.transformer.date');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $materialItem = NULL;
        
        // get date from DateService
        $dateItem = $dateService->getDate($itemId);
        if (!$dateItem) {
            throw $this->createNotFoundException('No date found for id ' . $itemId);
        }
        $formData = $transformer->transform($dateItem);
        $formOptions = array(
            'action' => $this->generateUrl('commsy_date_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
        );
        if ($dateItem->getRecurrencePattern() != '') {
            $formOptions['attr']['unsetRecurrence'] = true;
        }
        $form = $this->createForm(DateType::class, $formData, $formOptions);
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save' || $saveType == 'saveThisDate') {
                $dateItem = $transformer->applyTransformation($dateItem, $form->getData());

                // update modifier
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $dateItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($saveType == 'saveAllDates') {
                $dateService = $this->get('commsy_legacy.date_service');
                $datesArray = $dateService->getRecurringDates($dateItem->getContextId(), $dateItem->getRecurrenceId());
                $formData = $form->getData();
                $dateItem = $transformer->applyTransformation($dateItem, $formData);
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $dateItem->save();
                foreach ($datesArray as $tempDate) {
                    $tempDate->setTitle($dateItem->getTitle());
                    $tempDate->setPrivateEditing($dateItem->isPrivateEditing());
                    $tempDate->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $tempDate->save();
                }
            } else {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_date_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
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
            'withRecurrence' => $dateItem->getRecurrencePattern() != '',
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
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $dateService = $this->get('commsy_legacy.date_service');
        $transformer = $this->get('commsy_legacy.transformer.date');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $materialItem = NULL;
        
        // get date from DateService
        $dateItem = $dateService->getDate($itemId);
        if (!$dateItem) {
            throw $this->createNotFoundException('No date found for id ' . $itemId);
        }
        $formData = $transformer->transform($dateItem);
        $formOptions = array(
            'action' => $this->generateUrl('commsy_date_editdetails', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
        );
        if ($dateItem->getRecurrencePattern() != '') {
            $formOptions['attr']['unsetRecurrence'] = true;
        }
        $form = $this->createForm(DateDetailsType::class, $formData, $formOptions);
        
        $form->handleRequest($request);
        
        $submittedFormData = $form->getData();
        
        $startDateConstraint = new NotBlank();
        $errorList = $this->get('validator')->validate(
            $submittedFormData['start']['date'],
            $startDateConstraint
        );
        
        if ($form->isValid() && (count($errorList) === 0)) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {
                $formData = $form->getData();
                
                $valuesBeforeChange = array();
	            $valuesBeforeChange['startingTime'] = $dateItem->getStartingTime();
	            $valuesBeforeChange['endingTime'] = $dateItem->getEndingTime();
	            $valuesBeforeChange['place'] = $dateItem->getPlace();
	            $valuesBeforeChange['color'] = $dateItem->getColor();
                
                $dateItem = $transformer->applyTransformation($dateItem, $formData);

                // update modifier
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $valuesToChange = array();
                if($valuesBeforeChange['startingTime'] != $dateItem->getStartingTime()){
                    $valuesToChange[] = 'startingTime';
                }
                if($valuesBeforeChange['endingTime'] != $dateItem->getEndingTime()){
                    $valuesToChange[] = 'endingTime';
                }
                if($valuesBeforeChange['place'] != $dateItem->getPlace()){
                    $valuesToChange[] = 'place';
                }
                if($valuesBeforeChange['color'] != $dateItem->getColor()){
                    $valuesToChange[] = 'color';
                }

                $withRecurring = false;
                $isNewRecurring = true;
                if (isset($formData['recurring_select'])) {
                    if ($formData['recurring_select'] != '' && $formData['recurring_select'] != 'RecurringNoneType') {
                        $withRecurring = true;
                    }
                    if (!$withRecurring) {
                        if ($dateItem->getRecurrencePattern() != '') {
                            $withRecurring = true;
                            $isNewRecurring = false;
                        }
                    }
                }
                if ($withRecurring) {
                    $this->saveRecurringDates($dateItem, $isNewRecurring, $valuesToChange, $formData);
                }

                $dateItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($saveType == 'saveThisDate') {
                $formData = $form->getData();
                $dateItem = $transformer->applyTransformation($dateItem, $formData);
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $dateItem->save();
            } else if ($saveType == 'saveAllDates') {
                $dateService = $this->get('commsy_legacy.date_service');
                $datesArray = $dateService->getRecurringDates($dateItem->getContextId(), $dateItem->getRecurrenceId());
                $formData = $form->getData();
                $dateItem = $transformer->applyTransformation($dateItem, $formData);
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $dateItem->save();
                foreach ($datesArray as $tempDate) {
                    $tempDate->setStartingTime($dateItem->getStartingTime());
                    $tempDate->setEndingTime($dateItem->getEndingTime());
                    $tempDate->setPlace($dateItem->getPlace());
                    $tempDate->setColor($dateItem->getColor());
                    $tempDate->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $tempDate->save();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            } 
            return $this->redirectToRoute('commsy_date_savedetails', array('roomId' => $roomId, 'itemId' => $itemId));
        }else {
            if (count($errorList) > 0) {
                $form->get('start')->addError(new FormError('Start date must not be empty'));
            }
        }
        
        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
            'date' => $dateItem
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $dateService = $this->get('commsy_legacy.date_service');
        $transformer = $this->get('commsy_legacy.transformer.date');
        
        $date = $dateService->getDate($itemId);
        
        $itemArray = array($date);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        //$roomItem = $roomManager->getItem($material->getContextId());        
        //$numTotalMember = $roomItem->getAllUsers();
        
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
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
        
        return array(
            'roomId' => $roomId,
            'item' => $date,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/savedetails")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function savedetailsAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $dateService = $this->get('commsy_legacy.date_service');
        $transformer = $this->get('commsy_legacy.transformer.date');
        
        $date = $dateService->getDate($itemId);
        
        $itemArray = array($date);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        //$roomItem = $roomManager->getItem($material->getContextId());        
        //$numTotalMember = $roomItem->getAllUsers();
        
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
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
        
        return array(
            'roomId' => $roomId,
            'item' => $date,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }
    
    
    function saveRecurringDates($dateItem, $isNewRecurring, $valuesToChange, $formData){
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
       
        if($isNewRecurring){
            $recurrentId = $dateItem->getItemID();
            $recurringDateArray = array();
            $recurringPatternArray = array();

            $startDate = new \DateTime($dateItem->getStartingDay());
            $endDate = $formData['recurring_sub']['untilDate'];

            $recurringPatternArray['recurring_select'] = $formData['recurring_select'];

            // daily recurring
            if($formData['recurring_select'] == 'RecurringDailyType') {
                $dateInterval = new \DateInterval('P' . $formData['recurring_sub']['recurrenceDay'] . 'D');

                $day = clone $startDate;
                $day->add($dateInterval);
                while($day <= $endDate) {
                    $recurringDateArray[] = clone $day;

                    $day->add($dateInterval);
                }
                $recurringPatternArray['recurring_sub']['recurrenceDay'] = $formData['recurring_sub']['recurrenceDay'];

                unset($dateInterval);

            // weekly recurring
            } else if($formData['recurring_select'] == 'RecurringWeeklyType') {
                // go back to last monday(if day is not monday)
                $monday = clone $startDate;
                if($startDate->format('w') == 0) {
                    $monday->sub(new \DateInterval('P6D'));
                } else {
                    $monday->sub(new \DateInterval('P' . ($startDate->format('w')-1) . 'D'));
                }

                while($monday <= $endDate) {
                    foreach($formData['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                        if($day == 'monday') {
                            $addonDays = 0;
                        } elseif($day == 'tuesday') {
                            $addonDays = 1;
                        } elseif($day == 'wednesday') {
                            $addonDays = 2;
                        } elseif($day == 'thursday') {
                            $addonDays = 3;
                        } elseif($day == 'friday') {
                            $addonDays = 4;
                        } elseif($day == 'saturday') {
                            $addonDays = 5;
                        } elseif($day == 'sunday') {
                            $addonDays = 6;
                    }

                    $temp = clone $monday;
                    $temp->add(new \DateInterval('P' . $addonDays . 'D'));

                    if($temp > $startDate && $temp <= $endDate) {
                        $recurringDateArray[] = $temp;
                    }

                    unset($temp);
                }

                $monday->add(new \DateInterval('P' . $formData['recurring_sub']['recurrenceWeek'] . 'W'));
            }
            $recurringPatternArray['recurring_sub']['recurrenceDaysOfWeek'] = $formData['recurring_sub']['recurrenceDaysOfWeek'];
            $recurringPatternArray['recurring_sub']['recurrenceWeek'] = $formData['recurring_sub']['recurrenceWeek'];

            unset($monday);

            // monthly recurring
            } else if($formData['recurring_select'] == 'RecurringMonthlyType') {
                $monthCount = $startDate->format('m');
                $yearCount = $startDate->format('Y');
                $monthToAdd = $formData['recurring_sub']['recurrenceMonth'] % 12;
                $yearsToAdd = ($formData['recurring_sub']['recurrenceMonth'] - $monthToAdd) / 12;
                $month = new \DateTime($yearCount . '-' . $monthCount . '-01');

                while($month <= $endDate) {
                    $datesOccurenceArray = array();

                    // loop through every day of this month
                    for($index = 0; $index < $month->format('t'); $index++) {
                        $temp = clone $month;
                        $temp->add(new \DateInterval('P' . $index . 'D'));

                        // if the actual day is a correct week day, add it to possible dates
                        $weekDay = $temp->format('w');
                        if($weekDay == $formData['recurring_sub']['recurrenceDayOfMonth']) {
                            $datesOccurenceArray[] = $temp;
                        }

                        unset($temp);
                    }

                    // add only days, that match the right week
                    if($formData['recurring_sub']['recurrenceDayOfMonthInterval'] != 'last') {
                        if($formData['recurring_sub']['recurrenceDayOfMonthInterval'] <= count($datesOccurenceArray)) {
                            if( $datesOccurenceArray[$formData['recurring_sub']['recurrenceDayOfMonthInterval']-1] >= $startDate &&
                                $datesOccurenceArray[$formData['recurring_sub']['recurrenceDayOfMonthInterval']-1] <= $endDate) {
                                $recurringDateArray[] = $datesOccurenceArray[$formData['recurring_sub']['recurrenceDayOfMonthInterval']-1];
                            }
                        }
                    } else {
                        if( $datesOccurenceArray[count($formData['recurring_sub']['recurrenceDayOfMonthInterval'])-1] >= $startDate &&
                            $datesOccurenceArray[count($formData['recurring_sub']['recurrenceDayOfMonthInterval'])-1] <= $endDate) {
                            $recurringDateArray[] = $datesOccurenceArray[count($formData['recurring_sub']['recurrenceDayOfMonthInterval'])-1];
                        }
                    }

                    // go to next month
                    if($monthCount + $monthToAdd > 12) {
                        $monthCount += $monthToAdd - 12;
                        $yearCount += $yearsToAdd + 1;
                    } else {
                        $monthCount += $monthToAdd;
                    }

                    unset($month);
                    $month = new \DateTime($yearCount . '-' . $monthCount . '-01');
                }

                $recurringPatternArray['recurring_sub']['recurrenceMonth'] = $formData['recurring_sub']['recurrenceMonth'];
                $recurringPatternArray['recurring_sub']['recurrenceDayOfMonth'] = $formData['recurring_sub']['recurrenceDayOfMonth'];
                $recurringPatternArray['recurring_sub']['recurrenceDayOfMonthInterval'] = $formData['recurring_sub']['recurrenceDayOfMonthInterval'];

                unset($month);

            // yearly recurring
            } else if($formData['recurring_select'] == 'RecurringYearlyType') {
                $yearCount = $startDate->format('Y');
                $year = new \DateTime($yearCount . '-01-01');
                while($year <= $endDate) {
                    $date = new \DateTime($formData['recurring_sub']['recurrenceDayOfMonth'] . '-' . $formData['recurring_sub']['recurrenceMonthOfYear'] . '-' . $yearCount);
                    if($date > $startDate && $date <= $endDate) {
                        $recurringDateArray[] = $date;
                    }
                    unset($date);

                    unset($year);
                    $yearCount++;
                    $year = new \DateTime($yearCount . '-01-01');
                }

                $recurringPatternArray['recurring_sub']['recurrenceDayOfMonth'] = $formData['recurring_sub']['recurrenceDayOfMonth'];
                $recurringPatternArray['recurring_sub']['recurrenceMonthOfYear'] = $formData['recurring_sub']['recurrenceMonthOfYear'];
            }

            unset($startDate);
            unset($endDate);

            $recurringPatternArray['recurringStartDate'] = $dateItem->getStartingDay();
            $recurringPatternArray['recurringEndDate'] = $formData['recurring_sub']['untilDate']->format('Y-m-d');

            foreach($recurringDateArray as $date) {
                $tempDate = clone $dateItem;
                $tempDate->setItemID('');
                $tempDate->setStartingDay(date('Y-m-d', $date->getTimestamp()));

                if($dateItem->getStartingTime() != '') {
                    $tempDate->setDateTime_start(date('Y-m-d', $date->getTimestamp()) . ' ' . $dateItem->getStartingTime());
                } else {
                    $tempDate->setDateTime_start(date('Y-m-d 00:00:00', $date->getTimestamp()));
                }

                if($dateItem->getEndingDay() != '') {
                    $tempStartingDay = new \DateTime($dateItem->getStartingDay());
                    $tempEndingDay = new \DateTime($dateItem->getEndingDay());

                    $tempDate->setEndingDay(date('Y-m-d', $date->getTimestamp() + ($tempEndingDay->getTimestamp() - $tempStartingDay->getTimestamp())));

                    unset($tempStartingDay);
                    unset($tempEndingDay);

                    if($dateItem->getEndingTime() != '') {
                        $tempDate->setDateTime_end(date('Y-m-d', $date->getTimestamp()) . ' ' . $dateItem->getEndingTime());
                    } else {
                        $tempDate->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                    }
                } else {
                    if($dateItem->getEndingTime() != '')  {
                        $tempDate->setDateTime_end(date('Y-m-d', $date->getTimestamp()) . ' ' . $dateItem->getEndingTime());
                    } else {
                        $tempDate->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                    }
                }
                $tempDate->setRecurrenceId($dateItem->getItemID());
                $tempDate->setRecurrencePattern($recurringPatternArray);
                $tempDate->save();
            }
            $dateItem->setRecurrenceId($dateItem->getItemID());
            $dateItem->setRecurrencePattern($recurringPatternArray);
            $dateItem->save();
        } else {
            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->resetLimits();
            $datesManager->setRecurrenceLimit($dateItem->getRecurrenceId());
            $datesManager->setWithoutDateModeLimit();
            $datesManager->select();
            $datesList = $datesManager->get();
            $tempDate = $datesList->getFirst();
            while($tempDate){
                if(in_array('startingTime',$valuesToChange)){
                    $tempDate->setStartingTime($dateItem->getStartingTime());
                    $tempDate->setDateTime_start(mb_substr($tempDate->getDateTime_start(),0,10) . ' ' . $dateItem->getStartingTime());
                }
                if(in_array('endingTime',$valuesToChange)){
                    $tempDate->setEndingTime($dateItem->getEndingTime());
                    $tempDate->setDateTime_end(mb_substr($tempDate->getDateTime_end(),0,10) . ' ' . $dateItem->getEndingTime());
                }
                if(in_array('place',$valuesToChange)){
                    $tempDate->setPlace($dateItem->getPlace());
                }
                if(in_array('color',$valuesToChange)){
                    $tempDate->setColor($dateItem->getColor());
                }
                //$tempDate->save();
                $tempDate = $datesList->getNext();
            }
        }
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
