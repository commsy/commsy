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
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\Mark\MarkAction;
use App\Action\MarkRead\MarkReadAction;
use App\Event\CommsyEditEvent;
use App\Filter\AnnouncementFilterType;
use App\Form\DataTransformer\AnnouncementTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\AnnouncementType;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\AnnouncementService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\LabelService;
use App\Utils\TopicService;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class AnnouncementController.
 */
#[Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'announcement')")]
class AnnouncementController extends BaseController
{
    protected AnnouncementService $announcementService;

    protected AnnotationService $annotationService;

    protected AssessmentService $assessmentService;

    protected CategoryService $categoryService;

    private SessionInterface $session;

    #[Required]
    public function setAnnotationService(AnnotationService $annotationService): void
    {
        $this->annotationService = $annotationService;
    }

    #[Required]
    public function setAnnouncementService(AnnouncementService $announcementService): void
    {
        $this->announcementService = $announcementService;
    }

    /**
     * @param mixed $assessmentService
     */
    #[Required]
    public function setAssessmentService(AssessmentService $assessmentService): void
    {
        $this->assessmentService = $assessmentService;
    }

    #[Required]
    public function setCategoryService(CategoryService $categoryService): void
    {
        $this->categoryService = $categoryService;
    }

    #[Required]
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    #[Route(path: '/room/{roomId}/announcement/feed/{start}/{sort}')]
    public function feedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $announcementFilter = $request->get('announcementFilter');
        if (!$announcementFilter) {
            $announcementFilter = $request->query->get('announcement_filter');
        }

        /** @var \cs_room_item $roomItem */
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

        if (empty($sort)) {
            $sort = $this->session->get('sortAnnouncements', 'date');
        }
        $this->session->set('sortAnnouncements', $sort);

