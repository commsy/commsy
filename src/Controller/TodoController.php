<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\TodoStatus\TodoStatusAction;
use App\Form\DataTransformer\TodoTransformer;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\TodoService;
use App\Utils\TopicService;
use cs_item;
use cs_room_item;
use cs_step_item;
use cs_todo_item;
use cs_user_item;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use App\Filter\TodoFilterType;
use App\Form\Type\TodoType;
use App\Form\Type\StepType;
use App\Form\Type\AnnotationType;

use App\Event\CommsyEditEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class TodoController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'todo')")
 */
class TodoController extends BaseController
{
    /**
     * @Route("/room/{roomId}/todo")
     * @Template()
     * @param Request $request
     * @param RoomService $roomService
     * @param TodoService $todoService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        RoomService $roomService,
        TodoService $todoService,
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
            // set filter conditions in todo manager
            $todoService->setFilterConditions($filterForm);
        } else {
            $todoService->hideDeactivatedEntries();
            $todoService->hideCompletedEntries();
        }

        // get todo list from manager service 
        $itemsCountArray = $todoService->getCountArray($roomId);
 
        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('todo') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('todo');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('todo');
        }
 
        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'todo',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'statusList' => $roomItem->getExtraToDoStatusArray(),
            'usageInfo' => $usageInfo,
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/create")
     * @param TodoService $todoService
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'todo')")
     */
    public function createAction(
        TodoService $todoService,
        int $roomId
    ) {
        // create new todo item
        $todoItem = $todoService->getNewTodo();
        $todoItem->setDraftStatus(1);
        $todoItem->setPrivateEditing('1');
        $todoItem->save();

        return $this->redirectToRoute('app_todo_detail', array('roomId' => $roomId, 'itemId' => $todoItem->getItemId()));
    }

    /**
     * @Route("/room/{roomId}/todo/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param ReaderService $readerService
     * @param RoomService $roomService
     * @param TodoService $todoService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        AssessmentService $assessmentService,
        ReaderService $readerService,
        RoomService $roomService,
        TodoService $todoService,
        LegacyEnvironment $environment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'duedate_rev'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $todoFilter = $request->get('todoFilter');
        if (!$todoFilter) {
            $todoFilter = $request->query->get('todo_filter');
        }

        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($todoFilter) {
            $filterForm = $this->createFilterForm($roomItem);
    
            // manually bind values from the request
            $filterForm->submit($todoFilter);
    
            // apply filter
            $todoService->setFilterConditions($filterForm);
        } else {
            $todoService->hideDeactivatedEntries();
            $todoService->hideCompletedEntries();
        }

        // get todo list from manager service
        /** @var cs_todo_item[] $todos */
        $todos = $todoService->getListTodos($roomId, $max, $start, $sort);

