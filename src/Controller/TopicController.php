<?php

namespace App\Controller;

use App\Action\Download\DownloadAction;
use App\Event\CommsyEditEvent;
use App\Filter\TopicFilterType;
use App\Form\Type\AnnotationType;
use App\Form\Type\TopicPathType;
use App\Form\Type\TopicType;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class TopicController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'topic')")
 */
class TopicController extends BaseController
{
    /**
     * @Route("/room/{roomId}/topic")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomService = $this->get('commsy_legacy.room_service');
        /** @var \cs_project_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the topic manager service
        $topicService = $this->get('commsy_legacy.topic_service');
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in topic manager
            $topicService->setFilterConditions($filterForm);
        }
        else {
            $topicService->hideDeactivatedEntries();
        }

        // get topic list from manager service 
        $itemsCountArray = $topicService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('topic') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('topic');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('topic');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'topic',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showAssociations' => false,
            'showCategories' => false,
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'language' => $legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
            'showAssociations' => false,
        );
    }
    
   /**
     * @Route("/room/{roomId}/topic/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0,  $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $topicFilter = $request->get('topicFilter');
        if (!$topicFilter) {
            $topicFilter = $request->query->get('topic_filter');
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the topic manager service
        $topicService = $this->get('commsy_legacy.topic_service');

        if ($topicFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($topicFilter);

            // set filter conditions in topic manager
            $topicService->setFilterConditions($filterForm);
        }
        else {
            $topicService->hideDeactivatedEntries();
        }

        // get topic list from manager service 
        $topics = $topicService->getListTopics($roomId, $max, $start);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $readerList = array();
        $allowedActions = array();
        foreach ($topics as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        return array(
            'roomId' => $roomId,
            'topics' => $topics,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
       );
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function detailAction($roomId, $itemId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $topicService = $this->get('commsy_legacy.topic_service');
        $topic = $topicService->getTopic($itemId);

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $topicCategories = $topic->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $topicCategories);
        }

        $alert = null;
        if ($infoArray['topic']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $isLinkedToItems = false;
        if (!empty($topic->getAllLinkedItemIDArray())) {
            $isLinkedToItems = true;
        }

        $itemService = $this->get('commsy_legacy.item_service');
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        return array(
            'roomId' => $roomId,
            'topic' => $infoArray['topic'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'topicList' => $infoArray['topicList'],
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
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'showHashtags' => $infoArray['showHashtags'],
            'language' => $infoArray['language'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $categories,
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isLinkedToItems' => $isLinkedToItems,
       );
    }


 

    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $topicService = $this->get('commsy_legacy.topic_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        $topic = $topicService->getTopic($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $topic;
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
        $roomItem = $roomService->getRoomItem($topic->getContextId());

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
        while ( $current_user ) {
           $id_array[] = $current_user->getItemID();
           $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array,$topic->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($topic->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $topic->getModificationDate() ) {
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
        $reader = $readerService->getLatestReader($topic->getItemId());
        if ( empty($reader) ) {
           $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $topic->getModificationDate() ) {
           $readerList[$topic->getItemId()] = 'changed';
        }
        
        $modifierList[$topic->getItemId()] = $itemService->getAdditionalEditorsForItem($topic);
        
        $topics = $topicService->getListTopics($roomId);
        $topicList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundTopic = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($topics as $tempTopic) {
            if (!$foundTopic) {
                if ($counterBefore > 5) {
                    array_shift($topicList);
                } else {
                    $counterBefore++;
                }
                $topicList[] = $tempTopic;
                if ($tempTopic->getItemID() == $topic->getItemID()) {
                    $foundTopic = true;
                }
                if (!$foundTopic) {
                    $prevItemId = $tempTopic->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $topicList[] = $tempTopic;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempTopic->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($topics)) {
            if ($prevItemId) {
                $firstItemId = $topics[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $topics[sizeof($topics)-1]->getItemId();
            }
        }
        // mark annotations as readed
        $annotationList = $topic->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);
        
        
        $infoArray['topic'] = $topic;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['topicList'] = $topicList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($topics);
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
        $infoArray['language'] = $legacyEnvironment->getCurrentContextItem()->getLanguage();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();


        return $infoArray;
    }


    /**
     * @Route("/room/{roomId}/topic/create")
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function createAction($roomId, Request $request)
    {
        $topicService = $this->get('commsy_legacy.topic_service');
        
        // create new topic item
        $topicItem = $topicService->getNewtopic();
        $topicItem->setDraftStatus(1);
        $topicItem->save();

        return $this->redirectToRoute('app_topic_detail', array('roomId' => $roomId, 'itemId' => $topicItem->getItemId()));
    }


    /**
     * @Route("/room/{roomId}/topic/new")
     * @Template()
     */
    public function newAction($roomId, Request $request)
    {

    }


