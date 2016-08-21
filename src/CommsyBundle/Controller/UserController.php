<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\UserFilterType;

use CommsyBundle\Form\Type\UserType;

use \ZipArchive;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use CommsyBundle\Services\AvatarService;

class UserController extends Controller
{
    /**
     * @Route("/room/{roomId}/user/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $userFilter = $request->get('userFilter');
        if (!$userFilter) {
            $userFilter = $request->query->get('user_filter');
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the user manager service
        $userService = $this->get('commsy_legacy.user_service');

        if ($userFilter) {
            // setup filter form
            $defaultFilterValues = [
                'activated' => true,
                'user_status' => 2,
            ];
            
            $filterForm = $this->createForm(UserFilterType::class, $defaultFilterValues, [
                'action' => $this->generateUrl('commsy_user_list', [
                    'roomId' => $roomId,
                ]),
                'hasHashtags' => false,
                'hasCategories' => false,
                'isModerator' => $currentUser->isModerator(),
            ]);

            // manually bind values from the request
            $filterForm->submit($userFilter);

            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        } else {
            $userService->showNoNotActivatedEntries();
            $userService->showUserStatus(2);
        }

        // get user list from manager service 
        $users = $userService->getListUsers($roomId, $max, $start, $currentUser->isModerator());
        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = [];
        $allowedActions = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($currentUser->isModerator()) {
                $allowedActions[$item->getItemID()] = ['markread', 'copy', 'save', 'user-delete', 'user-block', 'user-confirm', 'user-status-reading-user', 'user-status-user', 'user-status-moderator', 'user-contact', 'user-contact-remove'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread'];
            }
        }

        return [
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
        ];
    }
    
    /**
     * @Route("/room/{roomId}/user")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $defaultFilterValues = [
            'activated' => true,
            'user_status' => 2,
        ];
        $filterForm = $this->createForm(UserFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('commsy_user_list', [
                'roomId' => $roomId,
            ]),
            'hasHashtags' => false,
            'hasCategories' => false,
            'isModerator' => $currentUser->isModerator(),
        ]);

        // get the user manager service
        $userService = $this->get('commsy_legacy.user_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        } else {
            $userService->showNoNotActivatedEntries();
            $userService->showUserStatus(2);
        }

        // get filtered and total number of results
        $itemsCountArray = $userService->getCountArray($roomId, $currentUser->isModerator());

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('user') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('user');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('user');
        }

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
            'usageInfo' => $usageInfo,
        ];
    }

    /**
     * @Route("/room/{roomId}/user/print")
     */
    public function printlistAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(UserFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
            'isModerator' => $currentUser->isModerator(),
        ));

        // get the user manager service
        $userService = $this->get('commsy_legacy.user_service');

        $userService->resetLimits();
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        }

        // get user list from manager service 
        $users = $userService->getListUsers($roomId);
        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get user list from manager service 
        $itemsCountArray = $userService->getCountArray($roomId);

        
        $html = $this->renderView('CommsyBundle:User:listPrint.html.twig', [
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $this->get('commsy.print_service')->printList($html);
    }

    /**
     * @Route("/room/{roomId}/user/feedaction")
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
            foreach ($entries['materials'] as $key => $value) {
                $selectedIds[] = $value->getItemId();
            }
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
        $result = [];
        
        $noModeratorsError = false;
        
        if ($action == 'markread') {
            $userService = $this->get('commsy_legacy.user_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $userService->getUser($id);
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
        } else if ($action == 'user-delete') {
            if ($this->contextHasModerators($roomId, $selectedIds)) {
                $userService = $this->get('commsy_legacy.user_service');
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $noticedManager = $legacyEnvironment->getNoticedManager();
                $readerManager = $legacyEnvironment->getReaderManager();
                foreach ($selectedIds as $id) {
                    $item = $userService->getUser($id);
                    $item->delete();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('deleted %count% users',count($selectedIds), array('%count%' => count($selectedIds)));
            } else {
                $noModeratorsError = true;
            }
        } else if ($action == 'user-block') {
            if ($this->contextHasModerators($roomId, $selectedIds)) {
                $userService = $this->get('commsy_legacy.user_service');
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $noticedManager = $legacyEnvironment->getNoticedManager();
                $readerManager = $legacyEnvironment->getReaderManager();
                foreach ($selectedIds as $id) {
                    $item = $userService->getUser($id);
                    $item->setStatus(0);
                    $item->save();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('set status of %count% users to blocked',count($selectedIds), array('%count%' => count($selectedIds)));
            } else {
                $noModeratorsError = true;
            }
        } else if ($action == 'user-confirm') {
            $userService = $this->get('commsy_legacy.user_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $userService->getUser($id);
                $item->setStatus(2);
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('confirmed %count% users',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'user-status-reading-user') {
            if ($this->contextHasModerators($roomId, $selectedIds)) {
                $userService = $this->get('commsy_legacy.user_service');
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $noticedManager = $legacyEnvironment->getNoticedManager();
                $readerManager = $legacyEnvironment->getReaderManager();
                foreach ($selectedIds as $id) {
                    $item = $userService->getUser($id);
                    $item->setStatus(4);
                    $item->save();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('set status of %count% users to reading user',count($selectedIds), array('%count%' => count($selectedIds)));
            } else {
                $noModeratorsError = true;
            }
        } else if ($action == 'user-status-user') {
            if ($this->contextHasModerators($roomId, $selectedIds)) {
                $userService = $this->get('commsy_legacy.user_service');
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $noticedManager = $legacyEnvironment->getNoticedManager();
                $readerManager = $legacyEnvironment->getReaderManager();
                foreach ($selectedIds as $id) {
                    $item = $userService->getUser($id);
                    $item->setStatus(2);
                    $item->save();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('set status of %count% users to user',count($selectedIds), array('%count%' => count($selectedIds)));
            } else {
                $noModeratorsError = true;
            }
        } else if ($action == 'user-status-moderator') {
            $userService = $this->get('commsy_legacy.user_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $userService->getUser($id);
                $item->setStatus(3);
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('set status of %count% users to moderator',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'user-contact') {
            $userService = $this->get('commsy_legacy.user_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $userService->getUser($id);
                $item->makeContactPerson();
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('set status of %count% users to contact',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'user-contact-remove') {
            $userService = $this->get('commsy_legacy.user_service');
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
                $item = $userService->getUser($id);
                $item->makeNoContactPerson();
                $item->save();
            }
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('removed contact status of %count% users',count($selectedIds), array('%count%' => count($selectedIds)));
        } else {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ToDo: '.$action;
        }
        
        if ($noModeratorsError) {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('no moderators left', array(), 'user');
        }
        
        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }

    function contextHasModerators($roomId, $selectedIds) {
        $userService = $this->get('commsy_legacy.user_service');
        $moderators = $userService->getModeratorsForContext($roomId);
        
        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }
        
        foreach ($selectedIds as $selectedId) {
            if (in_array($selectedId, $moderatorIds)) {
                if(($key = array_search($selectedId, $moderatorIds)) !== false) {
                    unset($moderatorIds[$key]);
                }
            }
        }
        
        return !empty($moderatorIds);
    }
    
    /**
     * @Route("/room/{roomId}/user/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);
        
        return array(
            'roomId' => $roomId,
            'user' => $infoArray['user'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'userList' => $infoArray['userList'],
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
            'showRating' => false,
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'currentUser' => $infoArray['currentUser'],
            'linkedGroups' => $infoArray['linkedGroups'],

       );
    }

     


    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $userService = $this->get('commsy_legacy.user_service');
        $itemService = $this->get('commsy_legacy.item_service');

        
        $user = $userService->getUser($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $user;
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
        $roomItem = $roomManager->getItem($user->getContextId());        
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
        $readerManager->getLatestReaderByUserIDArray($id_array,$user->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($user->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $user->getModificationDate() ) {
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
        $reader = $readerService->getLatestReader($user->getItemId());
        if ( empty($reader) ) {
           $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $user->getModificationDate() ) {
           $readerList[$user->getItemId()] = 'changed';
        }
        
        $modifierList[$user->getItemId()] = $itemService->getAdditionalEditorsForItem($user);
        
        $users = $userService->getListUsers($roomId);
        $userList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundUser = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($users as $tempUser) {
            if (!$foundUser) {
                if ($counterBefore > 5) {
                    array_shift($userList);
                } else {
                    $counterBefore++;
                }
                $userList[] = $tempUser;
                if ($tempUser->getItemID() == $user->getItemID()) {
                    $foundUser = true;
                }
                if (!$foundUser) {
                    $prevItemId = $tempUser->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $userList[] = $tempUser;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempUser->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($users)) {
            if ($prevItemId) {
                $firstItemId = $users[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $users[sizeof($users)-1]->getItemId();
            }
        }

        $groupUser = $userService->getUser($itemId);

        $this->groupManager = $legacyEnvironment->getGroupManager();
        $this->groupManager->reset();
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->select();
        $groupList = $this->groupManager->get();


        $group = $groupList->getFirst();
        while ( $group ) {
            if (!$groupUser->isInGroup($group)){
                $groupList->removeElement($group);
            }
            $group = $groupList->getNext();
        }       
        $infoArray['user'] = $user;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['userList'] = $userList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($users);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = false;
        $infoArray['showWorkflow'] = false;
        $infoArray['currentUser'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['linkedGroups'] = $groupList->to_array();;


        
        return $infoArray;
    }


    /**
     * @Route("/room/{roomId}/user/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $userData = array();
        $userService = $this->get('commsy_legacy.user_service');
        $transformer = $this->get('commsy_legacy.transformer.user');
        
        // create new user item
        $userItem = $userService->getNewuser();
        $userItem->setTitle('['.$translator->trans('insert title').']');
        $userItem->setBibKind('none');
        $userItem->setDraftStatus(1);
        $userItem->save();

 
        return $this->redirectToRoute('commsy_user_detail', array('roomId' => $roomId, 'itemId' => $userItem->getItemId()));

    }


    /**
     * @Route("/room/{roomId}/user/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $userService = $this->get('commsy_legacy.user_service');
        $transformer = $this->get('commsy_legacy.transformer.user');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        
        $userItem = $userService->getuser($itemId);
        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }
        $formData = $transformer->transform($userItem);
        $formOptions = array(
            'action' => $this->generateUrl('commsy_user_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
                'roomId' => $roomId,
            )),
        );
        $form = $this->createForm(UserType::class, $formData, $formOptions);
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {
                $userItem = $transformer->applyTransformation($userItem, $form->getData());

                // update modifier
                $userItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $userItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_user_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }
        
        return array(
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
        );
    }


    /**
     * @Route("/room/{roomId}/user/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $userService = $this->get('commsy_legacy.user_service');
        $transformer = $this->get('commsy_legacy.transformer.user');
        
        $user = $userService->getUser($itemId);
        
        $itemArray = array($user);
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
		$readerManager->getLatestReaderByUserIDArray($id_array,$user->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($user->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $user->getModificationDate() ) {
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
            'item' => $user,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    
    /**
     * @Route("/room/{roomId}/user/{itemId}/image")
     */
    public function imageAction($roomId, $itemId)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $user = $userService->getUser($itemId);

        $file = $user->getPicture();
        
        $foundUserImage = true;
        
        if ($file != '') {
            $rootDir = $this->get('kernel')->getRootDir().'/';

            $environment = $this->get("commsy_legacy.environment")->getEnvironment();
            $disc_manager = $environment->getDiscManager();
            $disc_manager->setContextID($roomId);
            $portal_id = $environment->getCurrentPortalID();
            if ( isset($portal_id) and !empty($portal_id) ) {
                $disc_manager->setPortalID($portal_id);
            } else {
                $context_item = $environment->getCurrentContextItem();
                if ( isset($context_item) ) {
                    $portal_item = $context_item->getContextItem();
                    if ( isset($portal_item) ) {
                        $disc_manager->setPortalID($portal_item->getItemID());
                        unset($portal_item);
                    }
                    unset($context_item);
                }
            }
            $filePath = $disc_manager->getFilePath().$file;
    
            if (file_exists($rootDir.$filePath)) {
                $processedImage = $this->container->get('liip_imagine.data.manager')->find('commsy_user_image', str_ireplace('../files', './', $filePath));
                $content = $newimage_string = $this->container->get('liip_imagine.filter.manager')->applyFilter($processedImage, 'commsy_user_image')->getContent();
                
                if (!$content) {
                    $foundUserImage = false;
                    $file = 'user_unknown.gif';
                }
            } else {
                $foundUserImage = false;
                $file = 'user_unknown.gif';
            }
        } else {
            $foundUserImage = false;
            $file = 'user_unknown.gif';
        }
        
        if (!$foundUserImage) {
            $avatarService = $this->get('commsy.avatar_service');
            
            $content = $avatarService->getAvatar($itemId);
        }
        
        $response = new Response($content, Response::HTTP_OK, array('content-type' => 'image'));
        
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, \Nette\Utils\Strings::webalize($file));

        $response->headers->set('Content-Disposition', $contentDisposition);
        
        return $response;
    }
    
    /**
     * @Route("/room/{roomId}/user/rooms/{start}")
     * @Template("CommsyBundle:Menu:room_list.html.twig")
     */
    public function roomsAction($roomId, $max = 10, $start = 0)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $user = $userService->getCurrentUserItem();

        // Room list feed
        $rooms = $userService->getRoomList($user);

        return [
            'roomId' => $roomId,
            'roomList' => $rooms,
        ];


    }

    /**
     * Displays the global user actions in top navbar.
     * This is an embedded controller action.
     *
     * @Template()
     * 
     * @param  int $roomId The current room id
     * @return Response The HTML response
     */
    public function globalNavbarAction($roomId)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $currentUserItem = $userService->getCurrentUserItem();

        $privateRoomItem = $currentUserItem->getOwnRoom();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $sessionItem = $legacyEnvironment->getSessionItem();

        $currentClipboardIds = array();
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
        }
        
        return [
            'privateRoomItem' => $privateRoomItem,
            'count' => sizeof($currentClipboardIds),
            'roomId' => $legacyEnvironment->getCurrentContextId(),
        ];
    }
    /**
     * @Route("/room/{roomId}/user/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $html = $this->renderView('CommsyBundle:User:detailPrint.html.twig', [
            'roomId' => $roomId,
            'user' => $infoArray['user'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'userList' => $infoArray['userList'],
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
            'showRating' => false,
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'currentUser' => $infoArray['currentUser'],
            'linkedGroups' => $infoArray['linkedGroups'],
        ]);

        return $this->get('commsy.print_service')->printDetail($html);
    }
}