        $this->get('session')->set('sortTodos', $sort);

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());

            if ($this->isGranted('ITEM_EDIT', $item->getItemID()) or
                ($this->isGranted('ITEM_ENTER',$roomId)) and $roomItem->getType() == 'userroom'
            or ($roomItem->getType() == 'project' and $this->isGranted('ITEM_PARTICIPATE', $roomId))) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete', 'markpending', 'markinprogress', 'markdone');
                
                $statusArray = $roomItem->getExtraToDoStatusArray();
                foreach ($statusArray as $tempStatus) {
                    $allowedActions[$item->getItemID()][] = 'mark'.$tempStatus;
                }
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($todos as $todo) {
                $itemIds[] = $todo->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        return array(
            'roomId' => $roomId,
            'todos' => $todos,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param AssessmentService $assessmentService
     * @param CategoryService $categoryService
     * @param ItemService $itemService
     * @param ReaderService $readerService
     * @param TodoService $todoService
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
        AssessmentService $assessmentService,
        CategoryService $categoryService,
        ItemService $itemService,
        ReaderService $readerService,
        TodoService $todoService,
        TopicService $topicService,
        TranslatorInterface $translator,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $todo = $todoService->getTodo($itemId);
        /** @var cs_step_item[] $steps */
        $steps = $todo->getStepItemList()->to_array();

        $legacyEnvironment = $environment->getEnvironment();

        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($todo->getItemID());
        if(empty($reader) || $reader['read_date'] < $todo->getModificationDate()) {
            $reader_manager->markRead($todo->getItemID(), $todo->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($todo->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $todo->getModificationDate()) {
            $noticed_manager->markNoticed($todo->getItemID(), $todo->getVersionID());
        }

        // mark annotations as read
        $annotationList = $todo->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $stepList = $todo->getStepItemList();

        $stepItem = $stepList->getFirst();
        while ( $stepItem ) {
            $reader = $reader_manager->getLatestReader($stepItem->getItemID());
            if ( empty($reader) || $reader['read_date'] < $stepItem->getModificationDate() ) {
                $reader_manager->markRead($stepItem->getItemID(), 0);
            }

            $noticed = $noticed_manager->getLatestNoticed($stepItem->getItemID());
            if ( empty($noticed) || $noticed['read_date'] < $stepItem->getModificationDate() ) {
                $noticed_manager->markNoticed($stepItem->getItemID(), 0);
            }

            $stepItem = $stepList->getNext();
        }

        $itemArray = array($todo);

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
		$readerManager->getLatestReaderByUserIDArray($id_array,$todo->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($todo->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $todo->getModificationDate() ) {
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

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $todoCategories = $todo->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $todoCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $assessmentService->getRatingDetail($todo);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($todo);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($todo);
        }

        $timeSpendSum = 0;
        foreach ($steps as $step) {
            $timeSpendSum += $step->getMinutes();
        }

        $alert = null;
        if ($todoService->getTodo($itemId)->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId, $todoService->getTodo($itemId)->getItemId(), null, null);

        return array(
            'roomId' => $roomId,
            'todo' => $todoService->getTodo($itemId),
            'amountAnnotations' => sizeof($amountAnnotations),
            'stepList' => $steps,
            'timeSpendSum' => $timeSpendSum,
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
            'showAssociations' => $current_context->withAssociations(),
            'buzzExpanded' => $current_context->isBuzzwordShowExpanded(),
            'catzExpanded' => $current_context->isTagsShowExpanded(),
            'roomCategories' => $categories,
            'showRating' => $current_context->isAssessmentActive(),
            'ratingArray' => $current_context->isAssessmentActive() ? [
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ] : [],
            'isParticipating' => $todo->isProcessor($legacyEnvironment->getCurrentUserItem()),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/createstep")
     * @Template("todo/edit_step.html.twig")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo') or is_granted('ITEM_USERROOM', itemId) or is_granted('ITEM_PARTICIPATE', itemId)")
     * @param TodoService $todoService
     * @param TodoTransformer $transformer
     * @param TranslatorInterface $translator
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function createStepAction(
        TodoService $todoService,
        TodoTransformer $transformer,
        TranslatorInterface $translator,
        int $roomId,
        int $itemId
    ) {
        $step = $todoService->getNewStep();
        $step->setDraftStatus(1);
        $step->setTodoID($itemId);
        $step->save();

        $formData = $transformer->transform($step);
        $form = $this->createForm(StepType::class, $formData, array(
            'action' => $this->generateUrl('app_todo_editstep', [
                'roomId' => $roomId,
                'itemId' => $step->getItemID()
            ]),
            'placeholderText' => '['.$translator->trans('insert title').']',
        ));

        return [
            'form' => $form->createView(),
            'step' => $step,
            'new' => true,
        ];
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/editstep")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     * @param Request $request
     * @param ItemService $itemService
     * @param TodoService $todoService
     * @param TodoTransformer $transformer
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editStepAction(
        Request $request,
        ItemService $itemService,
        TodoService $todoService,
        TodoTransformer $transformer,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $item = $itemService->getItem($itemId);

        // get step
        $step = $todoService->getStep($itemId);

        $formData = $transformer->transform($step);

        $form = $this->createForm(StepType::class, $formData, [
            'action' => $this->generateUrl('app_todo_editstep', [
                'roomId' => $roomId,
                'itemId' => $step->getItemID()
            ]),
            'placeholderText' => '['.$translator->trans('insert title').']',
        ]);

        $eventDispatcher->dispatch(new CommsyEditEvent($step->getLinkedItem()), CommsyEditEvent::EDIT);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked()) {
                if ($form->isSubmitted() && $form->isValid()) {

                    $formData = $form->getData();

                    // update title
                    $step->setTitle($formData['title']);

                    // spend hours
                    $step->setMinutes($formData['time_spend']['hour'] * 60 + $formData['time_spend']['minute']);

                    if ($item->isDraft()) {
                        $item->setDraftStatus(0);
                        $item->saveAsItem();
                    }

                    // update modifier
                    $step->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                    $step->save();

                    $step->getLinkedItem()->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                    // this will also update the todo item's modification date to indicate that it has changes
                    $step->getLinkedItem()->save();

                    $eventDispatcher->dispatch(new CommsyEditEvent($step->getLinkedItem()), CommsyEditEvent::SAVE);

                    return $this->redirectToRoute('app_todo_detail', [
                        'roomId' => $roomId,
                        'itemId' => $step->getTodoID(),
                        '_fragment' => 'step' . $itemId,
                    ]);
                }
            } else {
                if ($form->get('cancel')->isClicked()) {
                    // remove not saved item
                    $step->delete();
                    $step->save();

                    return $this->redirectToRoute('app_todo_detail', [
                        'roomId' => $roomId,
                        'itemId' => $step->getTodoID(),
                    ]);
                }
            }
        }

        return [
            'form' => $form->createView(),
            'step' => $step,
        ];
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     * @param Request $request
     * @param CategoryService $categoryService
     * @param ItemService $itemService
     * @param RoomService $roomService
     * @param TodoService $todoService
     * @param TodoTransformer $transformer
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
        RoomService $roomService,
        TodoService $todoService,
        TodoTransformer $transformer,
        TranslatorInterface $translator,
        ItemController $itemController,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_item $item */
        $item = $itemService->getItem($itemId);

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        $roomItem = $roomService->getRoomItem($roomId);

        $todoItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        $statusChoices = array(
            $translator->trans('pending', [], 'todo') => '1',
            $translator->trans('in progress', [], 'todo') => '2',
            $translator->trans('done', [], 'todo') => '3',
        );

        foreach ($roomItem->getExtraToDoStatusArray() as $key => $value) {
            $statusChoices[$value] = $key;
        }

        $formOptions = array(
            'action' => $this->generateUrl('app_todo_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'statusChoices' => $statusChoices,
            'placeholderText' => '['.$translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $categoryService)
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
        );

        $todoItem = $todoService->getTodo($itemId);
        if (!$todoItem) {
            throw $this->createNotFoundException('No todo found for id ' . $itemId);
        }



        $formData = $transformer->transform($todoItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['draft'] = $isDraft;

        $form = $this->createForm(TodoType::class, $formData, $formOptions);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $todoItem = $transformer->applyTransformation($todoItem, $form->getData());

                // update modifier
                $todoItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $todoItem->setTagListByID($formData['category_mapping']['categories']);
                }
                if ($hashtagsMandatory) {
                    $todoItem->setBuzzwordListByID($formData['hashtag_mapping']['hashtags']);
                }

                $todoItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            }

            return $this->redirectToRoute('app_todo_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($todoItem), CommsyEditEvent::EDIT);

        return array(
            'form' => $form->createView(),
            'todo' => $todoItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     * @param ItemService $itemService
     * @param TodoService $todoService
     * @param EventDispatcherInterface $eventDispatcher
     * @param ReaderService $readerService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        ItemService $itemService,
        TodoService $todoService,
        EventDispatcherInterface $eventDispatcher,
        ReaderService $readerService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);
        if ($item->getItemType() == 'todo') {
            $typedItem = $todoService->getTodo($itemId);
            $eventDispatcher->dispatch(new CommsyEditEvent($typedItem), CommsyEditEvent::SAVE);
        } else if ($item->getItemType() == 'step') {
            $typedItem = $todoService->getStep($itemId);
            $eventDispatcher->dispatch(new CommsyEditEvent($typedItem->getLinkedItem()), CommsyEditEvent::SAVE);
        }
        
        $itemArray = array($typedItem);
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

        /** @var cs_user_item $current_user */
        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}

		$readerManager->getLatestReaderByUserIDArray($id_array,$typedItem->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($typedItem->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $typedItem->getModificationDate() ) {
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

        return array(
            'roomId' => $roomId,
            'item' => $typedItem,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/rating/{vote}")
     * @Template()
     * @param AssessmentService $assessmentService
     * @param TodoService $todoService
     * @param int $roomId
     * @param int $itemId
     * @param $vote
     * @return array
     */
    public function ratingAction(
        AssessmentService $assessmentService,
        TodoService $todoService,
        int $roomId,
        int $itemId,
        $vote
    ) {
        $todo = $todoService->getTodo($itemId);
        if ($vote != 'remove') {
            $assessmentService->rateItem($todo, $vote);
        } else {
            $assessmentService->removeRating($todo);
        }
        $ratingDetail = $assessmentService->getRatingDetail($todo);
        $ratingAverageDetail = $assessmentService->getAverageRatingDetail($todo);
        $ratingOwnDetail = $assessmentService->getOwnRatingDetail($todo);
        
        return array(
            'roomId' => $roomId,
            'todo' => $todo,
            'ratingArray' =>  array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/print")
     * @param AssessmentService $assessmentService
     * @param ItemService $itemService
     * @param CategoryService $categoryService
     * @param ReaderService $readerService
     * @param TodoService $todoService
     * @param PrintService $printService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        AssessmentService $assessmentService,
        ItemService $itemService,
        CategoryService $categoryService,
        ReaderService $readerService,
        TodoService $todoService,
        PrintService $printService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($assessmentService, $categoryService, $itemService, $readerService, $todoService, $legacyEnvironment, $roomId, $itemId);
        // annotation form
        $form = $this->createForm(AnnotationType::class);
        $html = $this->renderView('todo/detail_print.html.twig', [
            'roomId' => $roomId,
            'item' => $infoArray['todo'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'stepList' => $infoArray['stepList'],
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
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => 'roomCategories',
        ]);
        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/todo/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param RoomService $roomService
     * @param TodoService $todoService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        ReaderService $readerService,
        RoomService $roomService,
        TodoService $todoService,
        LegacyEnvironment $environment,
        int $roomId,
        string $sort
    ) {
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        $numAllTodos = $todoService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $todoService->setFilterConditions($filterForm);
        }

        // get todo list from manager service
        if ($sort != "none") {
            /** @var cs_todo_item[] $todos */
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, $sort);
        }
        elseif ($this->get('session')->get('sortTodos')) {
            /** @var cs_todo_item[] $todos */
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, $this->get('session')->get('sortTodos'));
        }
        else {
            /** @var cs_todo_item[] $todos */
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, 'date');
        }

        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($todos as $todo) {
                $itemIds[] = $todo->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service 
        $itemsCountArray = $todoService->getCountArray($roomId);

        $html = $this->renderView('todo/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'todo',
            'todos' => $todos,
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
     * @Route("/room/{roomId}/todo/{itemId}/participate")
     * @param TodoService $todoService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo') or is_granted('ITEM_PARTICIPATE', itemId)")
     */
    public function participateAction(
        TodoService $todoService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $todo = $todoService->getTodo($itemId);
        $legacyEnvironment = $environment->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        if (!$todo->isProcessor($legacyEnvironment->getCurrentUserItem())) {
            $todo->addProcessor($currentUser);
        } else {
            $todo->removeProcessor($currentUser);
        }
        return $this->redirectToRoute('app_todo_detail', [
            'roomId' => $roomId,
            'itemId' => $itemId,
        ]);
    }

    /**
     * @Route("/room/{roomId}/todo/download")
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
     * @Route("/room/{roomId}/todo/xhr/markread", condition="request.isXmlHttpRequest()")
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

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);

    }

    /**
     * @Route("/room/{roomId}/todo/xhr/copy", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return Response
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
     * @Route("/room/{roomId}/todo/xhr/delete", condition="request.isXmlHttpRequest()")
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

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/todo/xhr/status", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrStatusAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        if (!$request->request->has('payload')) {
            throw new Exception('payload information not provided');
        }

        $payload = $request->request->get('payload');
        if (!isset($payload['status'])) {
            throw new Exception('new status string not provided');
        }

        $newStatus = $payload['status'];

        $action = $this->get(TodoStatusAction::class);
        $action->setNewStatus($newStatus);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/todo/xhr/changesatatus/{itemId}", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrStatusFromDetailAction ($roomId, $itemId, Request $request, TodoService $todoService, RoomService $roomService)
    {
        $room = $roomService->getRoomItem($roomId);
        $items = [$todoService->getTodo($itemId)];
        $payload = $request->request->get('payload');
        if (!isset($payload['status'])) {
            throw new \Exception('new status string not provided');
        }
        $newStatus = $payload['status'];

        $action = $this->get(TodoStatusAction::class);
        $action->setNewStatus($newStatus);
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_todo_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        $todoService = $this->get('commsy_legacy.todo_service');

        if ($selectAll) {
            if ($request->query->has('todo_filter')) {
                $currentFilter = $request->query->get('todo_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $todoService->setFilterConditions($filterForm);
            } else {
                $todoService->hideDeactivatedEntries();
                $todoService->hideCompletedEntries();
            }

            return $todoService->getListTodos($roomItem->getItemID());
        } else {
            return $todoService->getTodosById($roomItem->getItemID(), $itemIds);
        }
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
            'hide-completed-entries' => true,
        ];

        return $this->createForm(TodoFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_todo_list', [
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

    private function getDetailInfo (
        AssessmentService $assessmentService,
        CategoryService $categoryService,
        ItemService $itemService,
        ReaderService $readerService,
        TodoService $todoService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $todo = $todoService->getTodo($itemId);

        $stepList = $todo->getStepItemList()->to_array();

        $legacyEnvironment = $environment->getEnvironment();
        $item = $todo;
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

        $itemArray = array($todo);

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
        $readerManager->getLatestReaderByUserIDArray($id_array,$todo->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($todo->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $todo->getModificationDate() ) {
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

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $todoCategories = $todo->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $todoCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $assessmentService->getRatingDetail($todo);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($todo);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($todo);
        }

        /** @var cs_todo_item[] $todos */
        $todos = $todoService->getListTodos($roomId);
        $todoList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundTodo = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($todos as $tempTodo) {
            if (!$foundTodo) {
                if ($counterBefore > 5) {
                    array_shift($todoList);
                } else {
                    $counterBefore++;
                }
                $todoList[] = $tempTodo;
                if ($tempTodo->getItemID() == $todo->getItemID()) {
                    $foundTodo = true;
                }
                if (!$foundTodo) {
                    $prevItemId = $tempTodo->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $todoList[] = $tempTodo;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempTodo->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($todos)) {
            if ($prevItemId) {
                $firstItemId = $todos[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $todos[sizeof($todos)-1]->getItemId();
            }
        }

        return [
            'roomId' => $roomId,
            'todo' => $todoService->getTodo($itemId),
            'stepList' => $stepList,
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
            'showAssociations' => $current_context->withAssociations(),
            'roomCategories' => $categories,
            'buzzExpanded' => $current_context->isBuzzwordShowExpanded(),
            'catzExpanded' => $current_context->isTagsShowExpanded(),
            'showRating' => $current_context->isAssessmentActive(),
            'ratingArray' => $current_context->isAssessmentActive() ? [
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ] : [],
            'counterPosition' => $counterPosition,
            'count' => sizeof($todos),
            'firstItemId' => $firstItemId,
            'prevItemId' => $prevItemId,
            'nextItemId' => $nextItemId,
            'lastItemId' => $lastItemId,
        ];
    }
}
