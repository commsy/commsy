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

class TodoController extends Controller
{
    /**
     * @Route("/room/{roomId}/todo")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        
        // get the todo manager service
        $todoService = $this->get('commsy_legacy.todo_service');
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(TodoFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_todo_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in todo manager
            $todoService->setFilterConditions($filterForm);
        }

        // get todo list from manager service 
        $itemsCountArray = $todoService->getCountArray($roomId);
        
        
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(TodoFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_todo_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();


        // get the todo manager service
        $todoService = $this->get('commsy_legacy.todo_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in todo manager
            $todoService->setFilterConditions($filterForm);
        }
 
        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'todo',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'statusList' => $roomItem->getExtraToDoStatusArray(),
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
        $todoItem->setTitle('['.$translator->trans('insert title').']');
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
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $todoFilter = $request->get('todoFilter');
        if (!$todoFilter) {
            $todoFilter = $request->query->get('todo_filter');
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the todo manager service
        $todoService = $this->get('commsy_legacy.todo_service');

        if ($todoFilter) {
            // setup filter form
            $defaultFilterValues = array(
                'activated' => true,
            );
            $filterForm = $this->createForm(TodoFilterType::class, $defaultFilterValues, array(
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
        }

        // get todo list from manager service 
        $todos = $todoService->getListTodos($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($todos as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        return array(
            'roomId' => $roomId,
            'todos' => $todos,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
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
            $entries = $this->feedAction($roomId, $max = 1000, $start = $selectAllStart, $request);
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
    	        
    	        $sectionList =$item->getSectionList();
    	        if ( !empty($sectionList) ){
    	            $sectionItem = $sectionList->getFirst();
    	            while($sectionItem){
    	               $noticedManager->markNoticed($sectionItem->getItemID(),$versionId);
    	               $readerManager->markRead($sectionItem->getItemID(),$versionId);
    	               $sectionItem = $sectionList->getNext();
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
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
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
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($todo->getContextId());        
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
     * @Route("/room/{roomId}/todo/{itemId}/editsteps")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editStepsAction($roomId, $itemId, Request $request)
    {
        $todoService = $this->get('commsy_legacy.todo_service');

        $todo = $todoService->getTodo($itemId);

        $stepList = $todo->getStepItemList()->to_array();

        return array(
            'stepList' => $stepList,
            'todo' => $todo
        );
    }
    
    /**
     * @Route("/room/{roomId}/todo/{itemId}/createstep")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function createStepAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');

        $todo = $todoService->getTodo($itemId);

        $stepList = $material->getStepItemList();
        $steps = $stepList->to_array();
        $countSteps = $stepList->getCount();

        $step = $todoService->getNewStep();
        $step->setTitle('['.$translator->trans('insert title').']');
        $step->setLinkedItemId($itemId);
        $step->setNumber($countSteps+1);
        $step->save();

        $formData = $transformer->transform($step);
        $form = $this->createForm(SectionType::class, $formData, array(
            'action' => $this->generateUrl('commsy_material_savesection', array('roomId' => $roomId, 'itemId' => $step->getItemID()))
        ));

        return array(
            'form' => $form->createView(),
            'stepList' => $stepList,
            'todo' => $todo,
            'step' => $step,
            'modifierList' => array(),
            'userCount' => 0,
            'readCount' => 0,
            'readSinceModificationCount' => 0
        );
    }
    
    /**
     * @Route("/room/{roomId}/todo/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.date');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $todoItem = NULL;
        
        if ($item->getItemType() == 'todo') {
            $todoItem = $todoService->getTodo($itemId);
            if (!$todoItem) {
                throw $this->createNotFoundException('No todo found for id ' . $itemId);
            }
            $formData = $transformer->transform($todoItem);
            $form = $this->createForm(TodoType::class, $formData, array(
                'action' => $this->generateUrl('commsy_todo_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ))
            ));
        } else if ($item->getItemType() == 'step') {
            $todoItem = $todoService->getStep($itemId);
            if (!$todoItem) {
                throw $this->createNotFoundException('No step found for id ' . $itemId);
            }
            $formData = $transformer->transform($todoItem);
            $form = $this->createForm(StepType::class, $formData, array(
                'action' => $this->generateUrl('commsy_todo_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ))
            ));
        }
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $todoItem = $transformer->applyTransformation($todoItem, $form->getData());

                // update modifier
                $todoItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $todoItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_todo_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }
        
        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/todo/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $todoService = $this->get('commsy_legacy.todo_service');
        $transformer = $this->get('commsy_legacy.transformer.todo');
        
        if ($item->getItemType() == 'todo') {
            $typedItem = $todoService->getTodo($itemId);
        } else if ($item->getItemType() == 'step') {
            $typedItem = $todoService->getStep($itemId);
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
        
        return array(
            'roomId' => $roomId,
            'item' => $typedItem,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }
}