    /**
     * @Route("/room/{roomId}/topic/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $topicService = $this->get('commsy_legacy.topic_service');
        $transformer = $this->get('commsy_legacy.transformer.topic');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        // get date from DateService
        $topicItem = $topicService->getTopic($itemId);
        if (!$topicItem) {
            throw $this->createNotFoundException('No topic found for id ' . $itemId);
        }
        $itemController = $this->get('commsy.item_controller');
        $formData = $transformer->transform($topicItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['language'] = $legacyEnvironment->getCurrentContextItem()->getLanguage();
        $formData['draft'] = $isDraft;
        $translator = $this->get('translator');
        $form = $this->createForm(TopicType::class, $formData, array(
            'action' => $this->generateUrl('app_date_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '['.$translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service'))
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
        ));
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $topicItem = $transformer->applyTransformation($topicItem, $form->getData());

                // update modifier
                $topicItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $topicItem->setTagListByID($formData['category_mapping']['categories']);
                }
                if ($hashtagsMandatory) {
                    $topicItem->setBuzzwordListByID($formData['hashtag_mapping']['hashtags']);
                }

                $topicItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('app_topic_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($topicItem));

        return array(
            'form' => $form->createView(),
            'topic' => $topicItem,
            'isDraft' => $isDraft,  
            'showHashtags' => $hashtagsMandatory,
            'language' => $legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        
        $topicService = $this->get('commsy_legacy.topic_service');
        
        $topic = $topicService->getTopic($itemId);
        
        $itemArray = array($topic);
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
		$readerManager->getLatestReaderByUserIDArray($id_array,$topic->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($topic->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $topic->getModificationDate() ) {
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

        $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($topic));

        return array(
            'roomId' => $roomId,
            'item' => $topic,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}/print")
     */
    public function printAction($roomId, $itemId, PrintService $printService)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('todo/detail_print.html.twig', [
            'roomId' => $roomId,
            'item' => $infoArray['topic'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
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
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'roomCategories' => 'roomCategories',
        ]);

        return $printService->buildPdfResponse($html);
    }
    
    /**
     * @Route("/room/{roomId}/topic/print")
     */
    public function printlistAction($roomId, Request $request, PrintService $printService)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the announcement manager service
        $topicService = $this->get('commsy_legacy.topic_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $topicService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $topics = $topicService->getListTopics($roomId);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($topics as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($topics as $topic) {
                $itemIds[] = $topic->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service 
        $itemsCountArray = $topicService->getCountArray($roomId);

        $html = $this->renderView('topic/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'topic',
            'announcements' => $topics,
            'readerList' => $readerList,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'ratingList' => $ratingList,
            'showWorkflow' => $current_context->withWorkflow(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}/editpath")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function editPathAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        /** @var \cs_topic_item $item */
        $item = $itemService->getTypedItem($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $formData = array();

        $pathElements = [];
        $pathElementsAttr = [];

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);

        // get all linked items
        $linkedItemArray = [];
        foreach ($item->getPathItemList()->to_array() as $pathElement) {
            $formData['path'][] = $pathElement->getItemId();
            $linkedItemArray[] = $pathElement;
        }
        foreach ($itemManager->getItemList($item->getAllLinkedItemIDArray())->to_array() as $linkedItem) {
            $inPath = false;
            foreach ($linkedItemArray as $linkedItemPath) {
                if ($linkedItemPath->getItemId() == $linkedItem->getItemId()) {
                    $inPath = true;
                    break;
                }
            }
            if (!$inPath) {
                $linkedItemArray[] = $linkedItem;
            }
        }

        foreach ($linkedItemArray as $linkedItem) {
            $typedLinkedItem = $itemService->getTypedItem($linkedItem->getItemId());
            $pathElements[$typedLinkedItem->getTitle()] = $typedLinkedItem->getItemId();
            $pathElementsAttr[$typedLinkedItem->getTitle()] = ['title' => $typedLinkedItem->getTitle(), 'type' => $typedLinkedItem->getItemType()];
        }



        $form = $this->createForm(TopicPathType::class, $formData, array(
            'action' => $this->generateUrl('app_topic_editpath', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'pathElements' => $pathElements,
            'pathElementsAttr' => $pathElementsAttr,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $linkManager = $legacyEnvironment->getLinkItemManager();

                $formData = $form->getData();

                $formDataPath = [];
                if (isset($formData['path'])) {
                    $formDataPath = $formData['path'];
                }
                if (!empty($formDataPath)) {
                    $sortingPlace = 1;
                    if (isset($formData['pathOrder'])) {
                        foreach (explode(',', $formData['pathOrder']) as $orderItemId) {
                            if ($linkItem = $linkManager->getItemByFirstAndSecondID($item->getItemId(), $orderItemId, true)) {
                                if (in_array($orderItemId, $formDataPath)) {
                                    $linkItem->setSortingPlace($sortingPlace);
                                    $linkItem->save();
                                    $sortingPlace++;
                                }
                            }
                        }
                    }
                    $item->activatePath();
                    $item->save();
                } else {
                    $item->deactivatePath();
                    $item->save();
                }

                if (isset($formData['pathOrder'])) {
                    foreach (explode(',', $formData['pathOrder']) as $orderItemId) {
                        if ($linkItem = $linkManager->getItemByFirstAndSecondID($item->getItemId(), $orderItemId)) {
                            if (!in_array($orderItemId, $formDataPath)) {
                                $linkManager->cleanSortingPlaces($itemService->getTypedItem($orderItemId));
                            }
                        }
                    }
                }

            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('app_topic_savepath', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($item));

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}/savepath")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function savePathAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($item));

        $isLinkedToItems = false;
        if (!empty($item->getAllLinkedItemIDArray())) {
            $isLinkedToItems = true;
        }

        return [
            'topic' => $itemService->getTypedItem($itemId),
            'isLinkedToItems' => $isLinkedToItems,
        ];
    }

    /**
     * @Route("/room/{roomId}/topic/download")
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
     * @Route("/room/{roomId}/topic/xhr/markread", condition="request.isXmlHttpRequest()")
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
     * @Route("/room/{roomId}/topic/xhr/delete", condition="request.isXmlHttpRequest()")
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
     * @return \cs_topic_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        $topicService = $this->get('commsy_legacy.topic_service');

        if ($selectAll) {
            if ($request->query->has('topic_filter')) {
                $currentFilter = $request->query->get('topic_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $topicService->setFilterConditions($filterForm);
            } else {
                $topicService->hideDeactivatedEntries();
            }

            return $topicService->getListTopics($roomItem->getItemID());
        } else {
            return $topicService->getTopicsById($roomItem->getItemID(), $itemIds);
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
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(TopicFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_topic_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
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
}
