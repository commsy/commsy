<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Filter\TodoFilterType;
use CommsyBundle\Form\Type\TodoType;
use CommsyBundle\Form\Type\StepType;
use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\TodoStepType;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Event\CommsyEditEvent;

/**
 * Class TodoController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'todo')")
 */
class TodoController extends Controller
{
    // setup filter form default values
    private $defaultFilterValues = [
        'hide-deactivated-entries' => true,
        'hide-completed-entries' => true,
    ];

    /**
     * @Route("/room/{roomId}/todo")
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
        
        // get the todo manager service
        $todoService = $this->get('commsy_legacy.todo_service');
        $filterForm = $this->createForm(TodoFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_todo_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in todo manager
            $todoService->setFilterConditions($filterForm);
        } else {
            $todoService->showNoNotActivatedEntries();
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
            'showCategories' => $roomItem->withTags(),
            'statusList' => $roomItem->getExtraToDoStatusArray(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/todo/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $todoData = array();
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');
        
        // create new todo item
        $todoItem = $todoService->getNewTodo();
        // $todoItem->setTitle('['.$translator->trans('insert title').']');
        $todoItem->setDraftStatus(1);
        $todoItem->setPrivateEditing('1');
        $todoItem->save();

        /* $form = $this->createForm('todo', $todoData, array());
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $todoItem = $transformer->applyTransformation($todoItem, $form->getData());
            $todoItem->save();
            return $this->redirectToRoute('commsy_todo_detail', array('roomId' => $roomId, 'itemId' => $todoItem->getItemId()));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        } */

        return $this->redirectToRoute('commsy_todo_detail', array('roomId' => $roomId, 'itemId' => $todoItem->getItemId()));

