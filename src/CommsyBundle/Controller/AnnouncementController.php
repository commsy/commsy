<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\AnnouncementFilterType;
use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\AnnouncementType;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Event\CommsyEditEvent;

class AnnouncementController extends Controller
{
    // setup filter form default values
    private $defaultFilterValues = array(
        'hide-deactivated-entries' => true,
        'hide-invalid-entries' => true,
    );
    /**
     * @Route("/room/{roomId}/announcement/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0,  $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $announcementFilter = $request->get('announcementFilter');
        if (!$announcementFilter) {
            $announcementFilter = $request->query->get('announcement_filter');
        }
        
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

        if ($announcementFilter) {
            $filterForm = $this->createForm(AnnouncementFilterType::class, $this->defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_announcement_list', array(
                    'roomId' => $roomId,
                )),
                'hasHashtags' => $roomItem->withBuzzwords(),
                'hasCategories' => $roomItem->withTags(),
            ));
    
            // manually bind values from the request
            $filterForm->submit($announcementFilter);
    
            // apply filter
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service 
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
    public function shortfeedAction($roomId, $max = 10, $start = 0,  $sort = NULL, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(AnnouncementFilterType::class, $this->defaultFilterValues, array(
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
        } else {
            $announcementService->setDateLimit();
            $sort = 'date';
        }

        $announcementService->hideDeactivatedEntries();

        // get announcement list from manager service 
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

        $filterForm = $this->createForm(AnnouncementFilterType::class, $this->defaultFilterValues, array(
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
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service 
        $itemsCountArray = $announcementService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('announcement') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('announcement');
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
    public function printlistAction($roomId, Request $request, $sort)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(AnnouncementFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // get the announcement manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $numAllAnnouncements = $announcementService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in announcement manager
            $announcementService->setFilterConditions($filterForm);
        } else {
            $announcementService->hideDeactivatedEntries();
            $announcementService->hideInvalidEntries();
        }

        // get announcement list from manager service 
        if ($sort != "none") {
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $sort);
        }
        elseif ($this->get('session')->get('sortAnnouncements')) {
            $announcements = $announcementService->getListAnnouncements($roomId, $numAllAnnouncements, 0, $this->get('session')->get('sortAnnouncements'));
        }
        else {
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


         $html = $this->renderView('CommsyBundle:Announcement:listPrint.html.twig', [
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

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
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
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('CommsyBundle:Announcement:detailPrint.html.twig', [
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

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }


    
    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $itemService = $this->get('commsy_legacy.item_service');

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

        $roomService = $this->get('commsy_legacy.room_service');
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomService->getRoomItem($announcement->getContextId());
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
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
        $infoArray['categories'] = $categories;
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];
        
        return $infoArray;
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
        $announcementItem = $announcementService->getNewAnnouncement();
        // $announcementItem->setTitle('['.$translator->trans('insert title').']');
        $dateTime = new \DateTime('now');
        $announcementItem->setFirstDateTime($dateTime->format('Y-m-d H:i:s'));
        $dateTime->add(new \DateInterval('P2W'));
        $announcementItem->setSecondDateTime($dateTime->format('Y-m-d H:i:s'));
        $announcementItem->setDraftStatus(1);
        $announcementItem->setPrivateEditing(1);
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
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $transformer = $this->get('commsy_legacy.transformer.announcement');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $announcementItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();
        
        if ($item->getItemType() == 'announcement') {
            // get announcement from announcementService
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
                'action' => $this->generateUrl('commsy_announcement_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '['.$translator->trans('insert title').']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service')),
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                    'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('commsy_hashtag_add', ['roomId' => $roomId]),
                ],
            ));
        } 
        
        $form->handleRequest($request);
        
        $submittedFormData = $form->getData();
        
        if ($form->isValid()) {
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
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_announcement_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($announcementItem));

        return array(
            'form' => $form->createView(),
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }



    /**
     * @Route("/room/{roomId}/announcement/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
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
        
        $result = [];
        
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
                       $readerManager->markRead($annotationItem->getItemId(), '0');
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
            $downloadService = $this->get('commsy_legacy.download_service');
        
            $zipFile = $downloadService->zipFile($roomId, $selectedIds);
    
            $response = new BinaryFileResponse($zipFile);
            $response->deleteFileAfterSend(true);
    
            $filename = 'CommSy_Announcement.zip';
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
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
        
        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }

    /**
     * @Route("/room/{roomId}/announcement/{itemId}/download")
     */
    public function downloadAction($roomId, $itemId)
    {
        $downloadService = $this->get('commsy_legacy.download_service');
        
        $zipFile = $downloadService->zipFile($roomId, $itemId);

        $response = new BinaryFileResponse($zipFile);
        $response->deleteFileAfterSend(true);

        $filename = 'CommSy_Announcement.zip';
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
    
    /**
     * @Route("/room/{roomId}/announcement/{itemId}/rating/{vote}")
     * @Template()
     **/
    public function ratingAction($roomId, $itemId, $vote, Request $request)
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
            'ratingArray' =>  array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

}
