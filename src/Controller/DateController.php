<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Action\Activate\ActivateAction;
use App\Action\Activate\DeactivateAction;
use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteDate;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\Mark\MarkAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\Pin\PinAction;
use App\Action\Pin\UnpinAction;
use App\Entity\Calendars;
use App\Event\CommsyEditEvent;
use App\Filter\DateFilterType;
use App\Form\DataTransformer\DateTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\DateImportType;
use App\Form\Type\DateType;
use App\Hash\HashManager;
use App\Repository\CalendarsRepository;
use App\Security\Authorization\Voter\CategoryVoter;
use App\Security\Authorization\Voter\DateVoter;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\CalendarsService;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\DateService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\TopicService;
use cs_dates_item;
use cs_room_item;
use cs_user_item;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class DateController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_DATE')]
class DateController extends BaseController
{
    private DateService $dateService;

    #[Required]
    public function setDateService(DateService $dateService): void
    {
        $this->dateService = $dateService;
    }

    #[Route(path: '/room/{roomId}/date/feed/{start}/{sort}')]
    public function feed(
        Request $request,
        ItemService $itemService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        $roomItem = $this->getRoom($roomId);

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->all('date_filter');
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
        if ('date' == $sort) {
            $sort = 'time';
        } else {
            if ('date_rev' == $sort) {
                $sort = 'time_rev';
            }
        }

        if (empty($sort)) {
            $sort = $request->getSession()->get('sortDates', 'time');
        }
        $request->getSession()->set('sortDates', $sort);

        // get material list from manager service
        $dates = $this->dateService->getListDates($roomId, $max, $start, $sort);

        $readerList = [];
        foreach ($dates as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $allowedActions = $itemService->getAllowedActionsForItems($dates);

        return $this->render('date/feed.html.twig', [
            'roomId' => $roomId,
            'dates' => $dates,
            'readerList' => $readerList,
            'allowedActions' => $allowedActions,
        ]);
    }

    #[Route(path: '/room/{roomId}/date')]
    public function list(
        Request $request,
        int $roomId,
        CalendarsRepository $calendarsRepository,
        ItemService $itemService,
        HashManager $hashManager
    ): Response {
        $roomItem = $this->getRoom($roomId);

        $sort = $request->getSession()->get('sortDates', 'time');

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
        /* @noinspection PhpUndefinedMethodInspection */
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('date')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('date');
            /* @noinspection PhpUndefinedMethodInspection */
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

                $hash = $hashManager->getUserHashes($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $calendars = $calendarsRepository->findBy(['context_id' => $roomId, 'external_url' => ['', null]]);

        $pinnedItems = $itemService->getPinnedItems($roomId, [ CS_DATE_TYPE ]);

        return $this->render('date/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => CS_DATE_TYPE,
            'relatedModule' => null,
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->getArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'sort' => $sort,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/date/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlist(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ): Response {
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
        if ('none' === $sort || empty($sort)) {
            $sort = $request->getSession()->get('sortDates', 'time');
        }
        $dates = $this->dateService->getListDates($roomId, $numAllDates, 0, $sort);

        $readerList = [];
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

    #[Route(path: '/room/{roomId}/date/calendar')]
    public function calendar(
        Request $request,
        int $roomId,
        CalendarsRepository $calendarsRepository,
        ItemService $itemService,
        HashManager $hashManager
    ): Response {
        $roomItem = $this->getRoom($roomId);
        $filterForm = $this->createFilterForm($roomItem, false, true);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $this->dateService->setFilterConditions($filterForm);
        } else {
            $this->dateService->setPastFilter(false);
        }

        $usageInfo = false;
        /* @noinspection PhpUndefinedMethodInspection */
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('date')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('date');
            /* @noinspection PhpUndefinedMethodInspection */
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

                $hash = $hashManager->getUserHashes($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $calendars = $calendarsRepository->findBy(['context_id' => $roomId, 'external_url' => ['', null]]);

        $pinnedItems = $itemService->getPinnedItems($roomId, [ CS_DATE_TYPE ]);

        return $this->render('date/calendar.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => CS_DATE_TYPE,
            'relatedModule' => null,
            'usageInfo' => $usageInfo,
            'iCal' => $iCal,
            'calendars' => $calendars,
            'isArchived' => $roomItem->getArchived(),
            'defaultView' => ('calendar' === $roomItem->getDatesPresentationStatus()) ? 'timeGridWeek' : 'dayGridMonth',
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/date/calendardashboard')]
    public function calendardashboard(
        int $roomId
    ): Response {
        return $this->render('date/calendardashboard.html.twig', [
            'roomId' => $roomId,
            'module' => 'date',
        ]);
    }

    #[Route(path: '/room/{roomId}/date/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function detail(
        Request $request,
        AnnotationService $annotationService,
        CategoryService $categoryService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ): Response {
        $date = $this->dateService->getDate($itemId);

        $item = $date;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $itemArray = [$date];

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
        $id_array = [];
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
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = [];
        $modifierList = [];
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

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $dateCategories = $date->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $alert = null;
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        } else {
            if ($date->isExternal()) {
                $alert['type'] = 'warning';
                $alert['content'] = $this->translator->trans('date is external', [], 'date');
            }
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId,
            $this->dateService->getDate($itemId)->getItemId(), null, null);

        return $this->render('date/detail.html.twig', [
            'roomId' => $roomId,
            'date' => $this->dateService->getDate($itemId),
            'amountAnnotations' => sizeof($amountAnnotations),
            'readerList' => $readerList,
            'modifierList' => $modifierList,
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'annotationForm' => $form,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
            'draft' => $this->itemService->getItem($itemId)->isDraft(),
            'pinned' => $this->itemService->getItem($itemId)->isPinned(),
            'showCategories' => $current_context->withTags(),
            'showHashtags' => $current_context->withBuzzwords(),
            'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'showAssociations' => $current_context->isAssociationShowExpanded(),
            'buzzExpanded' => $current_context->isBuzzwordShowExpanded(),
            'catzExpanded' => $current_context->isTagsShowExpanded(),
            'roomCategories' => $categories,
            'isParticipating' => $date->isParticipant($this->legacyEnvironment->getCurrentUserItem()),
            'isRecurring' => ('' != $date->getRecurrenceId()),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        ]);
    }

    /**
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/events')]
    public function events(
        Request $request,
        int $roomId
    ): Response {
        $roomItem = $this->getRoom($roomId);

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $dateFilter = $request->get('dateFilter');
        if (!$dateFilter) {
            $dateFilter = $request->query->all('date_filter');
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

        $events = [];
        foreach ($listDates as $date) {
            if (!$date->isWholeDay()) {
                $start = $date->getStartingDay();
                if ('' != $date->getStartingTime()) {
                    $start .= ' '.$date->getStartingTime();
                }
                $end = $date->getEndingDay();
                if ('' == $end) {
                    $end = $date->getStartingDay();
                }
                if ('' != $date->getEndingTime()) {
                    $end .= ' '.$date->getEndingTime();
                }
            } else {
                $start = $date->getStartingDay().' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay().' 00:00:00');
                $endDateTime->modify('+1 day');
                $end = $endDateTime->format('Y-m-d H:i:s');
            }

            $participantsNameArray = array_map(
                fn (cs_user_item $participant) => $participant->getFullname(),
                iterator_to_array($date->getParticipantsItemList())
            );

            $participantsDisplay = !empty($participantsNameArray) ? implode(', ', $participantsNameArray) : '';

            $color = $date->getCalendar()->getColor();
            $textColor = $date->getCalendar()->hasLightColor() ? '#444444' : '#ffffff';
            $borderColor = $date->getCalendar()->hasLightColor() ? '#888888' : $date->getCalendar()->getColor();

            $recurringDescription = '';
            if ('' != $date->getRecurrencePattern()) {
                $recurrencePattern = $date->getRecurrencePattern();

                if (isset($recurrencePattern['recurringEndDate'])) {
                    $endDate = new DateTime($recurrencePattern['recurringEndDate']);
                }

                if ('RecurringDailyType' == $recurrencePattern['recurring_select']) {
                    $recurringDescription = $this->translator->trans('dailyDescription', ['%day%' => $recurrencePattern['recurring_sub']['recurrenceDay'], '%date%' => $endDate->format('d.m.Y')], 'date');
                } else {
                    if ('RecurringWeeklyType' == $recurrencePattern['recurring_select']) {
                        $daysOfWeek = [];
                        if (isset($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'])) {
                            foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                                $daysOfWeek[] = $this->translator->trans($day, [], 'date');
                            }
                        }
                        $recurringDescription = $this->translator->trans('weeklyDescription', ['%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'], '%daysOfWeek%' => implode(', ', $daysOfWeek), '%date%' => $endDate->format('d.m.Y')], 'date');
                    } else {
                        if ('RecurringMonthlyType' == $recurrencePattern['recurring_select']) {
                            $tempDayOfMonthInterval = $this->translator->trans('first', [], 'date');
                            if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval'])) {
                                if (2 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                    $tempDayOfMonthInterval = $this->translator->trans('second', [], 'date');
                                } else {
                                    if (3 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                        $tempDayOfMonthInterval = $this->translator->trans('third', [], 'date');
                                    } else {
                                        if (4 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                            $tempDayOfMonthInterval = $this->translator->trans('fourth', [],
                                                'date');
                                        } else {
                                            if (5 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                                $tempDayOfMonthInterval = $this->translator->trans('fifth', [],
                                                    'date');
                                            } else {
                                                if ('last' == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                                    $tempDayOfMonthInterval = $this->translator->trans('last', [],
                                                        'date');
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'])) {
                                $recurringDescription = $this->translator->trans('monthlyDescription', ['%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'], '%day%' => $tempDayOfMonthInterval, '%dayOfWeek%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                    [], 'date'), '%date%' => $endDate->format('d.m.Y')], 'date');
                            }
                        } else {
                            if ('RecurringYearlyType' == $recurrencePattern['recurring_select']) {
                                $recurringDescription = $this->translator->trans('yearlyDescription', ['%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], '%month%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'],
                                    [], 'date'), '%date%' => $endDate->format('d.m.Y')], 'date');
                            }
                        }
                    }
                }
            }

            $events[] = [
                'id' => $date->getItemId(),
                'allDay' => $date->isWholeDay(),
                'start' => $start,
                'end' => $end,
                'title' => html_entity_decode($date->getTitle()),
                'color' => $color,
                'calendar' => $date->getCalendar()->getTitle(),
                'editable' => $this->isGranted(DateVoter::EDIT, $date),
                'description' => $date->getDateDescription(),
                'place' => $date->getPlace(),
                'participants' => $participantsDisplay,
                'contextId' => $date->getContextID(),
                'contextTitle' => '',
                'recurringDescription' => $recurringDescription,
                'textColor' => $textColor,
                'borderColor' => $borderColor,
            ];
        }

        return new JsonResponse($events);
    }

    /**
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/eventsdashboard')]
    public function eventsdashboard(
        // Do not remove $roomId even if it is unused, @IsGranted() relies on this argument
        /* @noinspection PhpUnusedParameterInspection */
        int $roomId,
    ): Response
    {
        $user = $this->legacyEnvironment->getCurrentUserItem();
        $userList = $user->getRelatedUserList()->to_array();

        $listDates = [];
        foreach ($userList as $tempUser) {
            /** @var cs_user_item $tempUser */
            if ($tempUser->getStatus() >= 2) {
                $listDates = array_merge($listDates,
                    $this->dateService->getCalendarEvents($tempUser->getContextId(), $_GET['start'], $_GET['end']));
            }
        }

        $events = [];
        foreach ($listDates as $date) {
            /** @var cs_dates_item $date */
            if (!$date->isWholeDay()) {
                $start = $date->getStartingDay();
                if ('' != $date->getStartingTime()) {
                    $start .= ' '.$date->getStartingTime();
                }
                $end = $date->getEndingDay();
                if ('' == $end) {
                    $end = $date->getStartingDay();
                }
                if ('' != $date->getEndingTime()) {
                    $end .= ' '.$date->getEndingTime();
                }
            } else {
                $start = $date->getStartingDay().' 00:00:00';
                $endDateTime = new DateTime($date->getEndingDay().' 00:00:00');
                $endDateTime->modify('+1 day');
                $end = $endDateTime->format('Y-m-d H:i:s');
            }

            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = [];
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
            if ('' != $date->getRecurrencePattern()) {
                $recurrencePattern = $date->getRecurrencePattern();

                if (isset($recurrencePattern['recurringEndDate'])) {
                    $endDate = new DateTime($recurrencePattern['recurringEndDate']);
                }

                if ('RecurringDailyType' == $recurrencePattern['recurring_select']) {
                    $recurringDescription = $this->translator->trans('dailyDescription', ['%day%' => $recurrencePattern['recurring_sub']['recurrenceDay'], '%date%' => $endDate->format('d.m.Y')], 'date');
                } else {
                    if ('RecurringWeeklyType' == $recurrencePattern['recurring_select']) {
                        $daysOfWeek = [];
                        foreach ($recurrencePattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                            $daysOfWeek[] = $this->translator->trans($day, [], 'date');
                        }
                        $recurringDescription = $this->translator->trans('weeklyDescription', ['%week%' => $recurrencePattern['recurring_sub']['recurrenceWeek'], '%daysOfWeek%' => implode(', ', $daysOfWeek), '%date%' => $endDate->format('d.m.Y')], 'date');
                    } else {
                        if ('RecurringMonthlyType' == $recurrencePattern['recurring_select']) {
                            $tempDayOfMonthInterval = $this->translator->trans('first', [], 'date');
                            if (2 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                $tempDayOfMonthInterval = $this->translator->trans('second', [], 'date');
                            } else {
                                if (3 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                    $tempDayOfMonthInterval = $this->translator->trans('third', [], 'date');
                                } else {
                                    if (4 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                        $tempDayOfMonthInterval = $this->translator->trans('fourth', [], 'date');
                                    } else {
                                        if (5 == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                            $tempDayOfMonthInterval = $this->translator->trans('fifth', [],
                                                'date');
                                        } else {
                                            if ('last' == $recurrencePattern['recurring_sub']['recurrenceDayOfMonthInterval']) {
                                                $tempDayOfMonthInterval = $this->translator->trans('last', [],
                                                    'date');
                                            }
                                        }
                                    }
                                }
                            }
                            $recurringDescription = $this->translator->trans('monthlyDescription', ['%month%' => $recurrencePattern['recurring_sub']['recurrenceMonth'], '%day%' => $tempDayOfMonthInterval, '%dayOfWeek%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceDayOfMonth'],
                                [], 'date'), '%date%' => $endDate->format('d.m.Y')], 'date');
                        } else {
                            if ('RecurringYearlyType' == $recurrencePattern['recurring_select']) {
                                $recurringDescription = $this->translator->trans('yearlyDescription', ['%day%' => $recurrencePattern['recurring_sub']['recurrenceDayOfMonth'], '%month%' => $this->translator->trans($recurrencePattern['recurring_sub']['recurrenceMonthOfYear'],
                                    [], 'date'), '%date%' => $endDate->format('d.m.Y')], 'date');
                            }
                        }
                    }
                }
            }

            $context = $this->itemService->getTypedItem($date->getContextId());

            $events[] = [
                'id' => $date->getItemId(),
                'allDay' => $date->isWholeDay(),
                'start' => $start,
                'end' => $end,
                'title' => $date->getTitle(),
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
            ];
        }

        return new JsonResponse($events);
    }

    #[Route(path: '/room/{roomId}/date/create/{dateDescription}')]
    #[IsGranted('ITEM_NEW')]
    public function create(
        int $roomId,
        $dateDescription = ''
    ): RedirectResponse {
        // create new material item
        $dateItem = $this->dateService->getNewDate();
        $dateItem->setDraftStatus(1);
        $dateItem->setPrivateEditing('1');

        if ('now' != $dateDescription) {
            $isoString = urldecode((string) $dateDescription);
            $date = DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $isoString);



            // if (!date.hasTime()) {
            //   date.time('12:00:00');
            // }
            $dateDescriptionArray = date_parse($isoString);
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

        return $this->redirectToRoute('app_date_detail',
            ['roomId' => $roomId, 'itemId' => $dateItem->getItemId()]);
    }

    /**
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/{itemId}/calendaredit')]
    public function calendaredit(
        Request $request,
        int $roomId,
        int $itemId,
    ): Response {
        $date = $this->dateService->getDate($itemId);

        $requestContent = json_decode($request->getContent(), null, 512, JSON_THROW_ON_ERROR);

        $start = DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $requestContent->start);
        $start->setTimezone(new DateTimeZone('UTC'));
        if (!empty($requestContent->end)) {
            $end = DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $requestContent->end);
            $end->setTimezone(new DateTimeZone('UTC'));
        } else {
            $end = $start;
        }

        $date->setStartingDay($start->format('Y-m-d'));
        $date->setStartingTime($start->format('H:i:s'));
        $date->setDateTime_start($start->format('Y-m-d H:i:s'));

        if (!$requestContent->allDay) {
            $date->setEndingDay($end->format('Y-m-d'));
            $date->setEndingTime($end->format('H:i:s'));
            $date->setDateTime_end($end->format('Y-m-d H:i:s'));
        } else {
            $end->modify('-1 day');
            $date->setEndingDay($end->format('Y-m-d'));
            $date->setEndingTime($end->format('23:59:59'));
            $date->setDateTime_end($end->format('Y-m-d 23:59:59'));
        }

        $date->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
        $date->save();

        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$this->translator->trans('date changed',
            [], 'date');

        return new JsonResponse([
            'message' => $message,
            'status' => 'success',
            'timeout' => '5550',
            'description' => $date->getDateDescription(),
        ]);
    }

    #[Route(path: '/room/{roomId}/date/{itemId}/edit')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        DateTransformer $transformer,
        CalendarsRepository $calendarsRepository,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        // get date from DateService
        $dateItem = $this->dateService->getDate($itemId);
        if (!$dateItem) {
            throw $this->createNotFoundException('No date found for id '.$itemId);
        }

        $formData = $transformer->transform($dateItem);
        $formData['language'] = $this->legacyEnvironment->getCurrentContextItem()->getLanguage();
        $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
        $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $formData['draft'] = $isDraft;
        $formData['creatorId'] = $dateItem->getCreatorID();

        $calendars = $calendarsRepository->findBy(['context_id' => $roomId]);
        $calendarsOptions = [];
        $calendarsOptionsAttr = [];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = [
                    'title' => $calendar->getTitle(),
                    'color' => $calendar->getColor(),
                    'hasLightColor' => $calendar->hasLightColor(),
                ];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;

        $formOptions = ['action' => $this->generateUrl('app_date_edit', ['roomId' => $roomId, 'itemId' => $itemId]), 'placeholderText' => '['.$this->translator->trans('insert title').']', 'calendars' => $calendarsOptions, 'calendarsAttr' => $calendarsOptionsAttr, 'categoryMappingOptions' => [
            'categories' => $labelService->getCategories($roomId),
            'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
            'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
        ], 'hashtagMappingOptions' => [
            'hashtags' => $labelService->getHashtags($roomId),
            'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
        ], 'room' => $current_context];
        if ('' != $dateItem->getRecurrencePattern()) {
            $formOptions['attr']['unsetRecurrence'] = true;
        }
        $form = $this->createForm(DateType::class, $formData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $formData = $form->getData();
            if ('save' == $saveType) {
                $valuesBeforeChange = [];
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

                    if (isset($formData['category_mapping']['newCategory']) && $this->isGranted(CategoryVoter::EDIT)) {
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

                $valuesToChange = [];
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
                    if ('' != $formData['recurring_select'] && 'RecurringNoneType' != $formData['recurring_select']) {
                        $withRecurring = true;
                    }
                    if (!$withRecurring) {
                        if ('' != $dateItem->getRecurrencePattern()) {
                            $withRecurring = true;
                            $isNewRecurring = false;
                        }
                    }
                }
                if ($withRecurring) {
                    $this->saveRecurringDates($dateItem, $isNewRecurring, $valuesToChange, $formData);
                }

                $dateItem->save();
            } else {
                if ('saveThisDate' == $saveType) {
                    if (!$dateItem->getDateTime_recurrence()) {
                        $dateItem->setDateTime_recurrence($dateItem->getDateTime_start());
                    }
                    $dateItem = $transformer->applyTransformation($dateItem, $formData);
                    $dateItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                    $dateItem->save();
                } else {
                    if ('saveAllDates' == $saveType) {
                        $datesArray = $this->dateService->getRecurringDates($dateItem->getContextId(),
                            $dateItem->getRecurrenceId());
                        $dateItem = $transformer->applyTransformation($dateItem, $formData);
                        $dateItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                        $dateItem->save();
                        foreach ($datesArray as $tempDate) {
                            $tempDate->setTitle($dateItem->getTitle());
                            $tempDate->setPublic((int) $dateItem->isPublic());
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
                        }
                    }
                }
            }

            return $this->redirectToRoute('app_date_save', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($dateItem), CommsyEditEvent::EDIT);

        return $this->render('date/edit.html.twig', ['form' => $form, 'isDraft' => $isDraft, 'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(), 'currentUser' => $this->legacyEnvironment->getCurrentUserItem(), 'withRecurrence' => '' != $dateItem->getRecurrencePattern(), 'date' => $dateItem]);
    }

    private function getTagDetailArray($baseCategories, $itemCategories)
    {
        $result = [];
        $tempResult = [];
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
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                    } else {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']];
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                }
            }
            $tempResult = [];
            $addCategory = false;
        }

        return $result;
    }

    #[Route(path: '/room/{roomId}/date/{itemId}/save')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function save(
        int $roomId,
        int $itemId
    ): Response {
        $date = $this->dateService->getDate($itemId);

        $itemArray = [$date];
        $modifierList = [];
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
        $id_array = [];
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
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = [];
        $modifierList = [];
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

        return $this->render('date/save.html.twig', ['roomId' => $roomId, 'item' => $date, 'modifierList' => $modifierList, 'userCount' => $all_user_count, 'readCount' => $read_count, 'readSinceModificationCount' => $read_since_modification_count]);
    }

    public function saveRecurringDates($dateItem, $isNewRecurring, $valuesToChange, $formData)
    {
        /* @var cs_dates_item $dateItem */

        if ($isNewRecurring) {
            $recurringDateArray = [];
            $recurringPatternArray = [];

            $startDate = new DateTime($dateItem->getStartingDay());
            $endDate = $formData['recurring_sub']['untilDate'];

            $recurringPatternArray['recurring_select'] = $formData['recurring_select'];

            // daily recurring
            if ('RecurringDailyType' == $formData['recurring_select']) {
                $dateInterval = new DateInterval('P'.$formData['recurring_sub']['recurrenceDay'].'D');

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
                if ('RecurringWeeklyType' == $formData['recurring_select']) {
                    // go back to last monday(if day is not monday)
                    $monday = clone $startDate;
                    if (0 == $startDate->format('w')) {
                        $monday->sub(new DateInterval('P6D'));
                    } else {
                        $monday->sub(new DateInterval('P'.($startDate->format('w') - 1).'D'));
                    }

                    while ($monday <= $endDate) {
                        foreach ($formData['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                            if ('monday' == $day) {
                                $addonDays = 0;
                            } elseif ('tuesday' == $day) {
                                $addonDays = 1;
                            } elseif ('wednesday' == $day) {
                                $addonDays = 2;
                            } elseif ('thursday' == $day) {
                                $addonDays = 3;
                            } elseif ('friday' == $day) {
                                $addonDays = 4;
                            } elseif ('saturday' == $day) {
                                $addonDays = 5;
                            } elseif ('sunday' == $day) {
                                $addonDays = 6;
                            }

                            $temp = clone $monday;
                            $temp->add(new DateInterval('P'.$addonDays.'D'));

                            if ($temp > $startDate && $temp <= $endDate) {
                                $recurringDateArray[] = $temp;
                            }

                            unset($temp);
                        }

                        $monday->add(new DateInterval('P'.$formData['recurring_sub']['recurrenceWeek'].'W'));
                    }
                    $recurringPatternArray['recurring_sub']['recurrenceDaysOfWeek'] = $formData['recurring_sub']['recurrenceDaysOfWeek'];
                    $recurringPatternArray['recurring_sub']['recurrenceWeek'] = $formData['recurring_sub']['recurrenceWeek'];

                    unset($monday);

                // monthly recurring
                } else {
                    if ('RecurringMonthlyType' == $formData['recurring_select']) {
                        $monthCount = $startDate->format('m');
                        $yearCount = $startDate->format('Y');
                        $monthToAdd = $formData['recurring_sub']['recurrenceMonth'] % 12;
                        $yearsToAdd = ($formData['recurring_sub']['recurrenceMonth'] - $monthToAdd) / 12;
                        $month = new DateTime($yearCount.'-'.$monthCount.'-01');

                        while ($month <= $endDate) {
                            $datesOccurenceArray = [];

                            // loop through every day of this month
                            for ($index = 0; $index < $month->format('t'); ++$index) {
                                $temp = clone $month;
                                $temp->add(new DateInterval('P'.$index.'D'));

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
                            if ('last' === $dayOfMonthInterval) {
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
                            $month = new DateTime($yearCount.'-'.$monthCount.'-01');
                        }

                        $recurringPatternArray['recurring_sub']['recurrenceMonth'] = $formData['recurring_sub']['recurrenceMonth'];
                        $recurringPatternArray['recurring_sub']['recurrenceDayOfMonth'] = $formData['recurring_sub']['recurrenceDayOfMonth'];
                        $recurringPatternArray['recurring_sub']['recurrenceDayOfMonthInterval'] = $dayOfMonthInterval;

                        unset($month);

                    // yearly recurring
                    } else {
                        if ('RecurringYearlyType' == $formData['recurring_select']) {
                            $yearCount = $startDate->format('Y');
                            $year = new DateTime($yearCount.'-01-01');
                            while ($year <= $endDate) {
                                $date = new DateTime($formData['recurring_sub']['recurrenceDayOfMonth'].'-'.$formData['recurring_sub']['recurrenceMonthOfYear'].'-'.$yearCount);
                                if ($date > $startDate && $date <= $endDate) {
                                    $recurringDateArray[] = $date;
                                }
                                unset($date);

                                unset($year);
                                ++$yearCount;
                                $year = new DateTime($yearCount.'-01-01');
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

                if ('' != $dateItem->getStartingTime()) {
                    $tempDate->setDateTime_start(date('Y-m-d',
                        $date->getTimestamp()).' '.$dateItem->getStartingTime());
                } else {
                    $tempDate->setDateTime_start(date('Y-m-d 00:00:00', $date->getTimestamp()));
                }

                if ('' != $dateItem->getEndingDay()) {
                    $tempStartingDay = new DateTime($dateItem->getStartingDay());
                    $tempEndingDay = new DateTime($dateItem->getEndingDay());

                    $tempDate->setEndingDay(date('Y-m-d',
                        $date->getTimestamp() + ($tempEndingDay->getTimestamp() - $tempStartingDay->getTimestamp())));

                    unset($tempStartingDay);
                    unset($tempEndingDay);

                    if ('' != $dateItem->getEndingTime()) {
                        $tempDate->setDateTime_end(date('Y-m-d',
                            $date->getTimestamp()).' '.$dateItem->getEndingTime());
                    } else {
                        $tempDate->setDateTime_end(date('Y-m-d 00:00:00', $date->getTimestamp()));
                    }
                } else {
                    if ('' != $dateItem->getEndingTime()) {
                        $tempDate->setDateTime_end(date('Y-m-d',
                            $date->getTimestamp()).' '.$dateItem->getEndingTime());
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
                    $tempDate->setDateTime_start(mb_substr((string) $tempDate->getDateTime_start(), 0,
                        10).' '.$dateItem->getStartingTime());
                }
                if (in_array('endingTime', $valuesToChange)) {
                    $tempDate->setEndingTime($dateItem->getEndingTime());
                    $tempDate->setDateTime_end(mb_substr((string) $tempDate->getDateTime_end(), 0,
                        10).' '.$dateItem->getEndingTime());
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

                // $tempDate->save();
                $tempDate = $datesList->getNext();
            }
        }
    }

    #[Route(path: '/room/{roomId}/date/{itemId}/print')]
    public function print(
        CategoryService $categoryService,
        PrintService $printService,
        int $roomId,
        int $itemId
    ): Response {
        $date = $this->dateService->getDate($itemId);
        $item = $date;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $itemArray = [$date];

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
        $id_array = [];
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
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = [];
        $modifierList = [];
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

        $categories = [];
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

    #[Route(path: '/room/{roomId}/date/{itemId}/participate')]
    #[IsGranted('ITEM_PARTICIPATE', subject: 'itemId')]
    public function participate(
        int $roomId,
        int $itemId
    ): RedirectResponse {
        $date = $this->dateService->getDate($itemId);

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if (!$date->isParticipant($this->legacyEnvironment->getCurrentUserItem())) {
            $date->addParticipant($currentUser);
        } else {
            $date->removeParticipant($currentUser);
        }

        return $this->redirectToRoute('app_date_detail', ['roomId' => $roomId, 'itemId' => $itemId]);
    }

    #[Route(path: '/room/{roomId}/date/import')]
    public function import(
        Request $request,
        CalendarsService $calendarsService,
        CalendarsRepository $calendarsRepository,
        ManagerRegistry $doctrine,
        int $roomId
    ): Response {
        $calendar = null;
        $formData = [];
        $calendars = $calendarsRepository->findBy(['context_id' => $roomId]);
        $calendarsOptions = [$this->translator->trans('new calendar', [], 'date') => 'new'];
        $calendarsOptionsAttr = [
            [
                'title' => $this->translator->trans('new calendar'),
                'color' => '#ffffff',
                'hasLightColor' => true,
            ],
        ];
        foreach ($calendars as $calendar) {
            if (!$calendar->getExternalUrl()) {
                $calendarsOptions[$calendar->getTitle()] = $calendar->getId();
                $calendarsOptionsAttr[$calendar->getTitle()] = [
                    'title' => $calendar->getTitle(),
                    'color' => $calendar->getColor(),
                    'hasLightColor' => $calendar->hasLightColor(),
                ];
            }
        }
        $formData['calendars'] = $calendarsOptions;
        $formData['calendarsAttr'] = $calendarsOptionsAttr;
        $formData['files'] = [];

        $formOptions = ['action' => $this->generateUrl('app_date_import', ['roomId' => $roomId]), 'calendars' => $calendarsOptions, 'calendarsAttr' => $calendarsOptionsAttr, 'uploadUrl' => $this->generateUrl('app_date_importupload', [
            'roomId' => $roomId,
            'itemId' => null,
        ])];

        $form = $this->createForm(DateImportType::class, $formData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $files = $formData['files'];

            if (!empty($files)) {
                // get calendar object or create new
                if ('new' != $formData['calendar']) {
                    $calendars = $calendarsService->getCalendar($formData['calendar']);
                    if (isset($calendars[0])) {
                        $calendar = $calendars[0];
                    }
                } else {
                    $calendar = new Calendars();

                    $calendarTitle = $formData['calendartitle'];
                    if ('' == $calendarTitle) {
                        $calendarTitle = $this->translator->trans('new calendar');
                    }
                    $calendar->setTitle($calendarTitle);

                    $calendar->setContextId($roomId);
                    $calendar->setCreatorId($this->legacyEnvironment->getCurrentUserId());

                    $calendarColor = $formData['calendarcolor'];
                    if ('' == $calendarColor) {
                        $calendarColor = '#ffffff';
                    }
                    $calendar->setColor($calendarColor);

                    $calendar->setSynctoken(0);

                    $em = $doctrine->getManager();
                    $em->persist($calendar);
                    $em->flush();
                }

                $projectDir = $this->getParameter('kernel.project_dir');
                $fileData = [];
                foreach ($files as $file) {
                    $fileHandle = fopen($projectDir.'/var/temp/'.$file->getFileId(), 'r');
                    if ($fileHandle) {
                        $fileData[] = $fileHandle;
                    }
                }

                if (!empty($fileData)) {
                    $calendarsService->importEvents($fileData, $calendar);
                }

                return $this->redirectToRoute('app_date_list', ['roomId' => $roomId]);
            }
        }

        return $this->render('date/import.html.twig', ['form' => $form]);
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/date/importupload')]
    public function importUpload(
        Request $request,
        int $roomId
    ): Response {
        $response = new JsonResponse();

        $projectDir = $this->getParameter('kernel.project_dir');

        $files = $request->files->all();

        $responseData = [];
        foreach ($files['files'] as $file) {
            if (stristr((string) $file->getMimeType(), 'text/calendar')) {
                $filename = $roomId.'_'.date('Ymdhis').'_'.$file->getClientOriginalName();
                if ($file->move($projectDir.'/var/temp/', $filename)) {
                    $responseData[$filename] = $file->getClientOriginalName();
                }
            }
        }

        return $response->setData([
            'fileIds' => $responseData,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/download')]
    public function download(
        Request $request,
        DownloadAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkRead(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/pin', condition: 'request.isXmlHttpRequest()')]
    public function xhrPinAction(
        Request $request,
        PinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/unpin', condition: 'request.isXmlHttpRequest()')]
    public function xhrUnpinAction(
        Request $request,
        UnpinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/mark', condition: 'request.isXmlHttpRequest()')]
    public function xhrMark(
        Request $request,
        MarkAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorize(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtag(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/activate', condition: 'request.isXmlHttpRequest()')]
    public function xhrActivate(
        Request $request,
        ActivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeactivate(
        Request $request,
        DeactivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/date/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $action,
        DeleteDate $deleteDate,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $recurring = false;
        if ($request->request->has('payload')) {
            $payload = $request->request->all('payload');

            $recurring = isset($payload['recurring']) ?? false;
        }

        $deleteDate->setRecurring($recurring);
        $deleteDate->setDateMode($room->getDatesPresentationStatus());

        $action->setDeleteStrategy($deleteDate);

        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $room
     * @param bool          $hidePastDates  Default state for hide past dates filter
     * @param bool          $viewAsCalendar Wheter the form's action should point to the calendar view (true),
     *                                      or else to list view(false): defaults to else
     */
    private function createFilterForm($room, $hidePastDates = true, $viewAsCalendar = false): FormInterface
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
            'hide-past-dates' => $hidePastDates,
        ];

        return $this->createForm(DateFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl($viewAsCalendar ? 'app_date_calendar' : 'app_date_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
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
                $currentFilter = $request->query->all('date_filter');
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
