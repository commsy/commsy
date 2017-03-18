<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\GroupFilterType;
use CommsyBundle\Form\Type\GroupType;
use CommsyBundle\Form\Type\GrouproomType;
use CommsyBundle\Form\Type\AnnotationType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use CommsyBundle\Event\CommsyEditEvent;

class GroupController extends Controller
{
    // setup filter form default values
    private $defaultFilterValues = array(
        'hide-deactivated-entries' => true,
    );
    /**
     * @Route("/room/{roomId}/group")
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

       // get the group manager service
        $groupService = $this->get('commsy_legacy.group_service');
        $filterForm = $this->createForm(GroupFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_group_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in group manager
            $groupService->setFilterConditions($filterForm);
        }
        else {
            $groupService->showNoNotActivatedEntries();
        }

        // get group list from manager service 
        $itemsCountArray = $groupService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('group') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('group');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('group');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'group',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
            'usageInfo' => $usageInfo,
        );
    }

    /**
     * @Route("/room/{roomId}/group/print")
     */
    public function printlistAction($roomId, Request $request)
    {
         $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(GroupFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_group_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        // get the group manager service
        $groupService = $this->get('commsy_legacy.group_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in group manager
            $groupService->setFilterConditions($filterForm);
        }
        else {
            $groupService->showNoNotActivatedEntries();
        }

        // get group list from manager service 
        $groups = $groupService->getListGroups($roomId);
        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get group list from manager service 
        $itemsCountArray = $groupService->getCountArray($roomId);


        $html = $this->renderView('CommsyBundle:Group:listPrint.html.twig', [
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'showRating' => false,
            'module' => 'group',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $this->get('commsy.print_service')->printList($html);
    }
    
   /**
     * @Route("/room/{roomId}/group/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'title', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $groupFilter = $request->get('groupFilter');
        if (!$groupFilter) {
            $groupFilter = $request->query->get('group_filter');
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the group manager service
        $groupService = $this->get('commsy_legacy.group_service');

        if ($groupFilter) {
            $filterForm = $this->createForm(GroupFilterType::class, $this->defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_group_list', array(
                    'roomId' => $roomId,
                )),
                'hasHashtags' => false,
                'hasCategories' => false,
            ));

            // manually bind values from the request
            $filterForm->submit($groupFilter);

            $groupService->setFilterConditions($filterForm);
        }
        else {
            $groupService->showNoNotActivatedEntries();
        }

        // get group list from manager service 
        $groups = $groupService->getListGroups($roomId, $max, $start, $sort);
        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread');
            }
        }

        return array(
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
       );
    }


    /**
     * @Route("/room/{roomId}/group/feedaction")
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
        
        if ($action == 'markread') {
            $groupService = $this->get('commsy_legacy.group_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $groupService->getGroup($id);
                $versionId = $item->getVersionID();
                $noticedManager->markNoticed($id, $versionId);
                $readerManager->markRead($id, $versionId);
                $annotationList =$item->getAnnotationList();
                if ( !empty($annotationList) ){
                    $annotationItem = $annotationList->getFirst();
                    while($annotationItem){
                       $noticedManager->markNoticed($annotationItem->getItemID(),'0');
                       $annotationItem = $annotationList->getNext();
                    }
                }
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'copy') {
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'save') {
            $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
            $response->headers->set('Content-Disposition', $contentDisposition);
            
            return $response;
        } else if ($action == 'delete') {
            $groupService = $this->get('commsy_legacy.group_service');
            foreach ($selectedIds as $id) {
                $item = $groupService->getGroup($id);
                $item->delete();
            }
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
        }
        
        $response = new JsonResponse();
 /*       $response->setData(array(
            'message' => $message,
            'status' => $status
        ));
  */      
        $response->setData(array(
            'message' => $message,
            'timeout' => '5550',
            'layout'   => 'cs-notify-message'
        ));
        return $response;
    }
 

    /**
     * @Route("/room/{roomId}/group/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $memberStatus = '';

        if($infoArray['group']->isGroupRoomActivated()) {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $userService = $this->get('commsy_legacy.user_service');
            $memberStatus = $userService->getMemberStatus(
                $infoArray['group']->getGroupRoomItem(),
                $legacyEnvironment->getCurrentUser()
            );
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);
        
        return array(
            'roomId' => $roomId,
            'group' => $infoArray['group'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'groupList' => $infoArray['groupList'],
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
            'roomCategories' => $infoArray['roomCategories'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'userIsMember' => $infoArray['userIsMember'],
            'memberStatus' => $memberStatus,
            'annotationForm' => $form->createView(),
       );
    }

/**
     * @Route("/room/{roomId}/group/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('CommsyBundle:Group:detailPrint.html.twig', [
            'roomId' => $roomId,
            'group' => $infoArray['group'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'groupList' => $infoArray['groupList'],
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
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $this->get('commsy.print_service')->printDetail($html);
    }
 

    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $groupService = $this->get('commsy_legacy.group_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        $group = $groupService->getGroup($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $group;
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
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($group->getContextId());        
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
        $readerManager->getLatestReaderByUserIDArray($id_array,$group->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $group->getModificationDate() ) {
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
        $reader = $readerService->getLatestReader($group->getItemId());
        if ( empty($reader) ) {
           $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $group->getModificationDate() ) {
           $readerList[$group->getItemId()] = 'changed';
        }
        
        $modifierList[$group->getItemId()] = $itemService->getAdditionalEditorsForItem($group);
        
        $groups = $groupService->getListGroups($roomId);
        $groupList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundGroup = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($groups as $tempGroup) {
            if (!$foundGroup) {
                if ($counterBefore > 5) {
                    array_shift($groupList);
                } else {
                    $counterBefore++;
                }
                $groupList[] = $tempGroup;
                if ($tempGroup->getItemID() == $group->getItemID()) {
                    $foundGroup = true;
                }
                if (!$foundGroup) {
                    $prevItemId = $tempGroup->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $groupList[] = $tempGroup;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempGroup->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($groups)) {
            if ($prevItemId) {
                $firstItemId = $groups[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $groups[sizeof($groups)-1]->getItemId();
            }
        }
        // mark annotations as readed
        $annotationList = $group->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);


        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();
        
        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $groupCategories = $group->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $groupCategories);
        }

        $infoArray['group'] = $group;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['groupList'] = $groupList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($groups);
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
        $infoArray['roomCategories'] = $categories;
        $infoArray['members'] = $members;
        $infoArray['userIsMember'] = $membersList->inList($infoArray['user']);

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
     * @Route("/room/{roomId}/group/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $groupService = $this->get('commsy_legacy.group_service');
        
        // create new group item
        $groupItem = $groupService->getNewGroup();
        $groupItem->setDraftStatus(1);
        $groupItem->setPrivateEditing(1);
        $groupItem->save();

        return $this->redirectToRoute('commsy_group_detail', array('roomId' => $roomId, 'itemId' => $groupItem->getItemId()));
    }


    /**
     * @Route("/room/{roomId}/group/new")
     * @Template()
     */
    public function newAction($roomId, Request $request)
    {

    }


    /**
     * @Route("/room/{roomId}/group/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $groupService = $this->get('commsy_legacy.group_service');
        $transformer = $this->get('commsy_legacy.transformer.group');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $groupItem = NULL;
        
        // get date from DateService
        $groupItem = $groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id ' . $itemId);
        }
        $formData = $transformer->transform($groupItem);
        $translator = $this->get('translator');
        $form = $this->createForm(GroupType::class, $formData, array(
            'action' => $this->generateUrl('commsy_group_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '['.$translator->trans('insert title').']',
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $groupItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_group_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($groupItem));

        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $groupService = $this->get('commsy_legacy.group_service');
        $transformer = $this->get('commsy_legacy.transformer.group');
        
        $group = $groupService->getGroup($itemId);
        
        $itemArray = array($group);
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
		$readerManager->getLatestReaderByUserIDArray($id_array,$group->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $group->getModificationDate() ) {
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

        $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($group));

        return array(
            'roomId' => $roomId,
            'item' => $group,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }


    /**
     * @Route("/room/{roomId}/group/{itemId}/editgrouproom")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editgrouproomAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $groupService = $this->get('commsy_legacy.group_service');
        $transformer = $this->get('commsy_legacy.transformer.group');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $groupItem = NULL;
        
        // get group from GroupService
        $groupItem = $groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id ' . $itemId);
        }
        $formData = $transformer->transform($groupItem);
        $form = $this->createForm(GrouproomType::class, $formData, array(
            'action' => $this->generateUrl('commsy_group_editgrouproom', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            ))
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {
                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $groupItem->save(true);
            } else {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_group_savegrouproom', array('roomId' => $roomId, 'itemId' => $itemId));
        }
        
        return array(
            'form' => $form->createView(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}/savegrouproom")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function savegrouproomAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $groupService = $this->get('commsy_legacy.group_service');
        $transformer = $this->get('commsy_legacy.transformer.date');
        
        $group = $groupService->getGroup($itemId);
        
        /* $itemArray = array($grouproom);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        //$roomItem = $roomManager->getItem($material->getContextId());        
        //$numTotalMember = $roomItem->getAllUsers();
        
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
		$readerManager->getLatestReaderByUserIDArray($id_array,$date->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($date->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $date->getModificationDate() ) {
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
        } */
        
        return array(
            'roomId' => $roomId,
            'item' => $group,
            //'modifierList' => $modifierList,
            //'userCount' => $all_user_count,
            //'readCount' => $read_count,
            //'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/join")
     * @Template()
     */
    public function joinAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $groupService = $this->get('commsy_legacy.group_service');
        $groupItem = $groupService->getGroup($itemId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        } elseif (!$groupItem) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        $current_user = $legacyEnvironment->getCurrentUser();

        $roomGroupitem = $groupItem->getGroupRoomItem();
        if ( isset($roomGroupItem) and !empty($roomGroupItem) ) {
            // TODO: join group room
            // joinGroupRoom();
            throw new Exception("Joining a group room is not yet implemented!");
        } else {
            $groupItem->addMember($current_user);
            /*
            if($legacyEnvironment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() === '1') {
                $wiki_manager = $this->_environment->getWikiManager();
                $wiki_manager->updateNotification();
            }
            */
       }

        return new JsonResponse(array(
           'title' => $groupItem->getTitle(),
           'groupId' => $itemId,
           'memberId' => $current_user->getItemId(),
        ));
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/leave")
     * @Template()
     */
    public function leaveAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $groupService = $this->get('commsy_legacy.group_service');
        $groupItem = $groupService->getGroup($itemId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        } elseif (!$groupItem) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        $current_user = $legacyEnvironment->getCurrentUser();

        $groupItem->removeMember($current_user);
/*
        if($this->_environment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() === '1') {
            $wiki_manager = $this->_environment->getWikiManager();
            $wiki_manager->updateNotification();
        }

        if($groupItem->isGroupRoomActivated()) {
            $grouproom_item = $this->_item->getGroupRoomItem();
            if(isset($grouproom_item) && !empty($grouproom_item)) {
                $group_room_user_item = $grouproom_item->getUserByUserID($current_user->getUserID(), $current_user->getAuthSource());
                $group_room_user_item->reject();
                $group_room_user_item->save();
            }
        }
*/
        return new JsonResponse(array(
           'title' => $groupItem->getTitle(),
           'groupId' => $itemId,
           'memberId' => $current_user->getItemId(),
        ));
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/members", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function membersAction($roomId, $itemId, Request $request)
    {
        $groupService = $this->get('commsy_legacy.group_service');
        $group = $groupService->getGroup($itemId);

        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        return [
            'group' => $group,
            'members' => $members,
        ];
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/grouproom", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function groupRoomAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $groupService = $this->get('commsy_legacy.group_service');
        $group = $groupService->getGroup($itemId);

        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        $userService = $this->get('commsy_legacy.user_service');
        $memberStatus = '';
        $memberStatus = $userService->getMemberStatus(
                $group->getGroupRoomItem(),
                $legacyEnvironment->getCurrentUser()
        );

        return [
            'group' => $group,
            'roomId' => $roomId,
            'userIsMember' => $membersList->inList($legacyEnvironment->getCurrentUserItem()),
            'memberStatus' => $memberStatus,
        ];
    }

}
