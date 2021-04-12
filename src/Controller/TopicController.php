<?php

namespace App\Controller;

use App\Action\Download\DownloadAction;
use App\Event\CommsyEditEvent;
use App\Filter\TopicFilterType;
use App\Form\DataTransformer\TopicTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\TopicPathType;
use App\Form\Type\TopicType;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\TopicService;
use cs_room_item;
use cs_topic_item;
use cs_user_item;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @param Request $request
     * @param TopicService $topicService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        TopicService $topicService,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
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
        );
    }

    /**
     * @Route("/room/{roomId}/topic/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param ReaderService $readerService
     * @param TopicService $topicService
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        ReaderService $readerService,
        TopicService $topicService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $topicFilter = $request->get('topicFilter');
        if (!$topicFilter) {
            $topicFilter = $request->query->get('topic_filter');
        }

        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

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
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param CategoryService $categoryService
     * @param ItemService $itemService
     * @param ReaderService $readerService
     * @param TopicService $topicService
     * @param TranslatorInterface $translator
     * @param LegacyMarkup $legacyMarkup
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        AnnotationService $annotationService,
        CategoryService $categoryService,
        ItemService $itemService,
        ReaderService $readerService,
        TopicService $topicService,
        TranslatorInterface $translator,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        $topic = $topicService->getTopic($itemId);
        $infoArray = $this->getDetailInfo($annotationService, $itemService, $readerService, $topicService, $environment, $roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $topicCategories = $topic->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $topicCategories);
        }

        $alert = null;
        if ($infoArray['topic']->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $isLinkedToItems = false;
        if (!empty($topic->getAllLinkedItemIDArray())) {
            $isLinkedToItems = true;
        }

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

    private function getDetailInfo (
        AnnotationService $annotationService,
        ItemService $itemService,
        ReaderService $readerService,
        TopicService $topicService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $infoArray = array();
        $topic = $topicService->getTopic($itemId);

        $legacyEnvironment = $environment->getEnvironment();
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

        /** @var cs_user_item $current_user */
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
     * @param TopicService $topicService
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'topic')")
     */
    public function createAction(
        TopicService $topicService,
        int $roomId
    ) {
        // create new topic item
        $topicItem = $topicService->getNewtopic();
        $topicItem->setDraftStatus(1);
        $topicItem->save();

        return $this->redirectToRoute('app_topic_detail', array('roomId' => $roomId, 'itemId' => $topicItem->getItemId()));
    }


    /**
     * @Route("/room/{roomId}/topic/new")
     * @Template()
     * @param Request $request
     * @param int $roomId
     */
    public function newAction(
        Request $request,
        int $roomId
    ) {

    }


    /**
     * @Route("/room/{roomId}/topic/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     * @param Request $request
     * @param CategoryService $categoryService
     * @param ItemService $itemService
     * @param TopicService $topicService
     * @param TopicTransformer $transformer
     * @param TranslatorInterface $translator
     * @param ItemController $itemController
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        CategoryService $categoryService,
        ItemService $itemService,
        TopicService $topicService,
        TopicTransformer $transformer,
        TranslatorInterface $translator,
        ItemController $itemController,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        // get date from DateService
        $topicItem = $topicService->getTopic($itemId);
        if (!$topicItem) {
            throw $this->createNotFoundException('No topic found for id ' . $itemId);
        }
        $formData = $transformer->transform($topicItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['language'] = $legacyEnvironment->getCurrentContextItem()->getLanguage();
        $formData['draft'] = $isDraft;
        $form = $this->createForm(TopicType::class, $formData, array(
            'action' => $this->generateUrl('app_date_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '['.$translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $categoryService)
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

        $eventDispatcher->dispatch(new CommsyEditEvent($topicItem), 'commsy.edit');

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
     * @param ItemService $itemService
     * @param ReaderService $readerService
     * @param TopicService $topicService
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        ItemService $itemService,
        ReaderService $readerService,
        TopicService $topicService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $topic = $topicService->getTopic($itemId);
        
        $itemArray = array($topic);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $environment->getEnvironment();
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

        $eventDispatcher->dispatch(new CommsyEditEvent($topic), 'commsy.save');

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
     * @param AnnotationService $annotationService
     * @param ItemService $itemService
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param TopicService $topicService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        AnnotationService $annotationService,
        ItemService $itemService,
        PrintService $printService,
        ReaderService $readerService,
        TopicService $topicService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($annotationService, $itemService, $readerService, $topicService, $environment, $roomId, $itemId);

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
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param TopicService $topicService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return Response
     */
    public function printlistAction(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        ReaderService $readerService,
        TopicService $topicService,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $topicService->setFilterConditions($filterForm);
        }
        // get announcement list from manager service 
        $topics = $topicService->getListTopics($roomId);
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($topics as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
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
     * @param Request $request
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editPathAction(
        Request $request,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_topic_item $item */
        $item = $itemService->getTypedItem($itemId);
        $legacyEnvironment = $environment->getEnvironment();

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

        $eventDispatcher->dispatch(new CommsyEditEvent($item), 'commsy.edit');

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/topic/{itemId}/savepath")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'topic')")
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $itemId
     * @return array
     */
    public function savePathAction(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);
        $eventDispatcher->dispatch(new CommsyEditEvent($item), 'commsy.save');
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
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        int $roomId
    ) {
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
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/topic/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_topic_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
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
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm(
        $room
    ) {
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

    private function getTagDetailArray (
        $baseCategories,
        $itemCategories
    ) {
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
