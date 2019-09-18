<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\ItemMarkRead;
use App\Event\CommsyEditEvent;
use App\Filter\AnnouncementFilterType;
use App\Form\Type\AnnotationType;
use App\Form\Type\AnnouncementType;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnouncementService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\TopicService;
use cs_announcement_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AnnouncementController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'announcement')")
 */
class AnnouncementController extends BaseController
{
    /**
     * @Route("/room/{roomId}/announcement/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param RoomService $roomService
     * @param ReaderService $readerService
     * @param AnnouncementService $announcementService
     * @param AssessmentService $assessmentService
     * @param LegacyEnvironment $environment
     * @param $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        RoomService $roomService,
        ReaderService $readerService,
        AnnouncementService $announcementService,
        AssessmentService $assessmentService,
        LegacyEnvironment $environment, $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date')
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $announcementFilter = $request->get('announcementFilter');
        if (!$announcementFilter) {
            $announcementFilter = $request->query->get('announcement_filter');
        }

        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($announcementFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($announcementFilter);

            // apply filter
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service
        /** @var cs_announcement_item[] $announcements */
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $this->get('session')->set('sortAnnouncements', $sort);

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
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
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/shortfeed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param AnnouncementService $announcementService
     * @param AssessmentService $assessmentService
     * @param RoomService $roomService
     * @param ReaderService $readerService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param null $sort
     * @return array|void
     */
    public function shortfeedAction(
        Request $request,
        AnnouncementService $announcementService,
        AssessmentService $assessmentService,
        RoomService $roomService,
        ReaderService $readerService,
        LegacyEnvironment $environment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        $sort = NULL
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->setDateLimit();
            $sort = 'date';
        }

        $announcementService->hideDeactivatedEntries();

        // get announcement list from manager service
        /** @var cs_announcement_item[] $announcements */
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
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
     * @param Request $request
     * @param AnnouncementService $announcementService
     * @param RoomService $roomService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        AnnouncementService $announcementService,
        RoomService $roomService,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service 
        $itemsCountArray = $announcementService->getCountArray($roomId);

