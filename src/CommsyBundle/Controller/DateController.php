<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Action\Copy\CopyAction;
use CommsyBundle\Action\Delete\DeleteDate;
use CommsyBundle\Action\Download\DownloadAction;
use DateTime;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\DateType;
use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\DateImportType;

use CommsyBundle\Filter\DateFilterType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use CommsyBundle\Event\CommsyEditEvent;
use CommsyBundle\Entity\Calendars;

/**
 * Class DateController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'date')")
 */
class DateController extends BaseController
{
    /**
     * @Route("/room/{roomId}/date/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'time', Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
        
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->get('date_filter');
        }
        
        // get the date service
        $dateService = $this->get('commsy_legacy.date_service');
        
        if ($dateFilter) {
            $filterForm = $this->createFilterForm($roomItem);
    
            // manually bind values from the request
            $filterForm->submit($dateFilter);
            // set filter conditions on the date manager
            $dateService->setFilterConditions($filterForm);
        } else {
            $dateService->setPastFilter(false);
            $dateService->hideDeactivatedEntries();
        }

        // Correct sort from "date" to "time". Applies only in date rubric.
        if ($sort == 'date') {
            $sort = 'time';
        } else if ($sort == 'date_rev') {
            $sort = 'time_rev';
        }

        // get material list from manager service
        $dates = $dateService->getListDates($roomId, $max, $start, $sort);

        $this->get('session')->set('sortDates', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $allowedActions = array();
        foreach ($dates as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        return [
            'roomId' => $roomId,
            'dates' => $dates,
            'readerList' => $readerList,
            'allowedActions' => $allowedActions,
        ];
    }

    /**
     * @Route("/room/{roomId}/date")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $filterForm = $this->createFilterForm($roomItem);

        // get the date manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in date manager
            $dateService->setFilterConditions($filterForm);
        } else {
            $dateService->setPastFilter(false);
            $dateService->hideDeactivatedEntries();
        }

        $itemsCountArray = $dateService->getCountArray($roomId);

        $usageInfo = false;
        /** @noinspection PhpUndefinedMethodInspection */
        if ($roomItem->getUsageInfoTextForRubricInForm('date') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('date');
            /** @noinspection PhpUndefinedMethodInspection */
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('date');
        }