        // get announcement list from manager service
        /** @var \cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        $allowedActions = [];
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = ['markread', 'mark', 'categorize', 'hashtag', 'activate', 'deactivate', 'save', 'delete'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'mark', 'save'];
            }
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        return $this->render('announcement/feed.html.twig', ['roomId' => $roomId, 'announcements' => $announcements, 'readerList' => $readerList, 'showRating' => $current_context->isAssessmentActive(), 'ratingList' => $ratingList, 'allowedActions' => $allowedActions]);
    }

    /**
     * @param null $sort
     */
    #[Route(path: '/room/{roomId}/announcement/shortfeed/{start}/{sort}')]
    public function shortfeedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        $sort = null
    ): Response {
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
        /** @var \cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($announcements as $announcement) {
                $itemIds[] = $announcement->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        return $this->render('announcement/shortfeed.html.twig', ['roomId' => $roomId, 'announcements' => $announcements, 'readerList' => $readerList, 'showRating' => $current_context->isAssessmentActive(), 'ratingList' => $ratingList]);
    }

    #[Route(path: '/room/{roomId}/announcement')]
    public function listAction(
        Request $request,
        int $roomId
    ): Response {
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

        $sort = $this->session->get('sortAnnouncements', 'date');

        // get announcement list from manager service
        $itemsCountArray = $this->announcementService->getCountArray($roomId);

        $usageInfo = false;
        /* @noinspection PhpUndefinedMethodInspection */
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('announcement')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('announcement');
            /* @noinspection PhpUndefinedMethodInspection */
            /* @noinspection PhpUndefinedMethodInspection */
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('announcement');
        }

        return $this->render('announcement/list.html.twig', ['roomId' => $roomId, 'form' => $filterForm->createView(), 'module' => 'announcement', 'itemsCountArray' => $itemsCountArray, 'showRating' => $roomItem->isAssessmentActive(), 'showHashTags' => $roomItem->withBuzzwords(), 'showAssociations' => $roomItem->withAssociations(), 'showCategories' => $roomItem->withTags(), 'usageInfo' => $usageInfo, 'isArchived' => $roomItem->getArchived(), 'user' => $this->legacyEnvironment->getCurrentUserItem(), 'sort' => $sort]);
    }

    #[Route(path: '/room/{roomId}/announcement/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlistAction(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ): Response {
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
        if ('none' === $sort || empty($sort)) {
            $sort = $this->session->get('sortAnnouncements', 'date');
        }
        /** @var \cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        foreach ($announcements as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
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
     * @return array
     */
    #[Route(path: '/room/{roomId}/announcement/{itemId}', requirements: ['itemId' => '\d+'])]
    public function detailAction(
        Request $request,
        LegacyMarkup $legacyMarkup,
        TopicService $topicService,
        AnnotationService $annotationService,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['announcement']->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId, $infoArray['announcement']->getItemId(),
            null, null);

        return $this->render('announcement_detail.html.twig', [
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
            'pathTopicItem' => $pathTopicItem
        ]);
    }

    #[Route(path: '/room/{roomId}/announcement/{itemId}/print')]
    public function printAction(
        PrintService $printService,
        $roomId,
        $itemId
    ): Response {
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
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/create')]
    #[Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'announcement')")]
    public function createAction(
        int $roomId
    ): RedirectResponse {
        // create new announcement item
        $announcementItem = $this->announcementService->getNewAnnouncement();
        $dateTime = new \DateTime('now');
        $announcementItem->setFirstDateTime($dateTime->format('Y-m-d H:i:s'));

        try {
            $dateTime->add(new \DateInterval('P2W'));
        } catch (Exception) {
        }

        $announcementItem->setSecondDateTime($dateTime->format('Y-m-d H:i:s'));
        $announcementItem->setDraftStatus(1);
        $announcementItem->setPrivateEditing(1);
        $announcementItem->save();

        return $this->redirectToRoute('app_announcement_detail',
            ['roomId' => $roomId, 'itemId' => $announcementItem->getItemId()]);
    }

    #[Route(path: '/room/{roomId}/announcement/{itemId}/edit')]
    #[Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'announcement')")]
    public function editAction(
        Request $request,
        LabelService $labelService,
        CategoryService $categoryService,
        AnnouncementTransformer $transformer,
        int $roomId,
        int $itemId
    ): Response {
        $form = null;
        /** @var \cs_item $item */
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $announcementItem = null;

        $isDraft = $item->isDraft();

        if ('announcement' == $item->getItemType()) {
            // get announcement from announcementService
            /** @var \cs_announcement_item $announcementItem */
            $announcementItem = $this->announcementService->getannouncement($itemId);
            $announcementItem->setDraftStatus($item->isDraft());
            if (!$announcementItem) {
                throw $this->createNotFoundException('No announcement found for id '.$roomId);
            }
            $formData = $transformer->transform($announcementItem);
            $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
            $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
            $form = $this->createForm(AnnouncementType::class, $formData, ['action' => $this->generateUrl('app_announcement_edit', ['roomId' => $roomId, 'itemId' => $itemId]), 'placeholderText' => '['.$this->translator->trans('insert title').']', 'categoryMappingOptions' => [
                'categories' => $labelService->getCategories($roomId),
                'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
            ], 'hashtagMappingOptions' => [
                'hashtags' => $labelService->getHashtags($roomId),
                'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            ], 'room' => $current_context]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ('save' == $saveType) {
                $announcementItem = $transformer->applyTransformation($announcementItem, $form->getData());

                // update modifier
                $announcementItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

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
                        $announcementItem->setTagListByID($categoryIds);
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
                        $announcementItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $announcementItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            }

            return $this->redirectToRoute('app_announcement_save', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($announcementItem), CommsyEditEvent::EDIT);

        return $this->render('announcement/edit.html.twig', ['form' => $form->createView(), 'announcement' => $announcementItem, 'isDraft' => $isDraft, 'currentUser' => $this->legacyEnvironment->getCurrentUserItem()]);
    }

    #[Route(path: '/room/{roomId}/announcement/{itemId}/save')]
    #[Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'announcement')")]
    public function saveAction(
        int $roomId,
        int $itemId
    ): Response {
        $tempItem = $this->announcementService->getannouncement($itemId);
        $itemArray = [$tempItem];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $this->eventDispatcher->dispatch(new CommsyEditEvent($tempItem), CommsyEditEvent::SAVE);

        return $this->render('announcement/save.html.twig', ['roomId' => $roomId, 'item' => $tempItem, 'modifierList' => $modifierList, 'userCount' => $infoArray['userCount'], 'readCount' => $infoArray['readCount'], 'readSinceModificationCount' => $infoArray['readSinceModificationCount'], 'showRating' => $infoArray['showRating']]);
    }

    #[Route(path: '/room/{roomId}/announcement/{itemId}/rating/{vote}')]
    public function ratingAction(
        int $roomId,
        int $itemId,
        $vote
    ): Response {
        $announcement = $this->announcementService->getAnnouncement($itemId);
        if ('remove' != $vote) {
            $this->assessmentService->rateItem($announcement, $vote);
        } else {
            $this->assessmentService->removeRating($announcement);
        }
        $ratingDetail = $this->assessmentService->getRatingDetail($announcement);
        $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($announcement);
        $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($announcement);

        return $this->render('announcement/rating.html.twig', ['roomId' => $roomId, 'announcement' => $announcement, 'ratingArray' => ['ratingDetail' => $ratingDetail, 'ratingAverageDetail' => $ratingAverageDetail, 'ratingOwnDetail' => $ratingOwnDetail]]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/download')]
    public function downloadAction(
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
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/mark', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkAction(
        Request $request,
        MarkAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorizeAction(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtagAction(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/activate', condition: 'request.isXmlHttpRequest()')]
    public function xhrActivateAction(
        Request $request,
        ActivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeactivateAction(
        Request $request,
        DeactivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/announcement/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeleteAction(
        DeleteAction $action,
        Request $request,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param \cs_room_item $roomItem
     * @param int[]         $itemIds
     *
     * @return \cs_announcement_item[]
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
     * @return FormInterface
     */
    private function createFilterForm(
        \cs_room_item $room
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
        $infoArray = [];

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
        $id_array = [];
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $announcement->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($announcement->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $announcement->getModificationDate()) {
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
        $reader = $this->readerService->getLatestReader($announcement->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $announcement->getModificationDate()) {
            $readerList[$announcement->getItemId()] = 'changed';
        }

        $modifierList[$announcement->getItemId()] = $this->itemService->getAdditionalEditorsForItem($announcement);

        /** @var \cs_announcement_item[] $announcements */
        $announcements = $this->announcementService->getListAnnouncements($roomId);
        $announcementList = [];
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
                    ++$counterBefore;
                }
                $announcementList[] = $tempAnnouncement;
                if ($tempAnnouncement->getItemID() == $announcement->getItemID()) {
                    $foundAnnouncement = true;
                }
                if (!$foundAnnouncement) {
                    $prevItemId = $tempAnnouncement->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $announcementList[] = $tempAnnouncement;
                    ++$counterAfter;
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
        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $this->categoryService->getTags($roomId);
            $announcementCategories = $announcement->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $announcementCategories);
        }

        $ratingDetail = [];
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
}
