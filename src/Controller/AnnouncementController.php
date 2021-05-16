<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteGeneric;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\ItemMarkRead;
use App\Action\MarkRead\MarkReadAction;
use App\Event\CommsyEditEvent;
use App\Filter\AnnouncementFilterType;
use App\Form\DataTransformer\AnnouncementTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\AnnouncementType;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnouncementService;
use App\Utils\AnnotationService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
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
     * @var AnnouncementService
     */
    protected AnnouncementService $announcementService;

    /**
     * @var AnnotationService
     */
    protected AnnotationService $annotationService;

    /**
     * @var AssessmentService
     */
    protected AssessmentService $assessmentService;

    /**
     * @var CategoryService
     */
    protected CategoryService $categoryService;


    /**
     * @required
     * @param AnnotationService $annotationService
     */
    public function setAnnotationService(AnnotationService $annotationService): void
    {
        $this->annotationService = $annotationService;
    }

    /**
     * @required
     * @param AnnouncementService $announcementService
     */
    public function setAnnouncementService( AnnouncementService$announcementService): void
    {
        $this->announcementService = $announcementService;
    }

    /**
     * @required
     * @param mixed $assessmentService
     */
    public function setAssessmentService(AssessmentService $assessmentService): void
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * @required
     * @param CategoryService $categoryService
     */
    public function setCategoryService(CategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    /**
     * @Route("/room/{roomId}/announcement/feed/{start}/{sort}")
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
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $announcementFilter = $request->get('announcementFilter');
        if (!$announcementFilter) {
            $announcementFilter = $request->query->get('announcement_filter');
        }

        /** @var cs_room_item $roomItem */
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($announcementFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($announcementFilter);

            // apply filter
            $this->announcementService->setFilterConditions($filterForm);
        } else {
            $this->announcementService->hideDeactivatedEntries();
            $this->announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service
        /** @var cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $this->get('session')->set('sortAnnouncements', $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
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
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
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
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param null $sort
     * @return array|void
     */
    public function shortfeedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        $sort = NULL
    ) {
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $this->announcementService->setFilterConditions($filterForm);
        } else {
            $this->announcementService->setDateLimit();
            $sort = 'date';
        }

        $this->announcementService->hideDeactivatedEntries();

        // get announcement list from manager service
        /** @var cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
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
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $this->announcementService->setFilterConditions($filterForm);
        } else {
            $this->announcementService->hideDeactivatedEntries();
            $this->announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service 
        $itemsCountArray = $this->announcementService->getCountArray($roomId);

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
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param PrintService $printService
     * @param int $roomId
     * @param $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        PrintService $printService,
        int $roomId,
        $sort
    ) {
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        $numAllAnnouncements = $this->announcementService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $this->announcementService->setFilterConditions($filterForm);
        } else {
            $this->announcementService->hideDeactivatedEntries();
            $this->announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service
        if ($sort != "none") {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $this->announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $sort);
        } elseif ($this->get('session')->get('sortAnnouncements')) {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $this->announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $this->get('session')->get('sortAnnouncements'));
        } else {
            /** @var cs_announcement_item[] $announcements */
            $announcements = $this->announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, 'date');
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service 
        $itemsCountArray = $this->announcementService->getCountArray($roomId);

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
     * @param TopicService $topicService
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        LegacyMarkup $legacyMarkup,
        TopicService $topicService,
        AnnotationService $annotationService,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['announcement']->isLocked()) {

            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId, $infoArray['announcement']->getItemId(), null, null);

        return array(
            'roomId' => $roomId,
            'announcement' => $infoArray['announcement'],
            'amountAnnotations' => sizeof($amountAnnotations),
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
     * @param int $roomId
     * @return RedirectResponse
     * @throws Exception
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'announcement')")
     */
    public function createAction(
        int $roomId
    ) {
        // create new announcement item
        $announcementItem = $this->announcementService->getNewAnnouncement();
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
     * @param AnnouncementTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        ItemController $itemController,
        AnnouncementTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        /** @var \cs_item $item */
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $announcementItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        if ($item->getItemType() == 'announcement') {
            // get announcement from announcementService
            /** @var cs_announcement_item $announcementItem */
            $announcementItem = $this->announcementService->getannouncement($itemId);
            $announcementItem->setDraftStatus($item->isDraft());
            if (!$announcementItem) {
                throw $this->createNotFoundException('No announcement found for id ' . $roomId);
            }
            $formData = $transformer->transform($announcementItem);
            $formData['categoriesMandatory'] = $categoriesMandatory;
            $formData['hashtagsMandatory'] = $hashtagsMandatory;
            $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
            $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $this->legacyEnvironment);
            $form = $this->createForm(AnnouncementType::class, $formData, array(
                'action' => $this->generateUrl('app_announcement_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $this->categoryService),
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                    'hashTagPlaceholderText' => $this->translator->trans('Hashtag', [], 'hashtag'),
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
                $announcementItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

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

        $this->eventDispatcher->dispatch('commsy.edit', new CommsyEditEvent($announcementItem));

        return array(
            'form' => $form->createView(),
            'announcement' => $announcementItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }


    /**
     * @Route("/room/{roomId}/announcement/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'announcement')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $tempItem = $this->announcementService->getannouncement($itemId);
        $itemArray = array($tempItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $this->eventDispatcher->dispatch(new CommsyEditEvent($tempItem), CommsyEditEvent::SAVE);

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
     * @param int $roomId
     * @param int $itemId
     * @param $vote
     * @return array
     */
    public function ratingAction(
        int $roomId,
        int $itemId,
        $vote
    ) {
        $announcement = $this->announcementService->getAnnouncement($itemId);
        if ($vote != 'remove') {
            $this->assessmentService->rateItem($announcement, $vote);
        } else {
            $this->assessmentService->removeRating($announcement);
        }
        $ratingDetail = $this->assessmentService->getRatingDetail($announcement);
        $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($announcement);
        $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($announcement);

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
        MarkReadAction $markReadAction,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        return $markReadAction->execute($room, $items);
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
        DeleteAction $action,
        Request $request,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
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
        if ($selectAll) {
            if ($request->query->has('announcement_filter')) {
                $currentFilter = $request->query->get('announcement_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->announcementService->setFilterConditions($filterForm);
            } else {
                $this->announcementService->hideDeactivatedEntries();
                $this->announcementService->hideInvalidEntries();
            }

            return $this->announcementService->getListAnnouncements($roomItem->getItemID());
        } else {
            return $this->announcementService->getAnnouncementsById($roomItem->getItemID(), $itemIds);
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
            'hide-deactivated-entries' => 'only_activated',
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

        $announcement = $this->announcementService->getAnnouncement($itemId);

        $item = $announcement;
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

        $readerList = array();
        $modifierList = array();
        $reader = $this->readerService->getLatestReader($announcement->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $announcement->getModificationDate()) {
            $readerList[$announcement->getItemId()] = 'changed';
        }

        $modifierList[$announcement->getItemId()] = $this->itemService->getAdditionalEditorsForItem($announcement);

        /** @var cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId);
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
        $this->annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->categoryService->getTags($roomId);
            $announcementCategories = $announcement->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $announcementCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $this->assessmentService->getRatingDetail($announcement);
            $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($announcement);
            $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($announcement);
        }

        /** @var \cs_item $item */
        $item = $this->itemService->getItem($itemId);

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
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
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
