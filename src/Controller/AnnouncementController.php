<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\ItemMarkRead;
use App\Event\CommsyEditEvent;
use App\Filter\AnnouncementFilterType;
use App\Form\Type\AnnotationType;
use App\Form\Type\AnnouncementType;
use App\Services\PrintService;
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
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $announcementFilter = $request->get('announcementFilter');
        if (!$announcementFilter) {
            $announcementFilter = $request->query->get('announcement_filter');
        }

        $roomService = $this->get('commsy_legacy.room_service');

        /** @var \cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

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
        /** @var \cs_announcement_item[] $announcements */
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $this->get('session')->set('sortAnnouncements', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
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
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/shortfeed/{start}/{sort}")
     * @Template()
     */
    public function shortfeedAction($roomId, $max = 10, $start = 0, $sort = NULL, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

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
        /** @var \cs_announcement_item[] $announcements */
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
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

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

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
            'showCategories' => $roomItem->withTags(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/print/{sort}", defaults={"sort" = "none"})
     */
    public function printlistAction($roomId, Request $request, $sort, PrintService $printService)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');
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
            /** @var \cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $sort);
        } elseif ($this->get('session')->get('sortAnnouncements')) {
            /** @var \cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $this->get('session')->get('sortAnnouncements'));
        } else {
            /** @var \cs_announcement_item[] $announcements */
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');
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
            'showCategories' => $roomItem->withTags(),
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
     */
    public function detailAction($roomId, $itemId, Request $request)
    {

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
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $markupService = $this->get('commsy_legacy.markup');
        $itemService = $this->get('commsy_legacy.item_service');
        $markupService->addFiles($itemService->getItemFileList($itemId));

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
            'roomCategories' => $infoArray['categories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'ratingArray' => $infoArray['ratingArray'],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}/print")
     */
    public function printAction($roomId, $itemId, PrintService $printService)
    {

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
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/announcement/create")
     */
    public function createAction($roomId)
    {
        $announcementService = $this->get('commsy_legacy.announcement_service');

        // create new announcement item
        $announcementItem = $announcementService->getNewAnnouncement();
        $dateTime = new \DateTime('now');
        $announcementItem->setFirstDateTime($dateTime->format('Y-m-d H:i:s'));

        try {
            $dateTime->add(new \DateInterval('P2W'));
        } catch (\Exception $e) {

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
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        /** @var \cs_item $item */
        $item = $itemService->getItem($itemId);

        $announcementService = $this->get('commsy_legacy.announcement_service');
        $transformer = $this->get('commsy_legacy.transformer.announcement');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $announcementItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        if ($item->getItemType() == 'announcement') {
            // get announcement from announcementService
            /** @var \cs_announcement_item $announcementItem */
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
                    'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service')),
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
     */
    public function saveAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');

        $announcementService = $this->get('commsy_legacy.announcement_service');

        $tempItem = NULL;

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
     **/
    public function ratingAction($roomId, $itemId, $vote)
    {
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $announcement = $announcementService->getAnnouncement($itemId);

        $assessmentService = $this->get('commsy_legacy.assessment_service');
        if ($vote != 'remove') {
            $assessmentService->rateItem($announcement, $vote);
        } else {
            $assessmentService->removeRating($announcement);
        }

        $assessmentService = $this->get('commsy_legacy.assessment_service');
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
     * @Route("/room/{roomId}/announcement/xhr/markread", condition="request.isXmlHttpRequest()")
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
     * @Route("/room/{roomId}/announcement/xhr/copy", condition="request.isXmlHttpRequest()")
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
     * @Route("/room/{roomId}/announcement/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_announcement_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
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
     * @param \cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
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

    private function getDetailInfo($roomId, $itemId)
    {
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

        /** @var \cs_announcement_item[] $announcements */
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