        $usageInfo = false;
        /** @noinspection PhpUndefinedMethodInspection */
        if ($roomItem->getUsageInfoTextForRubricInForm('announcement') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('announcement');
            /** @noinspection PhpUndefinedMethodInspection */
            /** @noinspection PhpUndefinedMethodInspection */
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('announcement');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'announcement',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param AnnouncementService $announcementService
     * @param AssessmentService $assessmentService
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param RoomService $roomService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        AnnouncementService $announcementService,
        AssessmentService $assessmentService,
        PrintService $printService,
        ReaderService $readerService,
        RoomService $roomService,
        LegacyEnvironment $environment,
        int $roomId,
        $sort
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        $numAllAnnouncements = $announcementService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service
        if ($sort != "none") {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $sort);
        } elseif ($this->get('session')->get('sortAnnouncements')) {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $this->get('session')->get('sortAnnouncements'));
        } else {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, 'date');
        }

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service 
        $itemsCountArray = $announcementService->getCountArray($roomId);

        $html = $this->renderView('announcement/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'announcement',
            'announcements' => $announcements,
            'readerList' => $readerList,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'ratingList' => $ratingList,
            'showWorkflow' => $current_context->withWorkflow(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'announcement')")
     * @param Request $request
     * @param LegacyMarkup $legacyMarkup
     * @param ItemService $itemService
     * @param TopicService $topicService
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        LegacyMarkup $legacyMarkup,
        ItemService $itemService,
        TopicService $topicService,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['announcement']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

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
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $infoArray['categories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'ratingArray' => $infoArray['ratingArray'],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}/print")
     * @param PrintService $printService
     * @param $roomId
     * @param $itemId
     * @return Response
     */
    public function printAction(
        PrintService $printService,
        $roomId,
        $itemId
    ) {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('announcement/detail_print.html.twig', [
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
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'itions' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/announcement/create")
     * @param AnnouncementService $announcementService
     * @param int $roomId
     * @return RedirectResponse
     * @throws Exception
     */
    public function createAction(
        AnnouncementService $announcementService,
        int $roomId
    ) {
        // create new announcement item
        $announcementItem = $announcementService->getNewAnnouncement();
        $dateTime = new \DateTime('now');
        $announcementItem->setFirstDateTime($dateTime->format('Y-m-d H:i:s'));

        try {
            $dateTime->add(new \DateInterval('P2W'));
        } catch (Exception $e) {

        }

        $announcementItem->setSecondDateTime($dateTime->format('Y-m-d H:i:s'));
        $announcementItem->setDraftStatus(1);
        $announcementItem->setPrivateEditing(1);
        $announcementItem->save();

        return $this->redirectToRoute('app_announcement_detail', array('roomId' => $roomId, 'itemId' => $announcementItem->getItemId()));
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'announcement')")
     * @param Request $request
     * @param AnnouncementService $announcementService
     * @param CategoryService $categoryService
     * @param ItemService $itemService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        AnnouncementService $announcementService,
        CategoryService $categoryService,
        ItemService $itemService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        /** @var \cs_item $item */
        $item = $itemService->getItem($itemId);

        $transformer = $this->get('commsy_legacy.transformer.announcement');

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $announcementItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        if ($item->getItemType() == 'announcement') {
            // get announcement from announcementService
            /** @var cs_announcement_item $announcementItem */
            $announcementItem = $announcementService->getannouncement($itemId);
            $announcementItem->setDraftStatus($item->isDraft());
            if (!$announcementItem) {
                throw $this->createNotFoundException('No announcement found for id ' . $roomId);
            }
            $itemController = $this->get('commsy.item_controller');
            $formData = $transformer->transform($announcementItem);
            $formData['categoriesMandatory'] = $categoriesMandatory;
            $formData['hashtagsMandatory'] = $hashtagsMandatory;
            $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
            $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
            $translator = $this->get('translator');
            $form = $this->createForm(AnnouncementType::class, $formData, array(
                'action' => $this->generateUrl('app_announcement_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '[' . $translator->trans('insert title') . ']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $categoryService),
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                    'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
                ],
            ));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {
                $announcementItem = $transformer->applyTransformation($announcementItem, $form->getData());

                // update modifier
                $announcementItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $announcementItem->setTagListByID($formData['category_mapping']['categories']);
                }
                if ($hashtagsMandatory) {
                    $announcementItem->setBuzzwordListByID($formData['hashtag_mapping']['hashtags']);
                }

                $announcementItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            }

            return $this->redirectToRoute('app_announcement_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($announcementItem));

        return array(
            'form' => $form->createView(),
            'announcement' => $announcementItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }


    /**
     * @Route("/room/{roomId}/announcement/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'announcement')")
     * @param AnnouncementService $announcementService
     * @param ItemService $itemService
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        AnnouncementService $announcementService,
        ItemService $itemService,
        int $roomId,
        int $itemId
    ) {
        $tempItem = $announcementService->getannouncement($itemId);
        $itemArray = array($tempItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($tempItem));

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
     * @Route("/room/{roomId}/announcement/{itemId}/rating/{vote}")
     * @Template()
     * @param AnnouncementService $announcementService
     * @param AssessmentService $assessmentService
     * @param int $roomId
     * @param int $itemId
     * @param $vote
     * @return array
     */
    public function ratingAction(
        AnnouncementService $announcementService,
        AssessmentService $assessmentService,
        int $roomId,
        int $itemId,
        $vote
    ) {
        $announcement = $announcementService->getAnnouncement($itemId);
        if ($vote != 'remove') {
            $assessmentService->rateItem($announcement, $vote);
        } else {
            $assessmentService->removeRating($announcement);
        }
        $ratingDetail = $assessmentService->getRatingDetail($announcement);
        $ratingAverageDetail = $assessmentService->getAverageRatingDetail($announcement);
        $ratingOwnDetail = $assessmentService->getOwnRatingDetail($announcement);

        return array(
            'roomId' => $roomId,
            'announcement' => $announcement,
            'ratingArray' => array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/download")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        int $roomId)
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
     * @Route("/room/{roomId}/announcement/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/announcement/xhr/copy", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return
     * @throws Exception
     */
    public function xhrCopyAction(
        Request $request,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/announcement/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param $selectAll
     * @param integer[] $itemIds
     * @return cs_announcement_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        $announcementService = $this->get('commsy_legacy.announcement_service');

        if ($selectAll) {
            if ($request->query->has('announcement_filter')) {
                $currentFilter = $request->query->get('announcement_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $announcementService->setFilterConditions($filterForm);
            } else {
                $announcementService->hideDeactivatedEntries();
                $announcementService->hideInvalidEntries();
            }

            return $announcementService->getListAnnouncements($roomItem->getItemID());
        } else {
            return $announcementService->getAnnouncementsById($roomItem->getItemID(), $itemIds);
        }
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => true,
            'hide-invalid-entries' => true,
        ];

        return $this->createForm(AnnouncementFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_announcement_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    private function getDetailInfo(
        int $roomId,
        int $itemId
    ) {
        $infoArray = array();

        $announcementService = $this->get('commsy_legacy.announcement_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');

        $announcement = $announcementService->getAnnouncement($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $announcement;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
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
        $readerManager->getLatestReaderByUserIDArray($id_array, $announcement->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($announcement->getItemID(), $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $announcement->getModificationDate()) {
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
        $reader = $readerService->getLatestReader($announcement->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $announcement->getModificationDate()) {
            $readerList[$announcement->getItemId()] = 'changed';
        }

        $modifierList[$announcement->getItemId()] = $itemService->getAdditionalEditorsForItem($announcement);

        /** @var cs_announcement_item[] $announcements */
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
                $lastItemId = $announcements[sizeof($announcements) - 1]->getItemId();
            }
        }
        // mark annotations as read
        $annotationList = $announcement->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $announcementCategories = $announcement->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $announcementCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $ratingDetail = $assessmentService->getRatingDetail($announcement);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($announcement);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($announcement);
        }

        /** @var \cs_item $item */
        $item = $itemService->getItem($itemId);

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
        $infoArray['draft'] = $item->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['categories'] = $categories;
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];

        return $infoArray;
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
}
