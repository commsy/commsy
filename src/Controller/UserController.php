<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Action\Copy\CopyAction;
use App\Action\Copy\InsertAction;
use App\Action\Copy\InsertUserroomAction;
use App\Entity\User;
use App\Event\UserLeftRoomEvent;
use App\Event\UserStatusChangedEvent;
use App\Filter\UserFilterType;
use App\Form\DataTransformer\UserTransformer;
use App\Form\Type\Profile\AccountContactFormType;
use App\Form\Type\SendType;
use App\Form\Type\UserSendType;
use App\Form\Type\UserStatusChangeType;
use App\Form\Type\UserType;
use App\Services\AvatarService;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\ItemService;
use App\Utils\MailAssistant;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\TopicService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends BaseController
{
    private $userService;

    public function __construct(RoomService $roomService, UserService $userService)
    {
        parent::__construct($roomService);
        $this->userService = $userService;
    }

    /**
     * @Route("/room/{roomId}/user/feed/{start}/{sort}")
     * @Template()
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     * @param Request $request
     * @param ReaderService $readerService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'name'
    ) {
        return $this->gatherUsers($roomId, 'feedView', $request, $readerService, $legacyEnvironment, $max, $start, $sort);
    }

    /**
     * @Route("/room/{roomId}/user/grid/{start}/{sort}")
     * @Template()
     * @Security("is_granted('RUBRIC_SEE', 'user')")
     * @param Request $request
     * @param ReaderService $readerService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function gridAction(
        Request $request,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'name'
    ) {
        return $this->gatherUsers($roomId, 'gridView', $request, $readerService, $legacyEnvironment, $max, $start, $sort);
    }
/**
     * @Route("/room/{roomId}/user/{itemId}/contactForm/{originPath}/{moderatorIds}")
     * @Template
     */
    public function sendMailViaContactForm(
        $roomId,
        $itemId,
        $originPath,
        $moderatorIds = null,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserTransformer $userTransformer,
        ItemService $itemService,
        \Swift_Mailer $mailer
    ) {

        $environment = $legacyEnvironment->getEnvironment();

        $item = $itemService->getTypedItem($itemId);
        $formData = null;
        if(!is_null($item->getLinkedUserroomItem())){
            $recipients = [];
            $recipients[$item->getFullName()] = $item->getFullName();
            foreach ($item->getLinkedUserroomItem()->getModeratorList() as $moderator) {
                $recipients[$moderator->getFullName()] = $moderator->getFullName();
            }
            $message = $this->get('translator')->trans('This email has been sent by ... from userroom ...', [
                '%sender_name%' => $environment->getCurrentUserItem()->getFullName(),
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
            $message = $mailAssistant->getSwiftMessageContactForm($form, $item, true, $moderatorIds, $this->userService);
            $mailer->send($message);


            $countTo = 0;
            $countCc = 0;
            $countBcc = 0;

            if(!is_null($message->getTo())){
                $countTo = count($message->getTo());
            }
            if(!is_null($message->getCc())){
                $countTo = count($message->getCc());
            }
            if(!is_null($message->getBcc())){
                $countTo = count($message->getBcc());
            }

            $recipientCount = $countTo + $countCc + $countBcc;
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
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param $view
     * @return array
     */
    public function listAction(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        $view
    ) {
        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();

        $roomManager = $environment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createFilterForm($legacyEnvironment, $roomItem, $view);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in user manager
            $this->userService->setFilterConditions($filterForm);
        } else {
            $this->userService->hideDeactivatedEntries();
            $this->userService->showUserStatus(8);
        }

        // get filtered and total number of results
        $itemsCountArray = $this->userService->getCountArray($roomId, $currentUser->isModerator());
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
     * @param Request $request
     * @return array
     */
    public function sendMailAction(
        Request $request
    ) {
        $userItems = array();
        $userIds = $request->query->get('userIds');
        foreach ($userIds as $userId) {
            $userItems[] = $this->userService->getUser($userId);
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
     * @param Request $request
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param LegacyEnvironment $legacyEnvironment
     * @param string $sort
     * @param int $roomId
     * @return Response
     */
    public function printlistAction(
        Request $request,
        PrintService $printService,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        string $sort,
        int $roomId
    ) {
        $environment = $legacyEnvironment->getEnvironment();

        $roomManager = $environment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        // setup filter form
        $filterForm = $this->createFilterForm($legacyEnvironment, $roomItem);
        $numAllUsers = $this->userService->getCountArray($roomId)['countAll'];

        $this->userService->resetLimits();
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in user manager
            $this->userService->setFilterConditions($filterForm);
        }

        $users = $this->userService->getListUsers($roomId);

        // get user list from manager service
        if ($sort != "none") {
            $users = $this->userService->getListUsers($roomId, $numAllUsers, 0, $sort);
        }
        elseif ($this->get('session')->get('sortUsers')) {
            $users = $this->userService->getListUsers($roomId, $numAllUsers, 0, $this->get('session')->get('sortUsers'));
        }
        else {
            $users = $this->userService->getListUsers($roomId, $numAllUsers, 0, 'date');
        }
        $readerList = array();
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get user list from manager service 
        $itemsCountArray = $this->userService->getCountArray($roomId);


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
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $legacyEnvironment
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $roomId
     * @return array|RedirectResponse
     * @throws Exception
     */
    public function changeStatusAction(
        Request $request,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ) {
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
        $users = [];
        if (isset($formData['userIds'])) {
            foreach ($formData['userIds'] as $userId) {
                $user = $this->userService->getUser($userId);
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

                    foreach ($users as $user) {
                        $this->userService->updateAllGroupStatus($user, $roomId);
                    }

                    $environment = $legacyEnvironment->getEnvironment();
                    $readerManager = $environment->getReaderManager();
                    $noticedManager = $environment->getNoticedManager();
                    foreach ($users as $user) {
                        $itemId = $user->getItemID();
                        $versionId = $user->getVersionID();
                        $readerManager->markRead($itemId, $versionId);
                        $noticedManager->markNoticed($itemId, $versionId);

                        if ($user->isDeleted()) {
                            $event = new UserLeftRoomEvent($user, $room);
                        } else {
                            $event = new UserStatusChangedEvent($user);
                        }
                        $eventDispatcher->dispatch($event);
                    }

                    if ($formData['inform_user']) {
                        $this->sendUserInfoMail($formData['userIds'], $formData['status'], $legacyEnvironment);
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

    private function contextHasModerators(
        int $roomId,
        $selectedIds
    ) {
        $moderators = $this->userService->getModeratorsForContext($roomId);
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
     * @param Request $request
     * @param ItemService $itemService
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        ItemService $itemService,
        ReaderService $readerService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $environment = $legacyEnvironment->getEnvironment();
        $infoArray = $this->getDetailInfo($itemService, $readerService, $legacyEnvironment, $roomId, $itemId);

        $alert = null;
        if ($infoArray['user']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $isSelf = false;
        if ($environment->getCurrentUserItem()->getItemId() == $itemId) {
            $isSelf = true;
        }

        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        $roomItem = $this->roomService->getRoomItem($roomId);
        $moderatorListLength = $roomItem->getModeratorList()->getCount();

        $moderatorIds = [];
        $userRoomItem = null;
        if ($roomItem->isProjectRoom() &&
            $roomItem->getShouldCreateUserRooms() &&
            !is_null($infoArray['user']->getLinkedUserroomItem()) &&
            $this->isGranted('ITEM_ENTER', $infoArray['user']->getLinkedUserroomItemId())) {
            $userRoomItem = $infoArray['user']->getLinkedUserroomItem();
            $moderators = $infoArray['user']->getLinkedUserroomItem()->getModeratorList();
            foreach ($moderators as $moderator) {
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
            'userRoomItemMemberCount' => $userRoomItem == null ? [] : $userRoomItem->getUserList()->getCount(),
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


    private function getDetailInfo(
        ItemService $itemService,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        $roomId,
        $itemId
    ) {
        $infoArray = array();
        $user = $this->userService->getUser($itemId);

        $environment = $legacyEnvironment->getEnvironment();
        $item = $user;
        $reader_manager = $environment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $environment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $current_context = $environment->getCurrentContextItem();
        $readerManager = $environment->getReaderManager();

        $userManager = $environment->getUserManager();
        $userManager->setContextLimit($environment->getCurrentContextID());
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
        $readerList = array();
        $modifierList = array();
        $reader = $readerService->getLatestReader($user->getItemId());
        if ( empty($reader) ) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $user->getModificationDate() ) {
            $readerList[$user->getItemId()] = 'changed';
        }

        $modifierList[$user->getItemId()] = $itemService->getAdditionalEditorsForItem($user);

        $users = $this->userService->getListUsers($roomId);
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
        $infoArray['currentUser'] = $environment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['linkedGroups'] = $this->userService->getUser($itemId)->getGroupList()->to_array();;
        $infoArray['comment'] = $user->getUserComment();
        $infoArray['status'] = $user->getStatus();

        return $infoArray;
    }


    /**
     * @Route("/room/{roomId}/user/create")
     * @param TranslatorInterface $translator
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'user')")
     */
    public function createAction(
        TranslatorInterface $translator,
        int $roomId
    ) {
        // create new user item
        $userItem = $this->userService->getNewuser();
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
     * @param Request $request
     * @param ItemService $itemService
     * @param UserTransformer $transformer
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        ItemService $itemService,
        UserTransformer $transformer,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $environment = $legacyEnvironment->getEnvironment();
        $current_context = $environment->getCurrentContextItem();
        $userItem = $this->userService->getuser($itemId);
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
                $userItem->setModificatorItem($environment->getCurrentUserItem());

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
     * @param ItemService $itemService
     * @param ReaderService $readerService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        ItemService $itemService,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $user = $this->userService->getUser($itemId);

        $itemArray = array($user);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        $environment = $legacyEnvironment->getEnvironment();
        $readerManager = $environment->getReaderManager();

        $userManager = $environment->getUserManager();
        $userManager->setContextLimit($environment->getCurrentContextID());
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
     * @param Request $request
     * @param ItemService $itemService
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $legacyEnvironment
     * @param MailAssistant $mailAssistant
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function sendAction(
        Request $request,
        ItemService $itemService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();

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

                $portalItem = $environment->getCurrentPortalItem();

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
     * @param ItemService $itemService
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function sendSuccessAction(
        ItemService $itemService,
        int $roomId,
        int $itemId
    ) {
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
    public function sendSuccessContactAction($roomId, $itemId, $originPath, ItemService $itemService)
    {
        // get item
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'link' => $this->generateUrl($originPath, [
                'roomId' => $roomId,
                'itemId' => $itemId,
                'portalId' => $roomId,
            ]),
            'title' => $item->getFullname(),
        ];
    }

    /**
     * @Route("/room/user/guestimage")
     * @param AvatarService $avatarService
     * @return Response
     */
    public function guestimageAction(
        AvatarService $avatarService
    ) {
        $response = new Response($avatarService->getUnknownUserImage(), Response::HTTP_OK, array('content-type' => 'image'));
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, \Nette\Utils\Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/initials")
     * @param AvatarService $avatarService
     * @param int $itemId
     * @return Response
     */
    public function initialsAction(
        AvatarService $avatarService,
        int $itemId
    ) {
        $response = new Response($avatarService->getAvatar($itemId), Response::HTTP_OK, array('content-type' => 'image'));
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, \Nette\Utils\Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);
        return $response;
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/image")
     * @param AvatarService $avatarService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function imageAction(
        AvatarService $avatarService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $user = $this->userService->getUser($itemId);
        $file = $user->getPicture();
        $foundUserImage = true;

        if ($file != '') {
            $rootDir = $this->get('kernel')->getRootDir().'/';

            $environment = $legacyEnvironment->getEnvironment();
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
     * @param int $roomId
     * @return array
     */
    public function roomsAction(
        int $roomId
    ) {
        $user = $this->userService->getCurrentUserItem();

        // Room list feed
        $rooms = $this->userService->getRoomList($user);

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
     * @param LegacyEnvironment $legacyEnvironment
     * @param SessionInterface $session
     * @return array
     */
    public function globalNavbarAction(
        $contextId,
        LegacyEnvironment $legacyEnvironment,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        bool $uikit3 = false
    ) {
        $currentUserItem = $this->userService->getCurrentUserItem();
        $privateRoomItem = $currentUserItem->getOwnRoom();
        $environment = $legacyEnvironment->getEnvironment();

        $portalItem = $entityManager->getRepository(Portal::class)->find($contextId);
        if (!$portalItem) {
            $portalItem = $environment->getCurrentPortalItem();
        }

        $currentClipboardIds = $session->get('clipboard_ids', []);
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
        $userManager = $environment->getUserManager();
        $userManager->resetLimits();

        return [
            'privateRoomItem' => $privateRoomItem,
            'count' => sizeof($currentClipboardIds),
            'roomId' => $environment->getCurrentContextId(),
            'supportLink' => $portalItem ? $portalItem->getSupportPageLink() : '',
            'tooltip' => $portalItem ? $portalItem->getSupportPageLinkTooltip() : '',
            'showPortalConfigurationLink' => $showPortalConfigurationLink,
            'portal' => $portalItem,
            'uikit3' => $uikit3,
        ];
    }

    /**
     * Displays the all room link in top navbar.
     * This is an embedded controller action.
     *
     * @Template()
     * @param LegacyEnvironment $legacyEnvironment
     * @return array
     */
    public function allRoomsNavbarAction(
        LegacyEnvironment $legacyEnvironment
    ) {
        $currentUserItem = $this->userService->getCurrentUserItem();

        $privateRoomItem = $currentUserItem->getOwnRoom();

        if ($privateRoomItem) {
            $itemId = $privateRoomItem->getItemId();
        } else {
            $environment = $legacyEnvironment->getEnvironment();
            $itemId = $environment->getCurrentContextId();
        }

        return [
            'itemId' => $itemId,
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/print")
     * @param ItemService $itemService
     * @param PrintService $printService
     * @param ReaderService $readerService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        ItemService $itemService,
        PrintService $printService,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {

        $infoArray = $this->getDetailInfo($itemService, $readerService, $legacyEnvironment, $roomId, $itemId);

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

    private function sendUserInfoMail($userIds, $action, LegacyEnvironment $legacyEnvironment)
    {
        $accountMail = $this->get('commsy.utils.mail_account');
        $mailer = $this->get('mailer');

        $fromAddress = $this->getParameter('commsy.email.from');
        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();
        $fromSender = $environment->getCurrentContextItem()->getContextItem()->getTitle();

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
            $user = $this->userService->getUser($userId);

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

    private function gatherUsers(
        $roomId,
        $view,
        Request $request,
        ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment,
        $max = 10,
        $start = 0,
        $sort = 'name')
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $userFilter = $request->get('userFilter');
        if (!$userFilter) {
            $userFilter = $request->query->get('user_filter');
        }

        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();

        $roomManager = $environment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the user manager service
        $this->userService->resetLimits();

        if ($userFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($legacyEnvironment, $roomItem, $view);

            // manually bind values from the request
            $filterForm->submit($userFilter);

            // set filter conditions in user manager
            $this->userService->setFilterConditions($filterForm);
        } else {
            $this->userService->hideDeactivatedEntries();
            $this->userService->showUserStatus(8);
        }

        // get user list from manager service
        $users = $this->userService->getListUsers($roomId, $max, $start, $currentUser->isModerator(), $sort);

        $this->get('session')->set('sortUsers', $sort);

        $readerList = [];
        $allowedActions = [];
        $linkedUserRooms = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($currentUser->isModerator()) {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail', 'insertuserroom', 'copy', 'save', 'user-delete', 'user-block', 'user-confirm', 'user-status-reading-user', 'user-status-user', 'user-status-moderator', 'user-contact', 'user-contact-remove'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail', 'insertuserroom'];
            }
            if ($roomItem->isProjectRoom() &&
                $roomItem->getShouldCreateUserRooms() &&
                !is_null($item->getLinkedUserroomItem()) &&
                $this->isGranted('ITEM_ENTER', $item->getLinkedUserroomItemID())) {
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
     * @Route("/room/{roomId}/user/insertUserroom")
     * @Template()
     */
    public function insertUserroomAction(
        $roomId,
        InsertUserroomAction $action,
        Request $request
    ) {
        $room = $this->getRoom($roomId);
        $users = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $users);
    }

    /**
     * @Route("/room/{roomId}/user/sendMultiple")
     * @Template()
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @return array|RedirectResponse
     * @throws Exception
     */
    public function sendMultipleAction(
        Request $request,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        int $roomId
    ) {
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

        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();

        // include a footer message in the email body (which may be esp. useful if some emails are sent via BCC mail)
        $userCount = count($userIds);
        $defaultBodyMessage = '';
        if ($userCount) {
            $defaultBodyMessage .= '<br/><br/><br/>' . '--' . '<br/>';
            if ($userCount == 1) {
                $user = $this->userService->getUser(reset($userIds));
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
                $user = $this->userService->getUser($userId);
                if ($user) {
                    $users[] = $user;
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $environment->getCurrentPortalItem();

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
     * @param int $roomId
     * @return array
     */
    public function sendMultipleSuccessAction(
        int $roomId
    ) {
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
     * @param LegacyEnvironment $legacyEnvironment
     * @param cs_room_item $room
     * @param string $view
     * @return FormInterface
     */
    private function createFilterForm(
        LegacyEnvironment $legacyEnvironment,
        $room,
        $view = null
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'activated' => true,
            'user_status' => 8,
        ];

        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUserItem();

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
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_user_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if ($selectAll) {
            if ($request->query->has('user_filter')) {
                $currentFilter = $request->query->get('user_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->userService->setFilterConditions($filterForm);
            } else {
                $this->userService->hideDeactivatedEntries();
                $this->userService->showUserStatus(8);
            }

            return $this->userService->getListUsers($roomItem->getItemID());
        } else {
            return $this->userService->getUsersById($roomItem->getItemID(), $itemIds);
        }
    }
}