        // iCal
        $iCal = [
            'show' => false,
            'aboUrl' => $this->generateUrl('commsy_ical_getcontent', [
                'contextId' => $roomId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'exportUrl' => $this->generateUrl('commsy_ical_getcontent', [
                'contextId' => $roomId,
                'export' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($roomItem->isOpenForGuests()) {
            $iCal['show'] = true;
        } else {
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            if ($currentUserItem->isUser()) {
                $iCal['show'] = true;

                $hashManager = $legacyEnvironment->getHashManager();
                $iCalHash = $hashManager->getICalHashForUser($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('commsy_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('commsy_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId, 'external_url' => array('', NULL)));

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        ];
    }

       /**
     * @Route("/room/{roomId}/date/print/{sort}", defaults={"sort" = "none"})
     */
    public function printlistAction($roomId, Request $request, $sort)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $filterForm = $this->createFilterForm($roomItem);

        // get the date manager service
        $dateService = $this->get('commsy_legacy.date_service');
        $numAllDates = $dateService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in date manager
            $dateService->setFilterConditions($filterForm);
        } else {
            $dateService->setPastFilter(false);
        }

        // get date list from manager service
        if ($sort != "none") {
            $dates = $dateService->getListDates($roomId, $numAllDates, 0, $sort);
        }
        elseif ($this->get('session')->get('sortDates')) {
            $dates = $dateService->getListDates($roomId, $numAllDates, 0, $this->get('session')->get('sortDates'));
        }
        else {
            $dates = $dateService->getListDates($roomId, $numAllDates, 0, 'date');
        }

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

        $itemsCountArray = $dateService->getCountArray($roomId);

        $html = $this->renderView('CommsyBundle:date:list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray,
            'dates' => $dates,
            'readerList' => $readerList,
        ]);

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }
    
    /**
     * @Route("/room/{roomId}/date/calendar")
     * @Template()
     */
    public function calendarAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $filterForm = $this->createFilterForm($roomItem, false);

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $dateService->setFilterConditions($filterForm);
        } else {
            $dateService->setPastFilter(false);
        }

        $usageInfo = false;
        /** @noinspection PhpUndefinedMethodInspection */
        if ($roomItem->getUsageInfoTextForRubricInForm('date') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('date');
            /** @noinspection PhpUndefinedMethodInspection */
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('date');
        }

        // iCal
        $iCal = [
            'show' => false,
            'aboUrl' => $this->generateUrl('commsy_ical_getcontent', [
                'contextId' => $roomId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'exportUrl' => $this->generateUrl('commsy_ical_getcontent', [
                'contextId' => $roomId,
                'export' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($roomItem->isOpenForGuests()) {
            $iCal['show'] = true;
        } else {
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            if ($currentUserItem->isUser()) {
                $iCal['show'] = true;

                $hashManager = $legacyEnvironment->getHashManager();
                $iCalHash = $hashManager->getICalHashForUser($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('commsy_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('commsy_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId, 'external_url' => array('', NULL)));

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date',
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->isArchived(),
        ];
    }
    
    /**
     * @Route("/room/{roomId}/date/calendardashboard")
     * @Template()
     */
    public function calendardashboardAction($roomId)
    {
        return [
            'roomId' => $roomId,
            'module' => 'date',
        ];
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'date')")
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

        // mark annotations as read
        $annotationService = $this->get('commsy_legacy.annotation_service');
        $annotationList = $date->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $itemArray = array($date);

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerManager = $legacyEnvironment->getReaderManager();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        /** @var \cs_user_item $current_user */
        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}
		$readerManager->getLatestReaderByUserIDArray($id_array, $date->getItemID());
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

        $alert = null;
        if ($dateService->getDate($itemId)->isLocked()) {
            $translator = $this->get('translator');
            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        } else if ($date->isExternal()) {
            $translator = $this->get('translator');
            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('date is external', array(), 'date');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $markupService = $this->get('commsy_legacy.markup');
        $itemService = $this->get('commsy_legacy.item_service');
        $markupService->addFiles($itemService->getItemFileList($itemId));

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
            'isParticipating' => $date->isParticipant($legacyEnvironment->getCurrentUserItem()),
            'isRecurring' => ($date->getRecurrenceId() != ''),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/events")
     * @throws
     */
    public function eventsAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
        
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->get('date_filter');
        }
        // get the date service
        $dateService = $this->get('commsy_legacy.date_service');

        $startDate = $request->get('start');
        $endDate = $request->get('end');

        if ($dateFilter) {
            $filterForm = $this->createFilterForm($roomItem);
    
            // manually bind values from the request
            $filterForm->submit($dateFilter);

            // set filter conditions on the date manager
            $dateService->setFilterConditions($filterForm);

            if (isset($dateFilter['date-from']['date']) && !empty($dateFilter['date-from']['date'])) {
                $startDate = DateTime::createFromFormat('d.m.Y', $dateFilter['date-from']['date'])->format('Y-m-d 00:00:00');
            }
            if (isset($dateFilter['date-until']['date']) && !empty($dateFilter['date-until']['date'])) {
                $endDate = DateTime::createFromFormat('d.m.Y', $dateFilter['date-until']['date'])->format('Y-m-d 23:59:59');
            }
        } else {
            $dateService->setPastFilter(true);
        }

        $listDates = $dateService->getCalendarEvents($roomId, $startDate, $endDate);

        $events = array();
        foreach ($listDates as $date) {
            if (!$date->isWholeDay()) {
                $start = $date->getStartingDay();
                if ($date->getStartingTime() != '') {
                    $start .= ' ' . $date->getStartingTime();
                }
                $end = $date->getEndingDay();
                if ($end == '') {
                    $end = $date->getStartingDay();
                }
                if ($date->getEndingTime() != '') {
                    $end .= ' ' . $date->getEndingTime();
                }
            } else {
                $start = $date->getStartingDay().' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay().' 00:00:00');
                $endDateTime->modify('+1 day');
                $end = $endDateTime->format('Y-m-d H:i:s');
            }
            
            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = array();
            while ($participantItem) {
                $participantsNameArray[] = $participantItem->getFullname();
                $participantItem = $participantsList->getNext();    
            }
            $participantsDisplay = '';
            if (!empty($participantsNameArray)) {
                $participantsDisplay = implode(', ', $participantsNameArray);
            }

            $color = $date->getCalendar()->getColor();

            $textColor = '#ffffff';
            if ($date->getCalendar()->hasLightColor()) {
                $textColor = '#444444';
            }

            $borderColor = $date->getCalendar()->getColor();
            if ($date->getCalendar()->hasLightColor()) {
                $borderColor = '#888888';
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
                    if (isset($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'])) {
                        foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                            $daysOfWeek[] = $translator->trans($day, array(), 'date');
                        }
                    }
                    $recurringDescription = $translator->trans('weeklyDescription', array('%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'], '%daysOfWeek%' => implode(', ', $daysOfWeek), '%date%' => $endDate->format('d.m.Y')), 'date');
                } else if ($recurrencePattern['recurring_select'] == 'RecurringMonthlyType') {
                    $tempDayOfMonthInterval = $translator->trans('first', array(), 'date');
                    if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'])) {
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
                    }
                    if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'])) {
                        $recurringDescription = $translator->trans('monthlyDescription', array('%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'], '%day%' => $tempDayOfMonthInterval, '%dayOfWeek%' => $translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], array(), 'date'), '%date%' => $endDate->format('d.m.Y')), 'date');
                    }
                } else if ($recurrencePattern['recurring_select'] == 'RecurringYearlyType') {
                    $recurringDescription = $translator->trans('yearlyDescription', array('%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], '%month%' => $translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'], array(), 'date'), '%date%' => $endDate->format('d.m.Y')), 'date');
                }
            }

            $events[] = array('itemId' => $date->getItemId(),
                'title' => html_entity_decode($date->getTitle()),
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'calendar' => $date->getCalendar()->getTitle(),
                'editable' => $date->isPublic(),
                'description' => $date->getDateDescription(),
                'place' => $date->getPlace(),
                'participants' => $participantsDisplay,
                'contextId' => '',
                'contextTitle' => '',
                'recurringDescription' => $recurringDescription,
                'textColor' => $textColor,
                'borderColor' => $borderColor,
                'allDay' => $date->isWholeDay(),
            );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/eventsdashboard")
     */
    public function eventsdashboardAction($roomId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $dateService = $this->get('commsy_legacy.date_service');
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $user = $legacyEnvironment->getCurrentUserItem();
        $userList = $user->getRelatedUserList()->to_array();

        $listDates = array();
        foreach ($userList as $tempUser) {
            /** @var \cs_user_item $tempUser */
            if ($tempUser->getStatus() >= 2) {
                $listDates = array_merge($listDates, $dateService->getCalendarEvents($tempUser->getContextId(), $_GET['start'], $_GET['end']));
            }
        }

        $events = array();
        foreach ($listDates as $date) {
            /** @var \cs_dates_item $date */
            if (!$date->isWholeDay()) {
                $start = $date->getStartingDay();
                if ($date->getStartingTime() != '') {
                    $start .= ' ' . $date->getStartingTime();
                }
                $end = $date->getEndingDay();
                if ($end == '') {
                    $end = $date->getStartingDay();
                }
                if ($date->getEndingTime() != '') {
                    $end .= ' ' . $date->getEndingTime();
                }
            } else {
                $start = $date->getStartingDay().' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay().' 00:00:00');
                $endDateTime->modify('+1 day');
                $end = $endDateTime->format('Y-m-d H:i:s');
            }
            
            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = array();
            while ($participantItem) {
                $participantsNameArray[] = $participantItem->getFullname();
                $participantItem = $participantsList->getNext();    
            }
            $participantsDisplay = '';
            if (!empty($participantsNameArray)) {
                $participantsDisplay = implode(', ', $participantsNameArray);
            }

            $color = $date->getCalendar()->getColor();

            $textColor = '#ffffff';
            if ($date->getCalendar()->hasLightColor()) {
                $textColor = '#444444';
            }

            $borderColor = $date->getCalendar()->getColor();
            if ($date->getCalendar()->hasLightColor()) {
                $borderColor = '#888888';
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
            
            $context = $itemService->getTypedItem($date->getContextId());

            $events[] = array('itemId' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $color,
                              'calendar' => $date->getCalendar()->getTitle(),
                              'editable' => $date->isPublic(),
                              'description' => $date->getDateDescription(),
                              'place' => $date->getPlace(),
                              'participants' => $participantsDisplay,
                              'contextId' => $context->getItemId(),
                              'contextTitle' => $context->getTitle(),
                              'recurringDescription' => $recurringDescription,
                              'textColor' => $textColor,
                              'borderColor' => $borderColor,
                              'allDay' => $date->isWholeDay(),
                             );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/create/{dateDescription}")
     */
    public function createAction($roomId, $dateDescription)
    {
        $dateService = $this->get('commsy_legacy.date_service');

        // create new material item
        $dateItem = $dateService->getNewDate();
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

        $startTimeArray = explode('T', $requestContent->start);
        $endTimeArray = explode('T', $requestContent->end);

        $date->setStartingDay($startTimeArray[0]);

        if (isset($startTimeArray[1])) {
            $date->setStartingTime($startTimeArray[1]);
        } else {
            $date->setStartingTime('');
        }

        $date->setDateTime_start(str_ireplace('T', ' ', $requestContent->start));

        if (!$requestContent->allDay) {
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

            if ($requestContent->end != '') {
                $date->setDateTime_end(str_ireplace('T', ' ', $requestContent->end));
            }
        } else {
            $endDateTime = new \DateTime($requestContent->end);
            $endDateTime->modify('-1 day');

            $date->setEndingDay($endDateTime->format('Y-m-d'));

            $date->setEndingTime($endDateTime->format('23:59:59'));

            if ($requestContent->end != '') {
                $date->setDateTime_end($endDateTime->format('Y-m-d 23:59:59'));
            }
        }

        $date->save();

        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $translator->trans('date changed', [], 'date');

        $start = $date->getStartingDay();
        if ($date->getStartingTime() != '') {
            $start .= 'T' . $date->getStartingTime() . 'Z';
        }
        $end = $date->getEndingDay();
        if ($end == '') {
            $end = $date->getStartingDay();
        }
        if ($date->getEndingTime() != '') {
            $end .= 'T' . $date->getEndingTime() . 'Z';
        }

        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => [
                'itemId' => $date->getItemId(),
                'title' => $date->getTitle(),
                'start' => $start,
                'end' => $end,
                'color' => $date->getColor(),
                'editable' => $date->isPublic(),
                'description' => $date->getDateDescription(),
                'place' => $date->getPlace(),
                'participants' => '',
            ],
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'date')")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $dateService = $this->get('commsy_legacy.date_service');
        $transformer = $this->get('commsy_legacy.transformer.date');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        // get date from DateService
        $dateItem = $dateService->getDate($itemId);
        if (!$dateItem) {
            throw $this->createNotFoundException('No date found for id ' . $itemId);
        }

        $itemController = $this->get('commsy.item_controller');
        $formData = $transformer->transform($dateItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['draft'] = $isDraft;

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId));
        $calendarsOptions = [];
        $calendarsOptionsAttr = [];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = ['title' => $calendar->getTitle(), 'color' => $calendar->getColor(), 'hasLightColor' => $calendar->hasLightColor()];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;

        $formOptions = array(
            'action' => $this->generateUrl('commsy_date_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '['.$translator->trans('insert title').']',
            'calendars' => $calendarsOptions,
            'calendarsAttr' => $calendarsOptionsAttr,
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service'))
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('commsy_hashtag_add', ['roomId' => $roomId])
            ],
        );
        if ($dateItem->getRecurrencePattern() != '') {
            $formOptions['attr']['unsetRecurrence'] = true;
        }
        $form = $this->createForm(DateType::class, $formData, $formOptions);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $formData = $form->getData();
            if ($saveType == 'save') {
                $valuesBeforeChange = array();
                $valuesBeforeChange['startingTime'] = $dateItem->getStartingTime();
                $valuesBeforeChange['endingTime'] = $dateItem->getEndingTime();
                $valuesBeforeChange['place'] = $dateItem->getPlace();
                $valuesBeforeChange['color'] = $dateItem->getColor();

                $dateItem = $transformer->applyTransformation($dateItem, $formData);

                // update modifier
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $dateItem->setTagListByID($formData['category_mapping']['categories']);
                }
                if ($hashtagsMandatory) {
                    $dateItem->setBuzzwordListByID($formData['hashtag_mapping']['hashtags']);
                }

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
                if (!$dateItem->getDateTime_recurrence()) {
                    $dateItem->setDateTime_recurrence($dateItem->getDateTime_start());
                }
                $dateItem = $transformer->applyTransformation($dateItem, $formData);
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $dateItem->save();
            } else if ($saveType == 'saveAllDates') {
                $dateService = $this->get('commsy_legacy.date_service');
                $datesArray = $dateService->getRecurringDates($dateItem->getContextId(), $dateItem->getRecurrenceId());
                $dateItem = $transformer->applyTransformation($dateItem, $formData);
                $dateItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $dateItem->save();
                foreach ($datesArray as $tempDate) {
                    $tempDate->setTitle($dateItem->getTitle());
                    $tempDate->setPublic((int)$dateItem->isPublic());
                    $tempDate->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $tempDate->setColor($dateItem->getColor());
                    $tempDate->setCalendarId($dateItem->getCalendarId());
                    $tempDate->setWholeDay($dateItem->isWholeDay());
                    $tempDate->setStartingTime($dateItem->getStartingTime());
                    $tempDate->setEndingTime($dateItem->getEndingTime());
                    $tempDate->setPlace($dateItem->getPlace());
                    $tempDate->save();
                    
                    // mark as read and noticed by creator
                    $reader_manager = $legacyEnvironment->getReaderManager();
                    $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                    $noticed_manager = $legacyEnvironment->getNoticedManager();
                    $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());
                }
            }
            return $this->redirectToRoute('commsy_date_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($dateItem));

        return array(
            'form' => $form->createView(),
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
            'withRecurrence' => $dateItem->getRecurrencePattern() != '',
            'date' => $dateItem,
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
     * @Route("/room/{roomId}/date/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'date')")
     */
    public function saveAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $dateService = $this->get('commsy_legacy.date_service');
        
        $date = $dateService->getDate($itemId);
        
        $itemArray = array($date);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        
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
        /** @var \cs_user_item $current_user */
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

        $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($date));

        return array(
            'roomId' => $roomId,
            'item' => $date,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }
    
    function saveRecurringDates($dateItem, $isNewRecurring, $valuesToChange, $formData)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_dates_item $dateItem */

        if($isNewRecurring) {
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
                        $weekDay = $temp->format('l'); // 'l' returns the full textual representation of the date's day of week (e.g. "Tuesday")

                        // NOTE: for monthly recurring dates, `recurrenceDayOfMonth` contains the day of week (e.g. "tuesday")
                        // instead of the numeric day number (like "26") as is the case for yearly recurring dates
                        if(strtolower($weekDay) === $formData['recurring_sub']['recurrenceDayOfMonth']) {
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
                // prevent duplicate date entry
                if (date('Y-m-d', $date->getTimestamp()) === $dateItem->getStartingDay()) {
                    continue;
                }

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

                // mark as read and noticed by creator
                $reader_manager = $legacyEnvironment->getReaderManager();
                $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                $noticed_manager = $legacyEnvironment->getNoticedManager();
                $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());

            }
            $dateItem->setRecurrenceId($dateItem->getItemID());
            $dateItem->setRecurrencePattern($recurringPatternArray);
            $dateItem->save();
        } else {
            // TODO: remove this else block if (as suspected) it is dead code that doesn't get executed anymore
            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->resetLimits();
            $datesManager->setRecurrenceLimit($dateItem->getRecurrenceId());
            $datesManager->setWithoutDateModeLimit();
            $datesManager->select();


            $datesList = $datesManager->get();

            /** @var \cs_dates_item $tempDate */
            $tempDate = $datesList->getFirst();
            while($tempDate) {
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

                // mark as read and noticed by creator
                $reader_manager = $legacyEnvironment->getReaderManager();
                $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                $noticed_manager = $legacyEnvironment->getNoticedManager();
                $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());

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

        $readerManager = $legacyEnvironment->getReaderManager();

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

        $html = $this->renderView('CommsyBundle:date:detail_print.html.twig', [
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

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/participate")
     */
    public function participateAction($roomId, $itemId)
    {
        $dateService = $this->get('commsy_legacy.date_service');
        $date = $dateService->getDate($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        
        if (!$date->isParticipant($legacyEnvironment->getCurrentUserItem())) {
            $date->addParticipant($currentUser);
        } else {
            $date->removeParticipant($currentUser);
        }

        return $this->redirectToRoute('commsy_date_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }

    /**
     * @Route("/room/{roomId}/date/import")
     * @Template()
     */
    public function importAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $translator = $this->get('translator');

        $formData = [];
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId));
        $calendarsOptions = [$translator->trans('new calendar', [], 'date') => 'new'];
        $calendarsOptionsAttr = [['title' => $translator->trans('new calendar'), 'color' => '#ffffff', 'hasLightColor' => true]];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = ['title' => $calendar->getTitle(), 'color' => $calendar->getColor(), 'hasLightColor' => $calendar->hasLightColor()];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;
        $formData['files'] = [];

        $formOptions = array(
            'action' => $this->generateUrl('commsy_date_import', array(
                'roomId' => $roomId,
            )),
            'calendars' => $calendarsOptions,
            'calendarsAttr' => $calendarsOptionsAttr,
            'uploadUrl' => $this->generateUrl('commsy_date_importupload', [
                'roomId' => $roomId,
                'itemId' => null
            ]),
        );

        $form = $this->createForm(DateImportType::class, $formData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $files = $formData['files'];

            if (!empty($files)) {
                // get calendar object or create new
                $calendarsService = $this->get('commsy.calendars_service');
                if ($formData['calendar'] != 'new') {
                    $calendars = $calendarsService->getCalendar($formData['calendar']);
                    if (isset($calendars[0])) {
                        $calendar = $calendars[0];
                    }
                } else {
                    $calendar = new Calendars();

                    $calendarTitle = $formData['calendartitle'];
                    if ($calendarTitle == '') {
                        $calendarTitle = $translator->trans('new calendar');
                    }
                    $calendar->setTitle($calendarTitle);

                    $calendar->setContextId($roomId);
                    $calendar->setCreatorId($legacyEnvironment->getCurrentUserId());

                    $calendarColor = $formData['calendarcolor'];
                    if ($calendarColor == '') {
                        $calendarColor = '#ffffff';
                    }
                    $calendar->setColor($calendarColor);

                    $calendar->setSynctoken(0);
                    $em->persist($calendar);
                    $em->flush();
                }

                $kernelRootDir = $this->getParameter('kernel.root_dir');
                $fileData = array();
                foreach ($files as $file) {
                    $fileHandle = fopen($kernelRootDir . '/../var/temp/' . $file->getFileId(), 'r');
                    if ($fileHandle) {
                        $fileData[] = $fileHandle;
                    }
                }

                if (!empty($fileData)) {
                    $calendarsService->importEvents($fileData, $calendar);
                }

                return $this->redirectToRoute('commsy_date_list', array('roomId' => $roomId));
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/date/importupload")
     */
    public function importUploadAction($roomId, Request $request)
    {
        $response = new JsonResponse();

        $kernelRootDir = $this->getParameter('kernel.root_dir');

        $files = $request->files->all();

        $responseData = array();
        foreach ($files['files'] as $file) {
            if (stristr($file->getMimeType(), 'text/calendar')) {
                $filename = $roomId . '_' . date('Ymdhis') . '_' . $file->getClientOriginalName();
                if ($file->move($kernelRootDir . '/../var/temp/', $filename)) {
                    $responseData[$filename] = $file->getClientOriginalName();
                }
            }
        }

        return $response->setData([
            'fileIds' => $responseData,
        ]);
    }

    /**
     * @Route("/room/{roomId}/date/download")
     * @throws \Exception
     */
    public function downloadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(DownloadAction::class);
        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/date/xhr/markread", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrMarkReadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);

    }

    /**
     * @Route("/room/{roomId}/date/xhr/copy", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrCopyAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/date/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $recurring = false;
        if ($request->request->has('payload')) {
            $payload = $request->request->get('payload');

            $recurring = isset($payload['recurring']) ?? false;
        }

        $action = $this->get('commsy.action.delete.date');
        $strategy = $action->getStrategy();

        if ($strategy instanceof DeleteDate) {
            $strategy->setRecurring($recurring);
            $strategy->setDateMode($room->getDatesPresentationStatus());
        }

        return $action->execute($room, $items);
    }

    /**
     * @param \cs_room_item $room
     * @param bool $hidePastDates Default state for hide past dates filter
     * @return FormInterface
     */
    private function createFilterForm($room, $hidePastDates = true)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => true,
            'hide-past-dates' => $hidePastDates,
        ];

        return $this->createForm(DateFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('commsy_date_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_dates_item[]
     */
    protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        $dateService = $this->get('commsy_legacy.date_service');

        if ($selectAll) {
            if ($request->query->has('date_filter')) {
                $currentFilter = $request->query->get('date_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $dateService->setFilterConditions($filterForm);
            } else {
                $dateService->setPastFilter(false);
                $dateService->hideDeactivatedEntries();
            }

            return $dateService->getListDates($roomItem->getItemID());
        } else {
            return $dateService->getDatesById($roomItem->getItemID(), $itemIds);
        }
    }
}
