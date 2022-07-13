<?php

namespace App\Controller;

use App\Action\Mark\HashtagAction;
use App\Action\Mark\MarkAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\MarkRead\MarkReadTodo;
use App\Action\TodoStatus\TodoStatusAction;
use App\Event\CommsyEditEvent;
use App\Filter\TodoFilterType;
use App\Form\DataTransformer\TodoTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\StepType;
use App\Form\Type\TodoType;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\LabelService;
use App\Utils\TodoService;
use App\Utils\TopicService;
use cs_item;
use cs_room_item;
use cs_step_item;
use cs_todo_item;
use cs_user_item;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TodoController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'todo')")
 */
class TodoController extends BaseController
{

    /**
     * @var TodoService
     */
    private TodoService $todoService;
    private SessionInterface $session;

    /**
     * @required
     * @param TodoService $todoService
     */
    public function setTodoService(TodoService $todoService): void
    {
        $this->todoService = $todoService;
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
     * @Route("/room/{roomId}/todo")
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
            // set filter conditions in todo manager
            $this->todoService->setFilterConditions($filterForm);
        } else {
            $this->todoService->hideDeactivatedEntries();
            $this->todoService->hideCompletedEntries();
        }

        // get todo list from manager service 
        $itemsCountArray = $this->todoService->getCountArray($roomId);

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
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/create")
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'todo')")
     */
    public function createAction(
        int $roomId
    ) {
        // create new todo item
        $todoItem = $this->todoService->getNewTodo();
        $todoItem->setDraftStatus(1);
        $todoItem->setPrivateEditing('1');
        $todoItem->save();

        return $this->redirectToRoute('app_todo_detail',
            array('roomId' => $roomId, 'itemId' => $todoItem->getItemId()));
    }

    /**
     * @Route("/room/{roomId}/todo/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        AssessmentService $assessmentService,
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

        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($todoFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($todoFilter);

            // apply filter
            $this->todoService->setFilterConditions($filterForm);
        } else {
            $this->todoService->hideDeactivatedEntries();
            $this->todoService->hideCompletedEntries();
        }

        // get todo list from manager service
        /** @var cs_todo_item[] $todos */
        $todos = $this->todoService->getListTodos($roomId, $max, $start, $sort);

        $this->session->set('sortTodos', $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());

            if ($this->isGranted('ITEM_EDIT', $item->getItemID()) or
                ($this->isGranted('ITEM_ENTER', $roomId)) and $roomItem->getType() == 'userroom'
                or ($roomItem->getType() == 'project' and $this->isGranted('ITEM_PARTICIPATE', $roomId))) {
                $allowedActions[$item->getItemID()] = array(
                    'markread',
                    'mark',
                    'categorize',
                    'hashtag',
                    'save',
                    'delete',
                    'markpending',
                    'markinprogress',
                    'markdone'
                );

                $statusArray = $roomItem->getExtraToDoStatusArray();
                foreach ($statusArray as $tempStatus) {
                    $allowedActions[$item->getItemID()][] = 'mark' . $tempStatus;
                }
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'mark', 'save');
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
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        AnnotationService $annotationService,
        AssessmentService $assessmentService,
        CategoryService $categoryService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $todo = $this->todoService->getTodo($itemId);
        /** @var cs_step_item[] $steps */
        $steps = $todo->getStepItemList()->to_array();

        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($todo->getItemID());
        if (empty($reader) || $reader['read_date'] < $todo->getModificationDate()) {
            $reader_manager->markRead($todo->getItemID(), $todo->getVersionID());
        }

        $noticed_manager = $this->legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($todo->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $todo->getModificationDate()) {
            $noticed_manager->markNoticed($todo->getItemID(), $todo->getVersionID());
        }

        // mark annotations as read
        $annotationList = $todo->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $stepList = $todo->getStepItemList();

        $stepItem = $stepList->getFirst();
        while ($stepItem) {
            $reader = $reader_manager->getLatestReader($stepItem->getItemID());
            if (empty($reader) || $reader['read_date'] < $stepItem->getModificationDate()) {
                $reader_manager->markRead($stepItem->getItemID(), 0);
            }

            $noticed = $noticed_manager->getLatestNoticed($stepItem->getItemID());
            if (empty($noticed) || $noticed['read_date'] < $stepItem->getModificationDate()) {
                $noticed_manager->markNoticed($stepItem->getItemID(), 0);
            }

            $stepItem = $stepList->getNext();
        }

        $itemArray = array($todo);

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
        $readerManager->getLatestReaderByUserIDArray($id_array, $todo->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($todo->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $todo->getModificationDate()) {
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
        if ($this->todoService->getTodo($itemId)->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));
        $amountAnnotations = $annotationService->getListAnnotations($roomId,
            $this->todoService->getTodo($itemId)->getItemId(), null, null);

        return array(
            'roomId' => $roomId,
            'todo' => $this->todoService->getTodo($itemId),
            'amountAnnotations' => sizeof($amountAnnotations),
            'stepList' => $steps,
            'timeSpendSum' => $timeSpendSum,
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
            'isParticipating' => $todo->isProcessor($this->legacyEnvironment->getCurrentUserItem()),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isArchived' => $current_context->isArchived(),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/createstep")
     * @Template("todo/edit_step.html.twig")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo') or is_granted('ITEM_USERROOM', itemId) or is_granted('ITEM_PARTICIPATE', itemId)")
     * @param TodoTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function createStepAction(
        TodoTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        $step = $this->todoService->getNewStep();
        $step->setDraftStatus(1);
        $step->setTodoID($itemId);
        $step->save();

        $formData = $transformer->transform($step);
        $form = $this->createForm(StepType::class, $formData, array(
            'action' => $this->generateUrl('app_todo_editstep', [
                'roomId' => $roomId,
                'itemId' => $step->getItemID()
            ]),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
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
     * @param TodoTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editStepAction(
        Request $request,
        TodoTransformer $transformer,
        int $roomId,
        int $itemId
    ) {

        $item = $this->itemService->getItem($itemId);

        // get step
        $step = $this->todoService->getStep($itemId);

        $formData = $transformer->transform($step);

        $form = $this->createForm(StepType::class, $formData, [
            'action' => $this->generateUrl('app_todo_editstep', [
                'roomId' => $roomId,
                'itemId' => $step->getItemID()
            ]),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
        ]);

        $this->eventDispatcher->dispatch(new CommsyEditEvent($step->getLinkedItem()), CommsyEditEvent::EDIT);

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
                    $step->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                    $step->save();

                    $step->getLinkedItem()->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                    // this will also update the todo item's modification date to indicate that it has changes
                    $step->getLinkedItem()->save();

                    $this->eventDispatcher->dispatch(new CommsyEditEvent($step->getLinkedItem()),
                        CommsyEditEvent::SAVE);

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
     * @param TodoTransformer $transformer
     * @param ItemController $itemController
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        TodoTransformer $transformer,
        ItemController $itemController,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_item $item */
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();
        $roomItem = $this->roomService->getRoomItem($roomId);

        $todoItem = null;

        $isDraft = $item->isDraft();

        $statusChoices = array(
            $this->translator->trans('pending', [], 'todo') => '1',
            $this->translator->trans('in progress', [], 'todo') => '2',
            $this->translator->trans('done', [], 'todo') => '3',
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
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
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

        $todoItem = $this->todoService->getTodo($itemId);
        if (!$todoItem) {
            throw $this->createNotFoundException('No todo found for id ' . $itemId);
        }


        $formData = $transformer->transform($todoItem);
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId,
            $this->legacyEnvironment);
        $formData['draft'] = $isDraft;

        $form = $this->createForm(TodoType::class, $formData, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $todoItem = $transformer->applyTransformation($todoItem, $form->getData());

                // update modifier
                $todoItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

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
                        $todoItem->setTagListByID($categoryIds);
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
                        $todoItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $todoItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            }

            return $this->redirectToRoute('app_todo_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($todoItem), CommsyEditEvent::EDIT);

        return array(
            'form' => $form->createView(),
            'todo' => $todoItem,
            'isDraft' => $isDraft,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);
        if ($item->getItemType() == 'todo') {
            $typedItem = $this->todoService->getTodo($itemId);
            $this->eventDispatcher->dispatch(new CommsyEditEvent($typedItem), CommsyEditEvent::SAVE);
        } else {
            if ($item->getItemType() == 'step') {
                $typedItem = $this->todoService->getStep($itemId);
                $this->eventDispatcher->dispatch(new CommsyEditEvent($typedItem->getLinkedItem()),
                    CommsyEditEvent::SAVE);
            }
        }

        $itemArray = array($typedItem);
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

        /** @var cs_user_item $current_user */
        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }

        $readerManager->getLatestReaderByUserIDArray($id_array, $typedItem->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($typedItem->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $typedItem->getModificationDate()) {
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
     * @param int $roomId
     * @param int $itemId
     * @param $vote
     * @return array
     */
    public function ratingAction(
        AssessmentService $assessmentService,
        int $roomId,
        int $itemId,
        $vote
    ) {
        $todo = $this->todoService->getTodo($itemId);
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
            'ratingArray' => array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    /**
     * @Route("/room/{roomId}/todo/{itemId}/print")
     * @param AssessmentService $assessmentService
     * @param CategoryService $categoryService
     * @param PrintService $printService
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        AssessmentService $assessmentService,
        CategoryService $categoryService,
        PrintService $printService,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($assessmentService, $categoryService, $roomId, $itemId);
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
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        int $roomId,
        string $sort
    ) {
        $roomItem = $this->roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        $numAllTodos = $this->todoService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $this->todoService->setFilterConditions($filterForm);
        }

        // get todo list from manager service
        if ($sort != "none") {
            /** @var cs_todo_item[] $todos */
            $todos = $this->todoService->getListTodos($roomId, $numAllTodos, 0, $sort);
        } elseif ($this->session->get('sortTodos')) {
            /** @var cs_todo_item[] $todos */
            $todos = $this->todoService->getListTodos($roomId, $numAllTodos, 0,
                $this->session->get('sortTodos'));
        } else {
            /** @var cs_todo_item[] $todos */
            $todos = $this->todoService->getListTodos($roomId, $numAllTodos, 0, 'date');
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
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
        $itemsCountArray = $this->todoService->getCountArray($roomId);

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
     * @param int $roomId
     * @param int $itemId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo') or is_granted('ITEM_PARTICIPATE', itemId)")
     */
    public function participateAction(
        int $roomId,
        int $itemId
    ) {
        $todo = $this->todoService->getTodo($itemId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        if (!$todo->isProcessor($this->legacyEnvironment->getCurrentUserItem())) {
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
     * @Route("/room/{roomId}/todo/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        MarkReadTodo $markReadTodo,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        $markReadAction->setMarkReadStrategy($markReadTodo);
        return $markReadAction->execute($room, $items);

    }

    /**
     * @Route("/room/{roomId}/todo/xhr/mark", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrMarkAction(
        Request $request,
        MarkAction $action,
        $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/todo/xhr/hashtag", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param HashtagAction $action
     * @param ItemController $itemController
     * @param int $roomId
     * @return mixed
     * @throws Exception
     */
    public function xhrHashtagAction(
        Request $request,
        HashtagAction $action,
        ItemController $itemController,
        int $roomId
    ) {
        return parent::handleHashtagActionOptions($request, $action, $itemController, $roomId);
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
        DeleteAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

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
        TodoStatusAction $action,
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

        $action->setNewStatus($newStatus);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/todo/xhr/changesatatus/{itemId}", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrStatusFromDetailAction($roomId, $itemId, Request $request, TodoStatusAction $action)
    {
        $room = $this->roomService->getRoomItem($roomId);
        $items = [$this->todoService->getTodo($itemId)];
        $payload = $request->request->get('payload');
        if (!isset($payload['status'])) {
            throw new \Exception('new status string not provided');
        }
        $newStatus = $payload['status'];

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

        if ($selectAll) {
            if ($request->query->has('todo_filter')) {
                $currentFilter = $request->query->get('todo_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->todoService->setFilterConditions($filterForm);
            } else {
                $this->todoService->hideDeactivatedEntries();
                $this->todoService->hideCompletedEntries();
            }

            return $this->todoService->getListTodos($roomItem->getItemID());
        } else {
            return $this->todoService->getTodosById($roomItem->getItemID(), $itemIds);
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

    private function getDetailInfo(
        AssessmentService $assessmentService,
        CategoryService $categoryService,
        int $roomId,
        int $itemId
    ) {
        $todo = $this->todoService->getTodo($itemId);

        $stepList = $todo->getStepItemList()->to_array();

        $item = $todo;
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

        $itemArray = array($todo);

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
        $readerManager->getLatestReaderByUserIDArray($id_array, $todo->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($todo->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $todo->getModificationDate()) {
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
        $todos = $this->todoService->getListTodos($roomId);
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
                $lastItemId = $todos[sizeof($todos) - 1]->getItemId();
            }
        }

        return [
            'roomId' => $roomId,
            'todo' => $this->todoService->getTodo($itemId),
            'stepList' => $stepList,
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
