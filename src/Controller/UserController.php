<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Entity\User;
use App\Event\UserStatusChangedEvent;
use App\Filter\UserFilterType;
use App\Form\Model\Send;
use App\Form\Type\Profile\AccountContactFormType;
use App\Form\Type\Profile\RoomProfileContactType;
use App\Form\Type\SendType;
use App\Form\Type\UserSendType;
use App\Form\Type\UserStatusChangeType;
use App\Form\Type\UserType;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\MailAssistant;
use App\Utils\RoomService;
use App\Utils\UserService;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends BaseController
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
     * @Route("/room/{roomId}/user/{itemId}/contactForm/{originPath}/{moderatorIds}")
     * @Template
     */
    public function sendMailViaContactForm(
        $roomId,
        $itemId,
        $originPath,
        Request $request,
        MailAssistant $mailAssistant,
        $moderatorIds = null,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        $formData = null;
        if(!is_null($item->getLinkedUserroomItem())){
            $recipients = [];
            array_push($recipients,$item->getFullName());
            foreach($item->getLinkedUserroomItem()->getModeratorList() as $moderators){
                array_push($recipients,$moderators->getFullName());
            }
            $message = $this->get('translator')->trans('This email has been sent by ... from userroom ...', [
                '%sender_name%' => $legacyEnvironment->getEnvironment()->getCurrentUserItem()->getFullName(),
                '%room_name%' => $item->getLinkedUserroomItem()->getTitle(),
                '%recipients%' => implode(', ',$recipients),
            ], 'mail');
            $message = '<br><br>--<br>'.$message;
            $search = ', ';
            $replace = ' & ';
            $message = strrev(implode(strrev($replace), explode(strrev($search), strrev($message), 2)));
            $formData = ['message' => $message];
        }

        $form = $this->createForm(AccountContactFormType::class, $formData, [
            'item' => $item,
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute($originPath, [
                    'roomId' => $roomId,
                    'itemId' => $item->getItemId(),
                ]);
            }

            // send mail
            $message = $mailAssistant->getSwiftMessageContactForm($form, $item, true, $moderatorIds, $userService);
            $this->get('mailer')->send($message);

            $recipientCount = count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
            $this->addFlash('recipientCount', $recipientCount);

            // redirect to success page
            return $this->redirectToRoute('app_user_sendsuccesscontact', [
                'roomId' => $roomId,
                'itemId' => $item->getItemId(),
                'originPath' => $originPath,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
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
        $filterForm = $this->createFilterForm($roomItem, $view);

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
    public function printlistAction($roomId, Request $request, $sort, PrintService $printService)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createFilterForm($roomItem);

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

        $readerList = array();
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get user list from manager service 
        $itemsCountArray = $userService->getCountArray($roomId);


        $html = $this->renderView('user/list_print.html.twig', [
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/user/changeStatus")
     * @Template()
     * @Security("is_granted('MODERATOR')")
     */
    public function changeStatusAction(
        $roomId,
        Request $request,
        EventDispatcherInterface $eventDispatcher)
    {
        $room = $this->getRoom($roomId);

        $formData = [];

        if ($request->query->has('userDetail')) {
            $formData['status'] = $request->query->get('status');
            $formData['userIds'] = $request->query->get('userIds');
        } else {
            if (!$request->request->has('user_status')) {
                $formData['status'] = $request->request->get('status');

                $users = $this->getItemsForActionRequest($room, $request);
                foreach ($users as $user) {
                    $userIds[] = $user->getItemId();
                }

                $formData['userIds'] = $userIds;
            } else {
                $postData = $request->request->get('user_status');
                $formData['userIds'] = $postData['userIds'];
            }
        }

        $form = $this->createForm(UserStatusChangeType::class, $formData);
        $form->handleRequest($request);

        // get all affected user
        $userService = $this->get('commsy_legacy.user_service');
        $users = [];
        if (isset($formData['userIds'])) {
            foreach ($formData['userIds'] as $userId) {
                $user = $userService->getUser($userId);
                if ($user) {
                    $users[] = $user;
                }
            }
        }

        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();

                // manual validation - moderator count check
                if (in_array($formData['status'], ['user-delete', 'user-block', 'user-status-reading-user', 'user-status-user', 'user-confirm'])) {
                    if (!$this->contextHasModerators($roomId, $formData['userIds'])) {
                        $translator = $this->get('translator');
                        $form->addError(new FormError($translator->trans('no moderators left', [], 'user')));
                    }
                }

                if ($form->isSubmitted() && $form->isValid()) {
                    switch ($formData['status']) {
                        case 'user-delete':
                            foreach ($users as $user) {
                                $user->delete();
                                $user->save();
                            }
                            break;

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

                        $event = new UserStatusChangedEvent($user);
                        $eventDispatcher->dispatch($event);
                    }

                    if ($formData['inform_user']) {
                        $this->sendUserInfoMail($formData['userIds'], $formData['status']);
                    }
                    if ($request->query->has('userDetail') && $formData['status'] !== 'user-delete') {
                        return $this->redirectToRoute('app_user_detail', [
                            'roomId' => $roomId,
                            'itemId' => array_values($request->query->get('userIds'))[0],
                        ]);
                    }
                    else {
                        return $this->redirectToRoute('app_user_list', [
                            'roomId' => $roomId,
                        ]);
                    }
                }
            }
            elseif ($form->get('cancel')->isClicked()) {
                if($request->query->has('userDetail')) {
                    return $this->redirectToRoute('app_user_detail', [
                        'roomId' => $roomId,
                        'itemId' => array_values($request->query->get('userIds'))[0],
                    ]);
                }
                else {
                    return $this->redirectToRoute('app_user_list', [
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
    public function detailAction($roomId, $itemId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');
        $currentUser = $legacyEnvironment->getCurrentUserItem();

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

        $itemService = $this->get('commsy_legacy.item_service');
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        $roomItem = $roomService->getRoomItem($roomId);
        $moderatorListLength = $roomItem->getModeratorList()->getCount();

        $moderatorIds = null;
        $userRoomItem = null;
        if(!is_null($infoArray['user']->getLinkedUserroomItem())
            and $this->isGranted('ITEM_ENTER', $infoArray['user']->getLinkedUserroomItemId())){
            $userRoomItem = $infoArray['user']->getLinkedUserroomItem();
            $moderators = $infoArray['user']->getLinkedUserroomItem()->getModeratorList();
            $moderatorIds = [];
            foreach($moderators as $moderator){
                array_push($moderatorIds, $moderator->getItemId());
            }
        }

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
            'moderatorIds' => implode(', ', $moderatorIds),
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => false,
            'userRoomItem' => $userRoomItem,
            'userRoomItemMemberCount' => $userRoomItem == null ? [] : count($userRoomItem->getUserList()) + count($userRoomItem->getModeratorList()),
            'userRoomLinksCount' => count($userRoomItem == null ? [] : $userRoomItem->getAllLinkedItemIDArray()),
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


    private function getDetailInfo($roomId, $itemId)
    {
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
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'user')")
     */
    public function createAction($roomId)
    {
        $translator = $this->get('translator');

        $userService = $this->get('commsy_legacy.user_service');

        // create new user item
        $userItem = $userService->getNewuser();
        $userItem->setTitle('['.$translator->trans('insert title').']');
        $userItem->setBibKind('none');
        $userItem->setDraftStatus(1);
        $userItem->save();


        return $this->redirectToRoute('app_user_detail', array('roomId' => $roomId, 'itemId' => $userItem->getItemId()));
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
            'action' => $this->generateUrl('app_user_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'uploadUrl' => $this->generateUrl('app_upload_upload', array(
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
            return $this->redirectToRoute('app_user_save', array('roomId' => $roomId, 'itemId' => $itemId));
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
     * @Route("/room/{roomId}/user/{itemId}/send")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'user')")
     */
    public function sendAction($roomId, $itemId, Request $request, MailAssistant $mailAssistant)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        $translator = $this->get('translator');
        $defaultBodyMessage = '<br/><br/><br/>' . '--' . '<br/>' . $translator->trans(
                'This email has been sent by sender to recipient',
                ['%sender_name%' => $currentUser->getFullName(), '%recipient_name%' => $item->getFullName()],
                'mail'
            );

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(UserSendType::class, $formData, [
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $legacyEnvironment->getCurrentPortalItem();

                $from = $this->getParameter('commsy.email.from');

                // TODO: validate sender & recipient email addresses (similar to sendMultipleAction())
                $sender = [$currentUser->getEmail() => $currentUser->getFullName()];
                $recipient = [$item->getEmail() => $item->getFullName()];

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $message = (new \Swift_Message())
                    ->setSubject($formData['subject'])
                    ->setBody($formData['message'], 'text/html')
                    ->setFrom([$from => $portalItem->getTitle()]);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $message = $mailAssistant->addAttachments($formDataFiles, $message);
                }

                if ($currentUser->isEmailVisible()) {
                    $message->setReplyTo($sender);
                }

                // form option: copy_to_sender
                if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                    if ($currentUser->isEmailVisible()) {
                        $message->setCc($sender);
                    } else {
                        $message->addBcc(key($sender), current($sender));
                    }
                }

                if ($item->isEmailVisible()) {
                    $message->setTo($recipient);
                } else {
                    $message->addBcc(key($recipient), current($recipient));
                }

                // send mail
                $this->get('mailer')->send($message);

                // redirect to success page
                return $this->redirectToRoute('app_user_sendsuccess', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            } else {
                // redirect to user detail view
                return $this->redirectToRoute('app_user_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }
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
            'link' => $this->generateUrl('app_user_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getFullname(),
        ];
    }


    /**
     * @Route("/room/{roomId}/user/{itemId}/send/success/contact/{originPath}")
     * @Template()
     **/
    public function sendSuccessContactAction($roomId, $itemId, $originPath)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'link' => $this->generateUrl($originPath, [
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
     * @Template("menu/room_list.html.twig")
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
        } else {
            if ($currentUserItem->isRoot()) {
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
    public function printAction($roomId, $itemId, PrintService $printService)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $html = $this->renderView('user/detail_print.html.twig', [
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

        return $printService->buildPdfResponse($html);
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

                $mailMessage = (new \Swift_Message())
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
        $userService->resetLimits();

        if ($userFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($roomItem, $view);

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
        $linkedUserRooms = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($currentUser->isModerator()) {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail', 'xhrcopy', 'copy', 'save', 'user-delete', 'user-block', 'user-confirm', 'user-status-reading-user', 'user-status-user', 'user-status-moderator', 'user-contact', 'user-contact-remove'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail', 'xhrcopy'];
            }
            if(!is_null($item->getLinkedUserroomItem())
            and $this->isGranted('ITEM_ENTER', $item->getLinkedUserroomItemID())){
                $linkedUserRooms[strval($item->getItemID())] = $item->getLinkedUserroomItem();
            }
        }

        return [
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
            'linkedUserRooms' => $linkedUserRooms,
        ];
    }

    /**
     * @Route("/room/{roomId}/user/xhrCopy")
     * @Template()
     */
    public function xhrCopyAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/user/sendMultiple")
     * @Template()
     */
    public function sendMultipleAction($roomId, Request $request, MailAssistant $mailAssistant)
    {
        $room = $this->getRoom($roomId);

        $userIds = [];
        if (!$request->request->has('user_send')) {
            $users = $this->getItemsForActionRequest($room, $request);

            foreach ($users as $user) {
                $userIds[] = $user->getItemId();
            }
        } else {
            $postData = $request->request->get('user_send');
            $userIds = $postData['users'];
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $userService = $this->get('commsy_legacy.user_service');

        // include a footer message in the email body (which may be esp. useful if some emails are sent via BCC mail)
        $userCount = count($userIds);
        $defaultBodyMessage = '';
        if ($userCount) {
            $defaultBodyMessage .= '<br/><br/><br/>' . '--' . '<br/>';
            $translator = $this->get('translator');
            if ($userCount == 1) {
                $user = $userService->getUser(reset($userIds));
                if ($user) {
                    $defaultBodyMessage .= $translator->trans(
                        'This email has been sent by sender to recipient',
                        ['%sender_name%' => $currentUser->getFullName(), '%recipient_name%' => $user->getFullName()],
                        'mail'
                    );
                }
            } elseif ($userCount > 1) {
                $defaultBodyMessage .= $translator->trans(
                    'This email has been sent to multiple users of this room',
                    ['%sender_name%' => $currentUser->getFullName(), '%user_count%' => count($userIds), '%room_name%' => $room->getTitle()],
                    'mail'
                );
            }
        }

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
            'users' => $userIds,
        ];

        $form = $this->createForm(UserSendType::class, $formData, [
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        // get all affected user
        $users = [];
        if (isset($formData['users'])) {
            foreach ($formData['users'] as $userId) {
                $user = $userService->getUser($userId);
                if ($user) {
                    $users[] = $user;
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $legacyEnvironment->getCurrentPortalItem();

                $from = $this->getParameter('commsy.email.from');

                // NOTE: as of #2461 all mail should be sent as BCC mail; but, for now, we keep the original logic here
                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $forceBCCMail = true;

                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
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

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $message = (new \Swift_Message())
                    ->setSubject($formData['subject'])
                    ->setBody($formData['message'], 'text/html')
                    ->setFrom([$from => $portalItem->getTitle()])
                    ->setReplyTo($replyTo);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $message = $mailAssistant->addAttachments($formDataFiles, $message);
                }

                $recipientCount = 0;

                if ($forceBCCMail) {
                    $allRecipients = array_merge($to, $toCC, $toBCC);
                    $message->setBcc($allRecipients);
                    $recipientCount += count($allRecipients);
                } else {
                    if (!empty($to)) {
                        $message->setTo($to);
                        $recipientCount += count($to);
                    }

                    if (!empty($toCC)) {
                        $message->setCc($toCC);
                        $recipientCount += count($toCC);
                    }

                    if (!empty($toBCC)) {
                        $message->setBcc($toBCC);
                        $recipientCount += count($toBCC);
                    }
                }

                $this->addFlash('recipientCount', $recipientCount);

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
                return $this->redirectToRoute('app_user_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to user feed
                return $this->redirectToRoute('app_user_list', [
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
            'link' => $this->generateUrl('app_user_list', [
                'roomId' => $roomId,
            ]),
        ];
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/user/xhr/markread", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrMarkReadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    // NOTE: to allow for email notifications on delete, the 'user-delete' action is used instead of the 'delete' action
//    /**
//     * @Route("/room/{roomId}/user/xhr/delete", condition="request.isXmlHttpRequest()")
//     * @throws \Exception
//     */
//    public function xhrDeleteAction($roomId, Request $request)
//    {
//        $room = $this->getRoom($roomId);
//        $items = $this->getItemsForActionRequest($room, $request);
//
//        $action = $this->get('commsy.action.delete.generic');
//        return $action->execute($room, $items);
//    }

    /**
     * @param \cs_room_item $room
     * @param string $view
     * @return FormInterface
     */
    private function createFilterForm($room, $view = null)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'activated' => true,
            'user_status' => 8,
        ];

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        return $this->createForm(UserFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_user_list', [
                'roomId' => $room->getItemID(),
                'view' => $view,
            ]),
            'hasHashtags' => false,
            'hasCategories' => false,
            'isModerator' => $currentUser->isModerator(),
        ]);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_user_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        // get the user service
        $userService = $this->get('commsy_legacy.user_service');

        if ($selectAll) {
            if ($request->query->has('user_filter')) {
                $currentFilter = $request->query->get('user_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $userService->setFilterConditions($filterForm);
            } else {
                $userService->showNoNotActivatedEntries();
                $userService->showUserStatus(8);
            }

            return $userService->getListUsers($roomItem->getItemID());
        } else {
            return $userService->getUsersById($roomItem->getItemID(), $itemIds);
        }
    }
}
