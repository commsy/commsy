<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteDate;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\MarkReadAction;
use App\Entity\Calendars;
use App\Event\CommsyEditEvent;
use App\Filter\DateFilterType;
use App\Form\DataTransformer\DateTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\DateImportType;
use App\Form\Type\DateType;
use App\Services\CalendarsService;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\DateService;
use App\Utils\LabelService;
use App\Utils\TopicService;
use cs_dates_item;
use cs_room_item;
use cs_user_item;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DateController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'date')")
 */
class DateController extends BaseController
{

    /**
     * @var DateService
     */
    private DateService $dateService;
    private SessionInterface $session;

    /**
     * @required
     * @param DateService $dateService
     */
    public function setDateService(DateService $dateService): void
    {
        $this->dateService = $dateService;
    }


    /**
     * @required
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }



    /**
     * @Route("/room/{roomId}/date/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'time'
    ) {
        $roomItem = $this->getRoom($roomId);

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->get('date_filter');
        }

        if ($dateFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($dateFilter);
            // set filter conditions on the date manager
            $this->dateService->setFilterConditions($filterForm);
        } else {
            $this->dateService->setPastFilter(false);
            $this->dateService->hideDeactivatedEntries();
        }

        // Correct sort from "date" to "time". Applies only in date rubric.
        if ($sort == 'date') {
            $sort = 'time';
        } else {
            if ($sort == 'date_rev') {
                $sort = 'time_rev';
            }
        }

        // get material list from manager service
        $dates = $this->dateService->getListDates($roomId, $max, $start, $sort);

        $this->session->set('sortDates', $sort);

        $readerList = array();
        $allowedActions = array();
        foreach ($dates as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
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
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->getRoom($roomId);

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in date manager
            $this->dateService->setFilterConditions($filterForm);
        } else {
            $this->dateService->setPastFilter(false);
            $this->dateService->hideDeactivatedEntries();
        }

        $itemsCountArray = $this->dateService->getCountArray($roomId);

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
            'aboUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'exportUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
                'export' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($roomItem->isOpenForGuests()) {
            $iCal['show'] = true;
        } else {
            $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

            if ($currentUserItem->isUser()) {
                $iCal['show'] = true;

                $hashManager = $this->legacyEnvironment->getHashManager();
                $iCalHash = $hashManager->getICalHashForUser($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId, 'external_url' => array('', null)));

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->isArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
        ];
    }

    /**
     * @Route("/room/{roomId}/date/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param PrintService $printService
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ) {
        $roomItem = $this->getRoom($roomId);
        $filterForm = $this->createFilterForm($roomItem);
        $numAllDates = $this->dateService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in date manager
            $this->dateService->setFilterConditions($filterForm);
        } else {
            $this->dateService->setPastFilter(false);
        }

        // get date list from manager service
        if ($sort != "none") {
            $dates = $this->dateService->getListDates($roomId, $numAllDates, 0, $sort);
        } elseif ($this->session->get('sortDates')) {
            $dates = $this->dateService->getListDates($roomId, $numAllDates, 0,
                $this->session->get('sortDates'));
        } else {
            $dates = $this->dateService->getListDates($roomId, $numAllDates, 0, 'date');
        }

        $readerList = array();
        foreach ($dates as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }
        }

        $itemsCountArray = $this->dateService->getCountArray($roomId);

        $html = $this->renderView('date/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'date',
            'itemsCountArray' => $itemsCountArray,
            'dates' => $dates,
            'readerList' => $readerList,
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/date/calendar")
     * @Template()
     * @param Request $request
     * @param DateService $dateService
     * @param int $roomId
     * @return array
     */
    public function calendarAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->getRoom($roomId);
        $filterForm = $this->createFilterForm($roomItem, false);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $this->dateService->setFilterConditions($filterForm);
        } else {
            $this->dateService->setPastFilter(false);
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
            'aboUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'exportUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
                'export' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($roomItem->isOpenForGuests()) {
            $iCal['show'] = true;
        } else {
            $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

            if ($currentUserItem->isUser()) {
                $iCal['show'] = true;

                $hashManager = $this->legacyEnvironment->getHashManager();
                $iCalHash = $hashManager->getICalHashForUser($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId, 'external_url' => array('', null)));

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date',
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->isArchived(),
            'defaultView' => ($roomItem->getDatesPresentationStatus() === 'calendar')? 'agendaWeek': 'month'
        ];
    }

    /**
     * @Route("/room/{roomId}/date/calendardashboard")
     * @Template()
     * @param int $roomId
     * @return array
     */
    public function calendardashboardAction(
        int $roomId
    ) {
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
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param CategoryService $categoryService
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        AnnotationService $annotationService,
        CategoryService $categoryService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $date = $this->dateService->getDate($itemId);

        $item = $date;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $this->legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $itemArray = array($date);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerManager = $this->legacyEnvironment->getReaderManager();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        /** @var cs_user_item $current_user */
        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $date->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $date->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $dateCategories = $date->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $alert = null;
        if ($this->dateService->getDate($itemId)->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        } else {
            if ($date->isExternal()) {
                $alert['type'] = 'warning';
                $alert['content'] = $this->translator->trans('date is external', array(), 'date');
            }
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId,
            $this->dateService->getDate($itemId)->getItemId(), null, null);

        return array(
            'roomId' => $roomId,
            'date' => $this->dateService->getDate($itemId),
            'amountAnnotations' => sizeof($amountAnnotations),
            'readerList' => $readerList,
            'modifierList' => $modifierList,
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'annotationForm' => $form->createView(),
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
            'draft' => $this->itemService->getItem($itemId)->isDraft(),
            'showCategories' => $current_context->withTags(),
            'showHashtags' => $current_context->withBuzzwords(),
            'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'showAssociations' => $current_context->isAssociationShowExpanded(),
            'buzzExpanded' => $current_context->isBuzzwordShowExpanded(),
            'catzExpanded' => $current_context->isTagsShowExpanded(),
            'roomCategories' => $categories,
            'isParticipating' => $date->isParticipant($this->legacyEnvironment->getCurrentUserItem()),
            'isRecurring' => ($date->getRecurrenceId() != ''),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }

    /**
     * @Route("/room/{roomId}/date/events")
     * @param Request $request
     * @param int $roomId
     * @return JsonResponse
     * @throws Exception
     */
    public function eventsAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->getRoom($roomId);

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->get('date_filter');
        }

        $startDate = $request->get('start');
        $endDate = $request->get('end');

        if ($dateFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($dateFilter);

            // set filter conditions on the date manager
            $this->dateService->setFilterConditions($filterForm);

            if (isset($dateFilter['date-from']['date']) && !empty($dateFilter['date-from']['date'])) {
                $startDate = DateTime::createFromFormat('d.m.Y',
                    $dateFilter['date-from']['date'])->format('Y-m-d 00:00:00');
            }
            if (isset($dateFilter['date-until']['date']) && !empty($dateFilter['date-until']['date'])) {
                $endDate = DateTime::createFromFormat('d.m.Y',
                    $dateFilter['date-until']['date'])->format('Y-m-d 23:59:59');
            }
        } else {
            $this->dateService->setPastFilter(true);
        }

        $listDates = $this->dateService->getCalendarEvents($roomId, $startDate, $endDate);

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
                $start = $date->getStartingDay() . ' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay() . ' 00:00:00');
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

                $recurrencePattern = $date->getRecurrencePattern();

                if (isset($recurrencePattern['recurringEndDate'])) {
                    $endDate = new \DateTime($recurrencePattern['recurringEndDate']);
                }

                if ($recurrencePattern['recurring_select'] == 'RecurringDailyType') {
                    $recurringDescription = $this->translator->trans('dailyDescription', array(
                        '%day%' => $recurrencePattern['recurring_sub']['recurrenceDay'],
                        '%date%' => $endDate->format('d.m.Y')
                    ), 'date');
                } else {
                    if ($recurrencePattern['recurring_select'] == 'RecurringWeeklyType') {
                        $daysOfWeek = array();
                        if (isset($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'])) {
                            foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                                $daysOfWeek[] = $this->translator->trans($day, array(), 'date');
                            }
                        }
                        $recurringDescription = $this->translator->trans('weeklyDescription', array(
                            '%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'],
                            '%daysOfWeek%' => implode(', ', $daysOfWeek),
                            '%date%' => $endDate->format('d.m.Y')
                        ), 'date');
                    } else {
                        if ($recurrencePattern['recurring_select'] == 'RecurringMonthlyType') {
                            $tempDayOfMonthInterval = $this->translator->trans('first', array(), 'date');
                            if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'])) {
                                if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 2) {
                                    $tempDayOfMonthInterval = $this->translator->trans('second', array(), 'date');
                                } else {
                                    if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 3) {
                                        $tempDayOfMonthInterval = $this->translator->trans('third', array(), 'date');
                                    } else {
                                        if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 4) {
                                            $tempDayOfMonthInterval = $this->translator->trans('fourth', array(),
                                                'date');
                                        } else {
                                            if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 5) {
                                                $tempDayOfMonthInterval = $this->translator->trans('fifth', array(),
                                                    'date');
                                            } else {
                                                if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 'last') {
                                                    $tempDayOfMonthInterval = $this->translator->trans('last', array(),
                                                        'date');
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'])) {
                                $recurringDescription = $this->translator->trans('monthlyDescription', array(
                                    '%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'],
                                    '%day%' => $tempDayOfMonthInterval,
                                    '%dayOfWeek%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                        array(), 'date'),
                                    '%date%' => $endDate->format('d.m.Y')
                                ), 'date');
                            }
                        } else {
                            if ($recurrencePattern['recurring_select'] == 'RecurringYearlyType') {
                                $recurringDescription = $this->translator->trans('yearlyDescription', array(
                                    '%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                    '%month%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'],
                                        array(), 'date'),
                                    '%date%' => $endDate->format('d.m.Y')
                                ), 'date');
                            }
                        }
                    }
                }
            }

            $events[] = array(
                'itemId' => $date->getItemId(),
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
     * @return JsonResponse
     * @throws Exception
     */
    public function eventsdashboardAction()
    {

        $user = $this->legacyEnvironment->getCurrentUserItem();
        $userList = $user->getRelatedUserList()->to_array();

        $listDates = array();
        foreach ($userList as $tempUser) {
            /** @var cs_user_item $tempUser */
            if ($tempUser->getStatus() >= 2) {
                $listDates = array_merge($listDates,
                    $this->dateService->getCalendarEvents($tempUser->getContextId(), $_GET['start'], $_GET['end']));
            }
        }

        $events = array();
        foreach ($listDates as $date) {
            /** @var cs_dates_item $date */
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
                $start = $date->getStartingDay() . ' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay() . ' 00:00:00');
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
                $recurrencePattern = $date->getRecurrencePattern();

                if (isset($recurrencePattern['recurringEndDate'])) {
                    $endDate = new \DateTime($recurrencePattern['recurringEndDate']);
                }

                if ($recurrencePattern['recurring_select'] == 'RecurringDailyType') {
                    $recurringDescription = $this->translator->trans('dailyDescription', array(
                        '%day%' => $recurrencePattern['recurring_sub']['recurrenceDay'],
                        '%date%' => $endDate->format('d.m.Y')
                    ), 'date');
                } else {
                    if ($recurrencePattern['recurring_select'] == 'RecurringWeeklyType') {
                        $daysOfWeek = array();
                        foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                            $daysOfWeek[] = $this->translator->trans($day, array(), 'date');
                        }
                        $recurringDescription = $this->translator->trans('weeklyDescription', array(
                            '%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'],
                            '%daysOfWeek%' => implode(', ', $daysOfWeek),
                            '%date%' => $endDate->format('d.m.Y')
                        ), 'date');
                    } else {
                        if ($recurrencePattern['recurring_select'] == 'RecurringMonthlyType') {
                            $tempDayOfMonthInterval = $this->translator->trans('first', array(), 'date');
                            if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 2) {
                                $tempDayOfMonthInterval = $this->translator->trans('second', array(), 'date');
                            } else {
                                if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 3) {
                                    $tempDayOfMonthInterval = $this->translator->trans('third', array(), 'date');
                                } else {
                                    if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 4) {
                                        $tempDayOfMonthInterval = $this->translator->trans('fourth', array(), 'date');
                                    } else {
                                        if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 5) {
                                            $tempDayOfMonthInterval = $this->translator->trans('fifth', array(),
                                                'date');
                                        } else {
                                            if ($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'] == 'last') {
                                                $tempDayOfMonthInterval = $this->translator->trans('last', array(),
                                                    'date');
                                            }
                                        }
                                    }
                                }
                            }
                            $recurringDescription = $this->translator->trans('monthlyDescription', array(
                                '%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'],
                                '%day%' => $tempDayOfMonthInterval,
                                '%dayOfWeek%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                    array(), 'date'),
                                '%date%' => $endDate->format('d.m.Y')
                            ), 'date');
                        } else {
                            if ($recurrencePattern['recurring_select'] == 'RecurringYearlyType') {
                                $recurringDescription = $this->translator->trans('yearlyDescription', array(
                                    '%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                    '%month%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'],
                                        array(), 'date'),
                                    '%date%' => $endDate->format('d.m.Y')
                                ), 'date');
                            }
                        }
                    }
                }
            }

            $context = $this->itemService->getTypedItem($date->getContextId());

            $events[] = array(
                'itemId' => $date->getItemId(),
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
     * @param $roomId
     * @param $dateDescription
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'date')")
     */
    public function createAction(
        int $roomId,
        $dateDescription
    ) {
        // create new material item
        $dateItem = $this->dateService->getNewDate();
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
            $month = '0' . $month;
        }
        $day = $dateDescriptionArray['day'];
        if ($day < 10) {
            $day = '0' . $day;
        }
        $hour = $dateDescriptionArray['hour'];
        if ($hour < 10) {
            $hour = '0' . $hour;
        }
        $minute = $dateDescriptionArray['minute'];
        if ($minute < 10) {
            $minute = '0' . $minute;
        }
        $second = $dateDescriptionArray['second'];
        if ($second < 10) {
            $second = '0' . $second;
        }

        $dateItem->setStartingDay($year . '-' . $month . '-' . $day);
        $dateItem->setStartingTime($hour . ':' . $minute . ':' . $second);

        $dateItem->setDateTime_start($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);
        $dateItem->setDateTime_end($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);

        $dateItem->save();

        return $this->redirectToRoute('app_date_detail',
            array('roomId' => $roomId, 'itemId' => $dateItem->getItemId()));
    }

    /**
     * @Route("/room/{roomId}/date/{itemId}/calendaredit")
     * @param Request $request
     * @param int $itemId
     * @return JsonResponse
     * @throws Exception
     */
    public function calendareditAction(
        Request $request,
        int $itemId
    ) {
        $date = $this->dateService->getDate($itemId);

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

        // update modifier
        $date->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

        $date->save();

        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->trans('date changed',
                [], 'date');

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
     * @param Request $request
     * @param CategoryService $categoryService
     * @param DateTransformer $transformer
     * @param ItemController $itemController
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        CategoryService $categoryService,
        DateTransformer $transformer,
        ItemController $itemController,
        LabelService $labelService,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        // get date from DateService
        $dateItem = $this->dateService->getDate($itemId);
        if (!$dateItem) {
            throw $this->createNotFoundException('No date found for id ' . $itemId);
        }

        $formData = $transformer->transform($dateItem);
        $formData['language'] = $this->legacyEnvironment->getCurrentContextItem()->getLanguage();
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId,
            $this->legacyEnvironment);
        $formData['draft'] = $isDraft;

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId));
        $calendarsOptions = [];
        $calendarsOptionsAttr = [];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = [
                    'title' => $calendar->getTitle(),
                    'color' => $calendar->getColor(),
                    'hasLightColor' => $calendar->hasLightColor()
                ];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;

        $formOptions = array(
            'action' => $this->generateUrl('app_date_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
            'calendars' => $calendarsOptions,
            'calendarsAttr' => $calendarsOptionsAttr,
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $categoryService),
                'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId])
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
            'room' => $current_context,
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
                $dateItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($form->has('category_mapping')) {
                    $categoryIds = $formData['category_mapping']['categories'] ?? [];

                    if (isset($formData['category_mapping']['newCategory'])) {
                        $newCategoryTitle = $formData['category_mapping']['newCategory'];
                        $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                        $categoryIds[] = $newCategory->getItemID();
                    }

                    if (!empty($categoryIds)) {
                        $dateItem->setTagListByID($categoryIds);
                    }
                }
                if ($form->has('hashtag_mapping')) {
                    $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];

                    if (isset($formData['hashtag_mapping']['newHashtag'])) {
                        $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];
                        $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                        $hashtagIds[] = $newHashtag->getItemID();
                    }

                    if (!empty($hashtagIds)) {
                        $dateItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $valuesToChange = array();
                if ($valuesBeforeChange['startingTime'] != $dateItem->getStartingTime()) {
                    $valuesToChange[] = 'startingTime';
                }
                if ($valuesBeforeChange['endingTime'] != $dateItem->getEndingTime()) {
                    $valuesToChange[] = 'endingTime';
                }
                if ($valuesBeforeChange['place'] != $dateItem->getPlace()) {
                    $valuesToChange[] = 'place';
                }
                if ($valuesBeforeChange['color'] != $dateItem->getColor()) {
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
            } else {
                if ($saveType == 'saveThisDate') {
                    if (!$dateItem->getDateTime_recurrence()) {
                        $dateItem->setDateTime_recurrence($dateItem->getDateTime_start());
                    }
                    $dateItem = $transformer->applyTransformation($dateItem, $formData);
                    $dateItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                    $dateItem->save();
                } else {
                    if ($saveType == 'saveAllDates') {
                        $datesArray = $this->dateService->getRecurringDates($dateItem->getContextId(),
                            $dateItem->getRecurrenceId());
                        $dateItem = $transformer->applyTransformation($dateItem, $formData);
                        $dateItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                        $dateItem->save();
                        foreach ($datesArray as $tempDate) {
                            $tempDate->setTitle($dateItem->getTitle());
                            $tempDate->setPublic((int)$dateItem->isPublic());
                            $tempDate->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                            $tempDate->setColor($dateItem->getColor());
                            $tempDate->setCalendarId($dateItem->getCalendarId());
                            $tempDate->setWholeDay($dateItem->isWholeDay());
                            $tempDate->setStartingTime($dateItem->getStartingTime());
                            $tempDate->setEndingTime($dateItem->getEndingTime());
                            $tempDate->setPlace($dateItem->getPlace());
                            $tempDate->save();

                            // mark as read and noticed by creator
                            $reader_manager = $this->legacyEnvironment->getReaderManager();
                            $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                            $noticed_manager = $this->legacyEnvironment->getNoticedManager();
                            $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());
                        }
                    }
                }
            }
            return $this->redirectToRoute('app_date_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($dateItem), CommsyEditEvent::EDIT);

        return array(
            'form' => $form->createView(),
            'isDraft' => $isDraft,
            'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
            'withRecurrence' => $dateItem->getRecurrencePattern() != '',
            'date' => $dateItem,
        );
    }

    private function getTagDetailArray($baseCategories, $itemCategories)
    {
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
                        $result[] = array(
                            'title' => $baseCategory['title'],
                            'item_id' => $baseCategory['item_id'],
                            'children' => $tempResult
                        );
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array(
                        'title' => $baseCategory['title'],
                        'item_id' => $baseCategory['item_id'],
                        'children' => $tempResult
                    );
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
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $date = $this->dateService->getDate($itemId);

        $itemArray = array($date);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $readerManager = $this->legacyEnvironment->getReaderManager();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $date->getItemID());
        /** @var cs_user_item $current_user */
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $date->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($date), CommsyEditEvent::SAVE);

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
        /** @var cs_dates_item $dateItem */

        if ($isNewRecurring) {
            $recurringDateArray = array();
            $recurringPatternArray = array();

            $startDate = new \DateTime($dateItem->getStartingDay());
            $endDate = $formData['recurring_sub']['untilDate'];

            $recurringPatternArray['recurring_select'] = $formData['recurring_select'];

            // daily recurring
            if ($formData['recurring_select'] == 'RecurringDailyType') {
                $dateInterval = new \DateInterval('P' . $formData['recurring_sub']['recurrenceDay'] . 'D');

                $day = clone $startDate;
                $day->add($dateInterval);
                while ($day <= $endDate) {
                    $recurringDateArray[] = clone $day;

                    $day->add($dateInterval);
                }
                $recurringPatternArray['recurring_sub']['recurrenceDay'] = $formData['recurring_sub']['recurrenceDay'];

                unset($dateInterval);

                // weekly recurring
            } else {
                if ($formData['recurring_select'] == 'RecurringWeeklyType') {
                    // go back to last monday(if day is not monday)
                    $monday = clone $startDate;
                    if ($startDate->format('w') == 0) {
                        $monday->sub(new \DateInterval('P6D'));
                    } else {
                        $monday->sub(new \DateInterval('P' . ($startDate->format('w') - 1) . 'D'));
                    }

                    while ($monday <= $endDate) {
                        foreach ($formData['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                            if ($day == 'monday') {
                                $addonDays = 0;
                            } elseif ($day == 'tuesday') {
                                $addonDays = 1;
                            } elseif ($day == 'wednesday') {
                                $addonDays = 2;
                            } elseif ($day == 'thursday') {
                                $addonDays = 3;
                            } elseif ($day == 'friday') {
                                $addonDays = 4;
                            } elseif ($day == 'saturday') {
                                $addonDays = 5;
                            } elseif ($day == 'sunday') {
                                $addonDays = 6;
                            }

                            $temp = clone $monday;
                            $temp->add(new \DateInterval('P' . $addonDays . 'D'));

                            if ($temp > $startDate && $temp <= $endDate) {
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
                } else {
                    if ($formData['recurring_select'] == 'RecurringMonthlyType') {
                        $monthCount = $startDate->format('m');
                        $yearCount = $startDate->format('Y');
                        $monthToAdd = $formData['recurring_sub']['recurrenceMonth'] % 12;
                        $yearsToAdd = ($formData['recurring_sub']['recurrenceMonth'] - $monthToAdd) / 12;
                        $month = new \DateTime($yearCount . '-' . $monthCount . '-01');

                        while ($month <= $endDate) {
                            $datesOccurenceArray = array();

                            // loop through every day of this month
                            for ($index = 0; $index < $month->format('t'); $index++) {
                                $temp = clone $month;
                                $temp->add(new \DateInterval('P' . $index . 'D'));

                                // if the actual day is a correct week day, add it to possible dates
                                $weekDay = $temp->format('l'); // 'l' returns the full textual representation of the date's day of week (e.g. "Tuesday")

                                // NOTE: for monthly recurring dates, `recurrenceDayOfMonth` contains the day of week (e.g. "tuesday")
                                // instead of the numeric day number (like "26") as is the case for yearly recurring dates
                                if (strtolower($weekDay) === $formData['recurring_sub']['recurrenceDayOfMonth']) {
                                    $datesOccurenceArray[] = $temp;
                                }

                                unset($temp);
                            }

                            // add only days, that match the right week
                            $date = null;
                            $dayOfMonthInterval = $formData['recurring_sub']['recurrenceDayOfMonthInterval'];
                            if ($dayOfMonthInterval === 'last') {
                                $date = end($datesOccurenceArray);
                            } else {
                                if (isset($datesOccurenceArray[$dayOfMonthInterval - 1])) {
                                    $date = $datesOccurenceArray[$dayOfMonthInterval - 1];
                                }
                            }
                            if (isset($date) && $date >= $startDate && $date <= $endDate) {
                                $recurringDateArray[] = $date;
                            }

                            // go to next month
                            if ($monthCount + $monthToAdd > 12) {
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
                        $recurringPatternArray['recurring_sub']['recurrenceDayOfMonthInterval'] = $dayOfMonthInterval;

                        unset($month);

                        // yearly recurring
                    } else {
                        if ($formData['recurring_select'] == 'RecurringYearlyType') {
                            $yearCount = $startDate->format('Y');
                            $year = new \DateTime($yearCount . '-01-01');
                            while ($year <= $endDate) {
                                $date = new \DateTime($formData['recurring_sub']['recurrenceDayOfMonth'] . '-' . $formData['recurring_sub']['recurrenceMonthOfYear'] . '-' . $yearCount);
                                if ($date > $startDate && $date <= $endDate) {
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
                    }
                }
            }

            unset($startDate);
            unset($endDate);

            $recurringPatternArray['recurringStartDate'] = $dateItem->getStartingDay();
            $recurringPatternArray['recurringEndDate'] = $formData['recurring_sub']['untilDate']->format('Y-m-d');

            foreach ($recurringDateArray as $date) {
                // prevent duplicate date entry
                if (date('Y-m-d', $date->getTimestamp()) === $dateItem->getStartingDay()) {
                    continue;
                }

                $tempDate = clone $dateItem;
                $tempDate->setItemID('');
                $tempDate->setStartingDay(date('Y-m-d', $date->getTimestamp()));

                if ($dateItem->getStartingTime() != '') {
                    $tempDate->setDateTime_start(date('Y-m-d',
                            $date->getTimestamp()) . ' ' . $dateItem->getStartingTime());
                } else {
                    $tempDate->setDateTime_start(date('Y-m-d 00:00:00', $date->getTimestamp()));
                }

                if ($dateItem->getEndingDay() != '') {
                    $tempStartingDay = new \DateTime($dateItem->getStartingDay());
                    $tempEndingDay = new \DateTime($dateItem->getEndingDay());

                    $tempDate->setEndingDay(date('Y-m-d',
                        $date->getTimestamp() + ($tempEndingDay->getTimestamp() - $tempStartingDay->getTimestamp())));

                    unset($tempStartingDay);
                    unset($tempEndingDay);

                    if ($dateItem->getEndingTime() != '') {
                        $tempDate->setDateTime_end(date('Y-m-d',
                                $date->getTimestamp()) . ' ' . $dateItem->getEndingTime());
                    } else {
                        $tempDate->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                    }
                } else {
                    if ($dateItem->getEndingTime() != '') {
                        $tempDate->setDateTime_end(date('Y-m-d',
                                $date->getTimestamp()) . ' ' . $dateItem->getEndingTime());
                    } else {
                        $tempDate->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                    }
                }
                $tempDate->setRecurrenceId($dateItem->getItemID());
                $tempDate->setRecurrencePattern($recurringPatternArray);
                $tempDate->save();

                // mark as read and noticed by creator
                $reader_manager = $this->legacyEnvironment->getReaderManager();
                $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                $noticed_manager = $this->legacyEnvironment->getNoticedManager();
                $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());

            }
            $dateItem->setRecurrenceId($dateItem->getItemID());
            $dateItem->setRecurrencePattern($recurringPatternArray);
            $dateItem->save();
        } else {
            // TODO: remove this else block if (as suspected) it is dead code that doesn't get executed anymore
            $datesManager = $this->legacyEnvironment->getDatesManager();
            $datesManager->resetLimits();
            $datesManager->setRecurrenceLimit($dateItem->getRecurrenceId());
            $datesManager->setWithoutDateModeLimit();
            $datesManager->select();


            $datesList = $datesManager->get();

            /** @var cs_dates_item $tempDate */
            $tempDate = $datesList->getFirst();
            while ($tempDate) {
                if (in_array('startingTime', $valuesToChange)) {
                    $tempDate->setStartingTime($dateItem->getStartingTime());
                    $tempDate->setDateTime_start(mb_substr($tempDate->getDateTime_start(), 0,
                            10) . ' ' . $dateItem->getStartingTime());
                }
                if (in_array('endingTime', $valuesToChange)) {
                    $tempDate->setEndingTime($dateItem->getEndingTime());
                    $tempDate->setDateTime_end(mb_substr($tempDate->getDateTime_end(), 0,
                            10) . ' ' . $dateItem->getEndingTime());
                }
                if (in_array('place', $valuesToChange)) {
                    $tempDate->setPlace($dateItem->getPlace());
                }
                if (in_array('color', $valuesToChange)) {
                    $tempDate->setColor($dateItem->getColor());
                }

                // mark as read and noticed by creator
                $reader_manager = $this->legacyEnvironment->getReaderManager();
                $reader_manager->markRead($tempDate->getItemID(), $tempDate->getVersionID());

                $noticed_manager = $this->legacyEnvironment->getNoticedManager();
                $noticed_manager->markNoticed($tempDate->getItemID(), $tempDate->getVersionID());

                //$tempDate->save();
                $tempDate = $datesList->getNext();
            }
        }
    }

    /**
     * @Route("/room/{roomId}/date/{itemId}/print")
     * @param CategoryService $categoryService
     * @param PrintService $printService
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        CategoryService $categoryService,
        PrintService $printService,
        int $roomId,
        int $itemId
    ) {
        $date = $this->dateService->getDate($itemId);
        $item = $date;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $this->legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }


        $itemArray = array($date);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerManager = $this->legacyEnvironment->getReaderManager();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $date->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $date->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $dateCategories = $date->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $html = $this->renderView('date/detail_print.html.twig', [
            'roomId' => $roomId,
            'date' => $this->dateService->getDate($itemId),
            'readerList' => $readerList,
            'modifierList' => $modifierList,
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'annotationForm' => $form->createView(),
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
            'draft' => $this->itemService->getItem($itemId)->isDraft(),
            'showCategories' => $current_context->withTags(),
            'showAssociations' => $current_context->isAssociationShowExpanded(),
            'showHashtags' => $current_context->withBuzzwords(),
            'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'buzzExpanded' => $current_context->isBuzzwordShowExpanded(),
            'catzExpanded' => $current_context->isTagsShowExpanded(),
            'roomCategories' => $categories,
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/date/{itemId}/participate")
     * @param int $roomId
     * @param int $itemId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_PARTICIPATE', itemId) and is_granted('RUBRIC_SEE', 'date')")
     */
    public function participateAction(
        int $roomId,
        int $itemId
    ) {
        $date = $this->dateService->getDate($itemId);

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if (!$date->isParticipant($this->legacyEnvironment->getCurrentUserItem())) {
            $date->addParticipant($currentUser);
        } else {
            $date->removeParticipant($currentUser);
        }

        return $this->redirectToRoute('app_date_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }

    /**
     * @Route("/room/{roomId}/date/import")
     * @Template()
     * @param Request $request
     * @param CalendarsService $calendarsService
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function importAction(
        Request $request,
        CalendarsService $calendarsService,
        int $roomId
    ) {
        $formData = [];
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');
        $calendars = $repository->findBy(array('context_id' => $roomId));
        $calendarsOptions = [$this->translator->trans('new calendar', [], 'date') => 'new'];
        $calendarsOptionsAttr = [
            [
                'title' => $this->translator->trans('new calendar'),
                'color' => '#ffffff',
                'hasLightColor' => true
            ]
        ];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = [
                    'title' => $calendar->getTitle(),
                    'color' => $calendar->getColor(),
                    'hasLightColor' => $calendar->hasLightColor()
                ];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;
        $formData['files'] = [];

        $formOptions = array(
            'action' => $this->generateUrl('app_date_import', array(
                'roomId' => $roomId,
            )),
            'calendars' => $calendarsOptions,
            'calendarsAttr' => $calendarsOptionsAttr,
            'uploadUrl' => $this->generateUrl('app_date_importupload', [
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
                if ($formData['calendar'] != 'new') {
                    $calendars = $calendarsService->getCalendar($formData['calendar']);
                    if (isset($calendars[0])) {
                        $calendar = $calendars[0];
                    }
                } else {
                    $calendar = new Calendars();

                    $calendarTitle = $formData['calendartitle'];
                    if ($calendarTitle == '') {
                        $calendarTitle = $this->translator->trans('new calendar');
                    }
                    $calendar->setTitle($calendarTitle);

                    $calendar->setContextId($roomId);
                    $calendar->setCreatorId($this->legacyEnvironment->getCurrentUserId());

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

                return $this->redirectToRoute('app_date_list', array('roomId' => $roomId));
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/date/importupload")
     * @param Request $request
     * @param int $roomId
     * @return JsonResponse
     */
    public function importUploadAction(
        Request $request,
        int $roomId
    ) {
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
     * @param Request $request
     * @param DownloadAction $action
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        DownloadAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/date/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);

    }

    /**
     * @Route("/room/{roomId}/date/xhr/copy", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrCopyAction(
        Request $request,
        CopyAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/date/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        DeleteDate $deleteDate,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $recurring = false;
        if ($request->request->has('payload')) {
            $payload = $request->request->get('payload');

            $recurring = isset($payload['recurring']) ?? false;
        }

        $deleteDate->setRecurring($recurring);
        $deleteDate->setDateMode($room->getDatesPresentationStatus());

        $action->setDeleteStrategy($deleteDate);
        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $room
     * @param bool $hidePastDates Default state for hide past dates filter
     * @return FormInterface
     */
    private function createFilterForm($room, $hidePastDates = true)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
            'hide-past-dates' => $hidePastDates,
        ];

        return $this->createForm(DateFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_date_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_dates_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if ($selectAll) {
            if ($request->query->has('date_filter')) {
                $currentFilter = $request->query->get('date_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->dateService->setFilterConditions($filterForm);
            } else {
                $this->dateService->setPastFilter(false);
                $this->dateService->hideDeactivatedEntries();
            }

            return $this->dateService->getListDates($roomItem->getItemID());
        } else {
            return $this->dateService->getDatesById($roomItem->getItemID(), $itemIds);
        }
    }
}