        /* return array(
            'todo' => $todoItem,
            'form' => $form->createView()
        ); */
    }
    
    /**
     * @Route("/room/{roomId}/todo/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'duedate_rev', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $todoFilter = $request->get('todoFilter');
        if (!$todoFilter) {
            $todoFilter = $request->query->get('todo_filter');
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the todo manager service
        $todoService = $this->get('commsy_legacy.todo_service');

        if ($todoFilter) {
            $filterForm = $this->createForm(TodoFilterType::class, $this->defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_todo_list', array(
                    'roomId' => $roomId,
                )),
                'hasHashtags' => $roomItem->withBuzzwords(),
                'hasCategories' => $roomItem->withTags(),
            ));
    
            // manually bind values from the request
            $filterForm->submit($todoFilter);
    
            // apply filter
            $todoService->setFilterConditions($filterForm);
        } else {
            $todoService->showNoNotActivatedEntries();
            $todoService->hideCompletedEntries();
        }

        // get todo list from manager service 
        $todos = $todoService->getListTodos($roomId, $max, $start, $sort);

        $this->get('session')->set('sortTodos', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());

            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
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
            $assessmentService = $this->get('commsy_legacy.assessment_service');
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
     * @Route("/room/{roomId}/todo/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $selectAll = $request->request->get('selectAll');
        $selectAllStart = $request->request->get('selectAllStart');
        
        if ($selectAll == 'true') {
            $entries = $this->feedAction($roomId, $max = 1000, $start = $selectAllStart, 'duedate_rev', $request);
            foreach ($entries['todos'] as $key => $value) {
                $selectedIds[] = $value->getItemId();
            }
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');

        $result = [];
        
        if ($action == 'markread') {
	        $todoService = $this->get('commsy_legacy.todo_service');
	        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
    	        $item = $todoService->getTodo($id);
    	        $versionId = $item->getVersionID();
    	        $noticedManager->markNoticed($id, $versionId);
    	        $readerManager->markRead($id, $versionId);
    	        
                $stepList =$item->getStepItemList();
                if ( !empty($stepList) ){
                    $stepItem = $stepList->getFirst();
                    while($stepItem){
                       $noticedManager->markNoticed($stepItem->getItemID(),$versionId);
                       $readerManager->markRead($stepItem->getItemID(),$versionId);
                       $stepItem = $stepList->getNext();
                    }
                }

    	        $annotationList =$item->getAnnotationList();
    	        if ( !empty($annotationList) ){
    	            $annotationItem = $annotationList->getFirst();
    	            while($annotationItem){
    	               $noticedManager->markNoticed($annotationItem->getItemID(),$versionId);
    	               $readerManager->markRead($annotationItem->getItemID(),$versionId);
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
            /* $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
            $response->headers->set('Content-Disposition', $contentDisposition);
            
            return $response; */
            
            $downloadService = $this->get('commsy_legacy.download_service');
        
            $zipFile = $downloadService->zipFile($roomId, $selectedIds);
    
            $response = new BinaryFileResponse($zipFile);
            $response->deleteFileAfterSend(true);
    
            $filename = 'CommSy_Todo.zip';
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
            $response->headers->set('Content-Disposition', $contentDisposition);
    
            return $response;
        } else if ($action == 'delete') {
            $todoService = $this->get('commsy_legacy.todo_service');
  		    foreach ($selectedIds as $id) {
  		        $item = $todoService->getTodo($id);
  		        $item->delete();
  		    }
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'markpending') {
            $todoService = $this->get('commsy_legacy.todo_service');
            foreach ($selectedIds as $id) {
                $item = $todoService->getTodo($id);
                $item->setStatus(1);
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('Set status of %count% entries to pending', count($selectedIds), array('%count%' => count($selectedIds)), "messages");
        } else if ($action == 'markinprogress') {
            $todoService = $this->get('commsy_legacy.todo_service');
            foreach ($selectedIds as $id) {
                $item = $todoService->getTodo($id);
                $item->setStatus(2);
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('Set status of %count% entries to in progress', count($selectedIds), array('%count%' => count($selectedIds)), "messages");
        } else if ($action == 'markdone') {
            $todoService = $this->get('commsy_legacy.todo_service');
            foreach ($selectedIds as $id) {
                $item = $todoService->getTodo($id);
                $item->setStatus(3);
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('Set status of %count% entries to done', count($selectedIds), array('%count%' => count($selectedIds)), "messages");
        } else {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $roomService = $this->get('commsy_legacy.room_service');
            $roomItem = $roomService->getRoomItem($roomId);
            $statusArray = $roomItem->getExtraToDoStatusArray();
            
            $tempAction = str_ireplace('mark', '', $action);
            if (in_array($tempAction, $statusArray)) {
                $todoService = $this->get('commsy_legacy.todo_service');
                foreach ($selectedIds as $id) {
                    $item = $todoService->getTodo($id);
                    $item->setStatus(array_search ($tempAction, $statusArray));
                    $item->save();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('Set status of %count% entries to %status%',count($selectedIds), array('%count%' => count($selectedIds), '%status%' => substr($action, 4)));
            }
        }

        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/todo/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $todoService = $this->get('commsy_legacy.todo_service');
        $itemService = $this->get('commsy_legacy.item_service');
        
        $todo = $todoService->getTodo($itemId);

        $steps = $todo->getStepItemList()->to_array();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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
        $annotationService = $this->get('commsy_legacy.annotation_service');
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
 
        $roomService = $this->get('commsy_legacy.room_service');
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomService->getRoomItem($todo->getContextId());
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
        $read_percentage = round(($read_count/$all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count/$all_user_count) * 100);
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

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $todoCategories = $todo->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $todoCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
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
            'todo' => $todoService->getTodo($itemId),
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
     * @Route("/room/{roomId}/todo/{itemId}/createstep")
     * @Template("@Commsy/Todo/editStep.html.twig")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'todo')")
     */
    public function createStepAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');

        $step = $todoService->getNewStep();
        $step->setDraftStatus(1);
        $step->setTodoID($itemId);
        $step->save();

        $formData = $transformer->transform($step);
        $form = $this->createForm(StepType::class, $formData, array(
            'action' => $this->generateUrl('commsy_todo_editstep', [
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
     */
    public function editStepAction($roomId, $itemId, Request $request)
    {
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $translator = $this->get('translator');

        // get step
        $step = $todoService->getStep($itemId);

        $formData = $transformer->transform($step);

        $form = $this->createForm(StepType::class, $formData, [
            'action' => $this->generateUrl('commsy_todo_editstep', [
                'roomId' => $roomId,
                'itemId' => $step->getItemID()
            ]),
            'placeholderText' => '['.$translator->trans('insert title').']',
        ]);

        $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($step->getLinkedItem()));

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

                    $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($step->getLinkedItem()));

                    return $this->redirectToRoute('commsy_todo_detail', [
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

                    return $this->redirectToRoute('commsy_todo_detail', [
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
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');

        $translator = $this->get('translator');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
        
        $formData = array();
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

        $itemController = $this->get('commsy.item_controller');

        $formOptions = array(
            'action' => $this->generateUrl('commsy_todo_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'statusChoices' => $statusChoices,
            'placeholderText' => '['.$translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service'))
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('commsy_hashtag_add', ['roomId' => $roomId])
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
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_todo_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($todoItem));

        return array(
            'form' => $form->createView(),
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
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');
        
        if ($item->getItemType() == 'todo') {
            $typedItem = $todoService->getTodo($itemId);

            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($typedItem));
        } else if ($item->getItemType() == 'step') {
            $typedItem = $todoService->getStep($itemId);

            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($typedItem->getLinkedItem()));
        }
        
        $itemArray = array($typedItem);
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
     **/
    public function ratingAction($roomId, $itemId, $vote, Request $request)
    {
        $todoService = $this->get('commsy_legacy.todo_service');
        $todo = $todoService->getTodo($itemId);
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
        if ($vote != 'remove') {
            $assessmentService->rateItem($todo, $vote);
        } else {
            $assessmentService->removeRating($todo);
        }
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
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
     */
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('CommsyBundle:Todo:detailPrint.html.twig', [
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
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => 'roomCategories',
        ]);

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }
    
    /**
     * @Route("/room/{roomId}/todo/print/{sort}", defaults={"sort" = "none"})
     */
    public function printlistAction($roomId, Request $request, $sort)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(TodoFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_todo_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // get the announcement manager service
        $todoService = $this->get('commsy_legacy.todo_service');
        $numAllTodos = $todoService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $todoService->setFilterConditions($filterForm);
        }

        // get todo list from manager service
        if ($sort != "none") {
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, $sort);
        }
        elseif ($this->get('session')->get('sortTodos')) {
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, $this->get('session')->get('sortTodos'));
        }
        else {
            $todos = $todoService->getListTodos($roomId, $numAllTodos, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($todos as $todo) {
                $itemIds[] = $todo->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service 
        $itemsCountArray = $todoService->getCountArray($roomId);

        $html = $this->renderView('CommsyBundle:Todo:listPrint.html.twig', [
            'roomId' => $roomId,
            'module' => 'todo',
            'announcements' => $todos,
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
    
    private function getDetailInfo ($roomId, $itemId) {
        $todoService = $this->get('commsy_legacy.todo_service');
        $itemService = $this->get('commsy_legacy.item_service');
        
        $todo = $todoService->getTodo($itemId);

        $stepList = $todo->getStepItemList()->to_array();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomService->getRoomItem($todo->getContextId());
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
        $read_percentage = round(($read_count/$all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count/$all_user_count) * 100);
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

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $todoCategories = $todo->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $todoCategories);
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $ratingDetail = $assessmentService->getRatingDetail($todo);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($todo);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($todo);
        }

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

        return array(
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
            'roomCategories' => $categories,
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
        );
    }
    
    /**
     * @Route("/room/{roomId}/todo/{itemId}/participate")
     */
    public function participateAction($roomId, $itemId, Request $request)
    {
        $todoService = $this->get('commsy_legacy.todo_service');
        $todo = $todoService->getTodo($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        
        if (!$todo->isProcessor($legacyEnvironment->getCurrentUserItem())) {
            $todo->addProcessor($currentUser);
        } else {
            $todo->removeProcessor($currentUser);
        }

        return $this->redirectToRoute('commsy_todo_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }
}
