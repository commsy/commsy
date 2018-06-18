<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Form\Type\UserStatusChangeType;
use CommsyBundle\Utils\AccountMail;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Entity\User;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\UserFilterType;

use CommsyBundle\Form\Type\UserType;
use CommsyBundle\Form\Type\UserSendType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class UserController
 * @package CommsyBundle\Controller
 */
class UserController extends Controller
{

    /**
     * @Route("/room/{roomId}/user/feed/{start}/{sort}")
     * @Template()
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'name', Request $request)
    {
        return $this->gatherUsers($roomId, $max, $start, $sort, 'feedView', $request);
    }

    /**
     * @Route("/room/{roomId}/user/grid/{start}/{sort}")
     * @Template()
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     */
    public function gridAction($roomId, $max = 10, $start = 0, $sort = 'name', Request $request)
    {
        return $this->gatherUsers($roomId, $max, $start, $sort, 'gridView', $request);
    }
    
    /**
     * @Route("/room/{roomId}/user/{view}", defaults={"view": "feedView"}, requirements={
     *       "view": "feedView|gridView"
     * })
     * @Template()
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     */
    public function listAction($roomId, $view, Request $request)
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
            'user_status' => 8,
        ];
        $filterForm = $this->createForm(UserFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('commsy_user_list', [
                'roomId' => $roomId,
                'view' => $view,
            ]),
            'hasHashtags' => false,
            'hasCategories' => false,
            'isModerator' => $currentUser->isModerator(),
        ]);

        // get the user manager service
        $userService = $this->get('commsy_legacy.user_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        } else {
            $userService->showNoNotActivatedEntries();
            $userService->showUserStatus(8);
        }

        // get filtered and total number of results
        $itemsCountArray = $userService->getCountArray($roomId, $currentUser->isModerator());
        $itemsCountArray['countAll'] = $itemsCountArray['count'];

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('user') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('user');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('user');
        }

        // number of users which are waiting for confirmation
        $userTasks = $this->getDoctrine()->getRepository(User::class)->getConfirmableUserByContextId($roomId)->getQuery()->getResult();

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
            'usageInfo' => $usageInfo,
            'view' => $view,
            'isArchived' => $roomItem->isArchived(),
            'userTasks' => $userTasks,
            'isModerator' => $currentUser->isModerator(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/sendmail")
     * @Template()
     */
    public function sendMailAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $userService = $this->get('commsy_legacy.user_service');

        $userItems = array();

        $userIds = $request->query->get('userIds');

        foreach ($userIds as $userId) {
            $userItems[] = $userService->getUser($userId);
        }

        $formData = [
            'additional_recipients' => [],
            'send_to_groups' => [],
            'send_to_group_all' => false,
            'send_to_all' => false,
            'message' => '',
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(SendType::class, $formData, [
            'users' => $userItems,
        ]);

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/print/{sort}", defaults={"sort" = "none"})
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     */
    public function printlistAction($roomId, Request $request, $sort)
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
        $numAllUsers = $userService->getCountArray($roomId)['countAll'];

        $userService->resetLimits();
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        }

        $users = $userService->getListUsers($roomId);

        // get user list from manager service
        if ($sort != "none") {
            $users = $userService->getListUsers($roomId, $numAllUsers, 0, $sort);
        }
        elseif ($this->get('session')->get('sortUsers')) {
            $users = $userService->getListUsers($roomId, $numAllUsers, 0, $this->get('session')->get('sortUsers'));
        }
        else {
            $users = $userService->getListUsers($roomId, $numAllUsers, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get user list from manager service 
        $itemsCountArray = $userService->getCountArray($roomId);

        
        $html = $this->renderView('CommsyBundle:user:list_print.html.twig', [
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/user/changeStatus")
     * @Template()
     * @Security("is_granted('MODERATOR')")
     */
    public function changeStatusAction($roomId, Request $request)
    {
        $formData = [];

        // first call will pass query parameter
        if ($request->query->has('status')) {
            $formData['status'] = $request->query->get('status');
        }

        if ($request->query->has('userIds')) {
            $formData['userIds'] = $request->query->get('userIds');
        }

        $form = $this->createForm(UserStatusChangeType::class, $formData);
        $form->handleRequest($request);

        // get all affected user
        $userService = $this->get('commsy_legacy.user_service');
        $users = [];
        foreach ($formData['userIds'] as $userId) {
            $user = $userService->getUser($userId);
            if ($user) {
                $users[] = $user;
            }
        }

        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();

                // manual validation - moderator count check
                if (in_array($formData['status'], ['user-block', 'user-status-reading-user', 'user-status-user', 'user-confirm'])) {
                    if (!$this->contextHasModerators($roomId, $formData['userIds'])) {
                        $translator = $this->get('translator');
                        $form->addError(new FormError($translator->trans('no moderators left', [], 'user')));
                    }
                }

                if ($form->isSubmitted() && $form->isValid()) {
                    switch ($formData['status']) {
                        case 'user-block':
                            foreach ($users as $user) {
                                $user->setStatus(0);
                                $user->save();
                            }
                            break;

                        case 'user-confirm':
                            foreach ($users as $user) {
                                $user->setStatus(2);
                                $user->save();
                            }
                            break;

                        case 'user-status-reading-user':
                            foreach ($users as $user) {
                                $user->setStatus(4);
                                $user->save();
                            }
                            break;

                        case 'user-status-user':
                            foreach ($users as $user) {
                                $user->setStatus(2);
                                $user->save();
                            }
                            break;

                        case 'user-status-moderator':
                            foreach ($users as $user) {
                                $user->setStatus(3);
                                $user->save();
                            }
                            break;

                        case 'user-contact':
                            foreach ($users as $user) {
                                $user->makeContactPerson();
                                $user->save();
                            }
                            break;

                        case 'user-contact-remove':
                            foreach ($users as $user) {
                                $user->makeNoContactPerson();
                                $user->save();
                            }
                            break;
                    }

                    $userService = $this->get('commsy_legacy.user_service');
                    foreach ($users as $user) {
                        $userService->updateAllGroupStatus($user, $roomId);
                    }

                    $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                    $readerManager = $legacyEnvironment->getReaderManager();
                    $noticedManager = $legacyEnvironment->getNoticedManager();
                    foreach ($users as $user) {
                        $itemId = $user->getItemID();
                        $versionId = $user->getVersionID();
                        $readerManager->markRead($itemId, $versionId);
                        $noticedManager->markNoticed($itemId, $versionId);
                    }

                    if ($formData['inform_user']) {
                        $this->sendUserInfoMail($formData['userIds'], $formData['status']);
                    }
                    if($request->query->has('userDetail')) {
                        return $this->redirectToRoute('commsy_user_detail', [
                            'roomId' => $roomId,
                            'itemId' => array_values($request->query->get('userIds'))[0],
                        ]);
                    }
                    else {
                        return $this->redirectToRoute('commsy_user_list', [
                            'roomId' => $roomId,
                        ]);
                    }
                }
            }
            elseif ($form->get('cancel')->isClicked()) {
                if($request->query->has('userDetail')) {
                    return $this->redirectToRoute('commsy_user_detail', [
                        'roomId' => $roomId,
                        'itemId' => array_values($request->query->get('userIds'))[0],
                    ]);
                }
                else {
                    return $this->redirectToRoute('commsy_user_list', [
                        'roomId' => $roomId,
                    ]);
                }
            }
        }

        return [
            'users' => $users,
            'form' => $form->createView(),
            'status' => $formData['status'],
        ];
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
        $sort = $request->request->get('sort');
        
        if ($selectAll == 'true') {
            $entries = $this->feedAction($roomId, $max = 1000, $start = $selectAllStart, $sort, $request);
            foreach ($entries['users'] as $key => $value) {
                $selectedIds[] = $value->getItemId();
            }
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
        $result = [];
        
        $noModeratorsError = false;
        
        $userService = $this->get('commsy_legacy.user_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $noticedManager = $legacyEnvironment->getNoticedManager();
        $readerManager = $legacyEnvironment->getReaderManager();

        if ($action == 'markread') {
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

        } else if ($action == 'user-send-mail') {
            return $this->redirectToRoute('commsy_user_sendmail', array('roomId' => $roomId, 'userIds' => $selectedIds));
        } else if ($action == 'sendmail') {
            return new JsonResponse([
                'redirect' => $this->generateUrl('commsy_user_sendmultiple', array('roomId' => $roomId, 'userIds' => $selectedIds)),
            ]);
        } else if ($action == 'user-delete') {
            if ($this->contextHasModerators($roomId, $selectedIds)) {
                foreach ($selectedIds as $id) {
                    $item = $userService->getUser($id);
                    $item->delete();
                }
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
            } else {
                $noModeratorsError = true;
            }
        } else {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ToDo: '.$action;
        }
        
        if ($noModeratorsError) {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('no moderators left', array(), 'user');
        }

        // inform user about account action
        if (in_array($action, ['user-delete'])) {

            if (!$noModeratorsError) {
                $this->sendUserInfoMail($selectedIds, $action);
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
     * @Route("/room/{roomId}/user/{itemId}/delete", requirements={
     *     "itemId": "\d+"
     * }))
     * @Security("is_granted('MODERATOR')")
     */
    public function deleteAction($roomId, $itemId, Request $request) {
        // FIXME: popup confirm-cancel dialog does not work, yet!
        $translator = $this->get('translator');
        if ($this->contextHasModerators($roomId, [$itemId])) {
            $userService = $this->get('commsy_legacy.user_service');
            $user = $userService->getUser($itemId);
            //$user->delete();
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->trans('1 deleted entries');
            $this->sendUserInfoMail([$itemId], 'user-delete');
        } else {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('no moderators left', array(), 'user');
        }

        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => [],
        ]);
        //return $this->redirectToRoute('commsy_user_list', array('roomId' => $roomId));
    }

    private function contextHasModerators($roomId, $selectedIds) {
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
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'user')")
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $alert = null;
        if ($infoArray['user']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $isSelf = false;
        if ($legacyEnvironment->getCurrentUserItem()->getItemId() == $itemId) {
            $isSelf = true;
        }

        $markupService = $this->get('commsy_legacy.markup');
        $itemService = $this->get('commsy_legacy.item_service');
        $markupService->addFiles($itemService->getItemFileList($itemId));

        $roomItem = $roomService->getRoomItem($roomId);
        $moderatorListLength = $roomItem->getModeratorList()->getCount();
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
            'userComment' => $infoArray['comment'],
            'status' => $infoArray['status'],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isSelf' => $isSelf,
            'moderatorListLength' => $moderatorListLength,
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
        $infoArray['linkedGroups'] = $userService->getUser($itemId)->getGroupList()->to_array();;
        $infoArray['comment'] = $user->getUserComment();
        $infoArray['status'] = $user->getStatus();

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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'user')")
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
        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'user')")
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
     * @Route("/room/{roomId}/user/sendMultiple")
     * @Template()
     */
    public function sendMultipleAction($roomId, Request $request)
    {
        if (!$request->query->has('userIds')) {
            throw $this->createNotFoundException('no user ids found');
        }

        $userIds = $request->query->get('userIds');

        $userService = $this->get('commsy_legacy.user_service');

        $formData = [
            'message' => '',
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(UserSendType::class, $formData, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

                $portalItem = $legacyEnvironment->getCurrentPortalItem();
                $currentUser = $legacyEnvironment->getCurrentUserItem();

                $from = $this->getParameter('commsy.email.from');

                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $users = [];
                $failedUsers = [];
                foreach ($userIds as $userId) {
                    $user = $userService->getUser($userId);
                    $users[] = $user;
                    if ($validator->isValid($user->getEmail(), new RFCValidation())) {
                        if ($user->isEmailVisible()) {
                            $to[$user->getEmail()] = $user->getFullName();
                        } else {
                            $toBCC[$user->getEmail()] = $user->getFullName();
                        }
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                $toCC = [];
                if ($validator->isValid($currentUser->getEmail(), new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[$currentUser->getEmail()] = $currentUser->getFullName();
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        if ($currentUser->isEmailVisible()) {
                            $toCC[$currentUser->getEmail()] = $currentUser->getFullName();
                        } else {
                            $toBCC[$currentUser->getEmail()] = $currentUser->getFullName();
                        }
                    }
                }

                $message = \Swift_Message::newInstance()
                    ->setSubject($formData['subject'])
                    ->setBody($formData['message'], 'text/html')
                    ->setFrom([$from => $portalItem->getTitle()])
                    ->setReplyTo($replyTo)
                    ->setTo($to);

                if (!empty($toCC)) {
                    $message->setCc($toCC);
                }

                if (!empty($toBCC)) {
                    $message->setBcc($toBCC);
                }

                // send mail
                $failedRecipients = [];
                $this->get('mailer')->send($message, $failedRecipients);

                foreach ($failedUsers as $failedUser) {
                    $this->addFlash('failedRecipients', $failedUser->getUserId());
                }

                foreach ($failedRecipients as $failedRecipient) {
                    $failedUser = array_filter($users, function($user) use ($failedRecipient) {
                        return $user->getEmail() == $failedRecipient;
                    });

                    if ($failedUser) {
                        $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
                    }
                }

                // redirect to success page
                return $this->redirectToRoute('commsy_user_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to user feed
                return $this->redirectToRoute('commsy_user_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/user/sendMultiple/success")
     * @Template()
     **/
    public function sendMultipleSuccessAction($roomId)
    {
        return [
            'link' => $this->generateUrl('commsy_user_list', [
                'roomId' => $roomId,
            ]),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/send")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'user')")
     */
    public function sendAction($roomId, $itemId, Request $request)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $formData = [
            'message' => '',
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(UserSendType::class, $formData, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

            $portalItem = $legacyEnvironment->getCurrentPortalItem();
            $currentUser = $legacyEnvironment->getCurrentUserItem();

            $from = $this->getParameter('commsy.email.from');

            $message = \Swift_Message::newInstance()
                ->setSubject($formData['subject'])
                ->setBody($formData['message'], 'text/html')
                ->setFrom([$from => $portalItem->getTitle()])
                ->setReplyTo([$currentUser->getEmail() => $currentUser->getFullName()])
                ->setTo([$item->getEmail() => $item->getFullName()]);

            // form option: copy_to_sender
            if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                $message->setCc($message->getReplyTo());
            }

            // send mail
            $this->get('mailer')->send($message);

            // redirect to success page
            return $this->redirectToRoute('commsy_user_sendsuccess', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/send/success")
     * @Template()
     **/
    public function sendSuccessAction($roomId, $itemId)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'link' => $this->generateUrl('commsy_user_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getFullname(),
        ];
    }

    /**
     * @Route("/room/user/guestimage")
     */
    public function guestimageAction()
    {
        $avatarService = $this->get('commsy.avatar_service');
        $response = new Response($avatarService->getUnknownUserImage(), Response::HTTP_OK, array('content-type' => 'image'));
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, \Nette\Utils\Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/initials")
     */
    public function initialsAction($roomId, $itemId, Request $request) {
        $avatarService = $this->get('commsy.avatar_service');
        $response = new Response($avatarService->getAvatar($itemId), Response::HTTP_OK, array('content-type' => 'image'));
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, \Nette\Utils\Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/image")
     */
    public function imageAction($roomId, $itemId, Request $request)
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
        
        $referer = $request->headers->get('referer');

        if (!$foundUserImage || (!preg_match("/room\/".$roomId."/", $referer) && !preg_match("/dashboard/", $referer) ) ) {
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
     * @Template("CommsyBundle:menu:room_list.html.twig")
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
        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $currentClipboardIds = array();
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
        }

        $showPortalConfigurationLink = false;
        $currentPortalUserItem = $currentUserItem->getRelatedPortalUserItem();
        if ($currentPortalUserItem) {
            if ($currentPortalUserItem->isModerator()) {
                $showPortalConfigurationLink = true;
            }
        }

        // NOTE: getRelatedPortalUserItem() sets some limits which need to get reset again before feedAction gets called
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->resetLimits();

        return [
            'privateRoomItem' => $privateRoomItem,
            'count' => sizeof($currentClipboardIds),
            'roomId' => $legacyEnvironment->getCurrentContextId(),
            'supportLink' => $portalItem->getSupportPageLink(),
            'tooltip' => $portalItem->getSupportPageLinkTooltip(),
            'showPortalConfigurationLink' => $showPortalConfigurationLink,
            'portal' => $portalItem,
        ];
    }

    /**
     * Displays the all room link in top navbar.
     * This is an embedded controller action.
     *
     * @Template()
     *
     * @return Response The HTML response
     */
    public function allRoomsNavbarAction()
    {
        $userService = $this->get('commsy_legacy.user_service');
        $currentUserItem = $userService->getCurrentUserItem();

        $privateRoomItem = $currentUserItem->getOwnRoom();

        if ($privateRoomItem) {
            $itemId = $privateRoomItem->getItemId();
        } else {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $itemId = $legacyEnvironment->getCurrentContextId();
        }

        return [
            'itemId' => $itemId,
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $html = $this->renderView('CommsyBundle:user:detail_print.html.twig', [
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

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }

    private function sendUserInfoMail($userIds, $action)
    {
        $accountMail = $this->get('commsy.utils.mail_account');
        $mailer = $this->get('mailer');

        $fromAddress = $this->getParameter('commsy.email.from');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $fromSender = $legacyEnvironment->getCurrentContextItem()->getContextItem()->getTitle();

        $userService = $this->get('commsy_legacy.user_service');

        $validator = new EmailValidator();
        $replyTo = [];
        $currentUserEmail = $currentUser->getEmail();
        if ($validator->isValid($currentUserEmail, new RFCValidation())) {
            if ($currentUser->isEmailVisible()) {
                $replyTo[$currentUserEmail] = $currentUser->getFullName();
            }
        }

        $users = [];
        $failedUsers = [];
        foreach ($userIds as $userId) {
            $user = $userService->getUser($userId);

            $userEmail = $user->getEmail();
            if (!empty($userEmail) && $validator->isValid($userEmail, new RFCValidation())) {
                $to = [$userEmail => $user->getFullname()];
                $subject = $accountMail->generateSubject($action);
                $body = $accountMail->generateBody($user, $action);

                $mailMessage = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setBody($body, 'text/plain')
                    ->setFrom([$fromAddress => $fromSender])
                    ->setReplyTo($replyTo);

                if ($user->isEmailVisible()) {
                    $mailMessage->setTo($to);
                } else {
                    $mailMessage->setBcc($to);
                }

                // send mail
                $failedRecipients = [];
                $mailer->send($mailMessage, $failedRecipients);
            } else {
                $failedUsers[] = $user;
            }
        }

        foreach ($failedUsers as $failedUser) {
            $this->addFlash('failedRecipients', $failedUser->getUserId());
        }

        foreach ($failedRecipients as $failedRecipient) {
            $failedUser = array_filter($users, function($user) use ($failedRecipient) {
                return $user->getEmail() == $failedRecipient;
            });

            if ($failedUser) {
                $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
            }
        }
    }

    private function gatherUsers($roomId, $max = 10, $start = 0, $sort = 'name', $view, Request $request) {
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
                'user_status' => 8,
            ];

            $filterForm = $this->createForm(UserFilterType::class, $defaultFilterValues, [
                'action' => $this->generateUrl('commsy_user_list', [
                    'roomId' => $roomId,
                    'view' => $view,
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
            $userService->showUserStatus(8);
        }

        // get user list from manager service
        $users = $userService->getListUsers($roomId, $max, $start, $currentUser->isModerator(), $sort);

        $this->get('session')->set('sortUsers', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = [];
        $allowedActions = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($currentUser->isModerator()) {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail', 'copy', 'save', 'user-delete', 'user-block', 'user-confirm', 'user-status-reading-user', 'user-status-user', 'user-status-moderator', 'user-contact', 'user-contact-remove'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail'];
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
}
