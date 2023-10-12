<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Action\Copy\InsertUserroomAction;
use App\Action\MarkRead\MarkReadAction;
use App\Entity\Portal;
use App\Event\UserLeftRoomEvent;
use App\Event\UserStatusChangedEvent;
use App\Filter\UserFilterType;
use App\Form\Type\Profile\AccountContactFormType;
use App\Form\Type\SendType;
use App\Form\Type\UserSendType;
use App\Form\Type\UserStatusChangeType;
use App\Mail\Helper\ContactFormHelper;
use App\Mail\Mailer;
use App\Repository\UserRepository;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\AvatarService;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AccountMail;
use App\Utils\MailAssistant;
use App\Utils\TopicService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController.
 */
class UserController extends BaseController
{
    private UserService $userService;

    #[Required]
    public function setUserService(UserService $userService): void
    {
        $this->userService = $userService;
    }

    #[Route(path: '/room/{roomId}/user/feed/{start}/{sort}')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('RUBRIC_USER')]
    public function feed(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        return $this->render('user/feed.html.twig',
            $this->gatherUsers($roomId, 'feedView', $request, $max, $start, $sort)
        );
    }

    #[Route(path: '/room/{roomId}/user/grid/{start}/{sort}')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('RUBRIC_USER')]
    public function grid(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        return $this->render('user/grid.html.twig',
            $this->gatherUsers($roomId, 'gridView', $request, $max, $start, $sort)
        );
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/contactForm/{originPath}/{moderatorIds}')]
    public function sendMailViaContactForm(
        Request $request,
        MailAssistant $mailAssistant,
        ContactFormHelper $contactFormHelper,
        Mailer $mailer,
        TranslatorInterface $translator,
        $roomId,
        $itemId,
        $originPath,
        $moderatorIds = null
    ): Response {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

        $item = $this->userService->getUser($itemId);
        $formData = null;
        if (!is_null($item->getLinkedUserroomItem())) {
            $recipients = [];
            $recipients[$item->getFullName()] = $item->getFullName();
            foreach ($item->getLinkedUserroomItem()->getModeratorList() as $moderator) {
                $recipients[$moderator->getFullName()] = $moderator->getFullName();
            }
            $message = $translator->trans('This email has been sent by ... from userroom ...', [
                '%sender_name%' => $this->legacyEnvironment->getCurrentUserItem()->getFullName(),
                '%room_name%' => $item->getLinkedUserroomItem()->getTitle(),
                '%recipients%' => implode(', ', $recipients),
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
                    // Specifing the portalId is just a workaround. This action is linked from the portal with the
                    // portal id as roomId parameter. If the user cancels the form the parameter is needed to construct
                    // the URL for app_portalsettings_accountindex
                    // Instead we should consider to refactor the behavior to use a complete URL and not just the
                    // route name
                    'portalId' => $roomId,
                ]);
            }

            $formData = $form->getData();

            $recipients = [$item];
            $moderators = explode(', ', (string) $moderatorIds);
            foreach ($moderators as $moderatorId) {
                $recipients[] = $this->userService->getUser($moderatorId);
            }

            // send mail
            $recipientCount = $contactFormHelper->handleContactFormSending(
                $formData['subject'],
                $formData['message'] ?: '',
                $portalItem->getTitle(),
                $this->legacyEnvironment->getCurrentUserItem(),
                $formData['files'],
                $recipients,
                $formData['additional_recipient'],
                $formData['copy_to_sender']
            );

            $this->addFlash('recipientCount', $recipientCount);

            // redirect to success page
            return $this->redirectToRoute('app_user_sendsuccesscontact', [
                'roomId' => $roomId,
                'itemId' => $item->getItemId(),
                'originPath' => $originPath,
            ]);
        }

        return $this->render('user/send_mail_via_contact_form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{view}', defaults: ['view' => 'feedView'], requirements: ['view' => 'feedView|gridView'])]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('RUBRIC_USER')]
    public function list(
        Request $request,
        UserRepository $userRepository,
        int $roomId,
        string $view
    ): Response {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $sort = $request->getSession()->get('sortUsers', 'name');

        // setup filter form
        $userStatus = $this->resolveUserStatus('user');
        $filterForm = $this->createFilterForm($roomItem, $view, $userStatus);

        // reset manager
        $this->userService->resetLimits();

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in user manager
            $this->userService->setFilterConditions($filterForm);

            // get filtered and total number of results
            $itemsCountArray = $this->userService->getCountArray($roomId, $currentUser->isModerator());
        } else {
            $this->userService->hideDeactivatedEntries();
            $this->userService->showUserStatus($userStatus);

            // no filters should be active - get total number of users
            $itemsCountArray = $this->userService->getCountArray($roomId, $currentUser->isModerator());
        }

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('user')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('user');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('user');
        }

        // number of users which are waiting for confirmation
        $userTasks = $userRepository->getConfirmableUserByContextId($roomId)->getQuery()->getResult();

        return $this->render('user/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
            'usageInfo' => $usageInfo,
            'view' => $view,
            'isArchived' => $roomItem->getArchived(),
            'userTasks' => $userTasks,
            'isModerator' => $currentUser->isModerator(),
            'user' => $currentUser,
            'sort' => $sort,
            'shouldCreateUserRooms' => $roomItem->isProjectRoom() ? $roomItem->getShouldCreateUserRooms() : false,
        ]);
    }

    private function resolveUserStatus(string $userStatus): string
    {
        return match ($userStatus) {
            'is blocked' => '0',
            'is applying' => '1',
            'user' => '8',
            'moderator' => '3',
            'is contact' => 'is contact',
            'reading user' => '4',
            default => '8',
        };
    }

    #[Route(path: '/room/{roomId}/user/sendmail')]
    public function sendMail(
        Request $request
    ): Response {
        $userItems = [];
        $userIds = $request->query->all('userIds');
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

        return $this->render('user/send_mail.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/print/{sort}', defaults: ['sort' => 'none'])]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('RUBRIC_USER')]
    public function printlist(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ): Response {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        // setup filter form
        $filterForm = $this->createFilterForm($roomItem);
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
        if ('none' === $sort || empty($sort)) {
            $sort = $request->getSession()->get('sortUsers', 'name');
        }
        $users = $this->userService->getListUsers($roomId, $numAllUsers, 0, $sort);

        $readerList = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
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
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/user/changeStatus')]
    #[IsGranted('MODERATOR')]
    public function changeStatus(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        Mailer $mailer,
        AccountMail $accountMail,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);

        $formData = [];

        if ($request->query->has('userDetail')) {
            $formData['status'] = $request->query->get('status');
            $formData['userIds'] = $request->query->all('userIds');
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
                $formData['status'] = $postData['status'];
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
                                $user->reject(); // status 0
                                $user->save();
                                $this->userService->propagateStatusToGrouproomUsersForUser($user);
                            }
                            break;

                        case 'user-confirm':
                            foreach ($users as $user) {
                                $previousStatus = $user->getStatus();
                                $user->makeUser(); // status 2
                                $user->save();
                                if (0 == $previousStatus) {
                                    $this->userService->propagateStatusToGrouproomUsersForUser($user);
                                }
                            }
                            break;

                        case 'user-status-reading-user':
                            foreach ($users as $user) {
                                $previousStatus = $user->getStatus();
                                $user->makeReadOnlyUser(); // status 4
                                $user->save();
                                if (0 == $previousStatus) {
                                    $this->userService->propagateStatusToGrouproomUsersForUser($user);
                                }
                            }
                            break;

                        case 'user-status-user':
                            foreach ($users as $user) {
                                $previousStatus = $user->getStatus();
                                $user->makeUser(); // status 2
                                $user->save();
                                if (0 == $previousStatus) {
                                    $this->userService->propagateStatusToGrouproomUsersForUser($user);
                                }
                            }
                            break;

                        case 'user-status-moderator':
                            foreach ($users as $user) {
                                $previousStatus = $user->getStatus();
                                $user->makeModerator(); // status 3
                                $user->save();
                                if (0 == $previousStatus) {
                                    $this->userService->propagateStatusToGrouproomUsersForUser($user);
                                }
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

                    $readerManager = $this->legacyEnvironment->getReaderManager();
                    $noticedManager = $this->legacyEnvironment->getNoticedManager();
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
                        $this->userService->sendUserInfoMail($mailer, $accountMail, $formData['userIds'], $formData['status']);
                    }
                    if ($request->query->has('userDetail') && 'user-delete' !== $formData['status']) {
                        return $this->redirectToRoute('app_user_detail', [
                            'roomId' => $roomId,
                            'itemId' => array_values($request->query->all('userIds'))[0],
                        ]);
                    } else {
                        return $this->redirectToRoute('app_user_list', [
                            'roomId' => $roomId,
                        ]);
                    }
                }
            } elseif ($form->get('cancel')->isClicked()) {
                if ($request->query->has('userDetail')) {
                    return $this->redirectToRoute('app_user_detail', [
                        'roomId' => $roomId,
                        'itemId' => array_values($request->query->all('userIds'))[0],
                    ]);
                } else {
                    return $this->redirectToRoute('app_user_list', [
                        'roomId' => $roomId,
                    ]);
                }
            }
        }

        return $this->render('user/change_status.html.twig', [
            'users' => $users,
            'form' => $form,
            'status' => $formData['status'],
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    #[IsGranted('RUBRIC_USER')]
    public function detail(
        Request $request,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        TranslatorInterface $translator,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $alert = null;
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $isSelf = $this->legacyEnvironment->getCurrentUserItem()->getItemId() == $itemId;

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $roomItem = $this->roomService->getRoomItem($roomId);
        $moderatorListLength = $roomItem->getModeratorList()->getCount();

        $moderatorIds = [];
        $userRoomItem = null;
        if (
            $roomItem->isProjectRoom() &&
            $roomItem->getShouldCreateUserRooms() &&
            !is_null($infoArray['user']->getLinkedUserroomItem()) &&
            $this->isGranted('ITEM_ENTER', $infoArray['user']->getLinkedUserroomItemId())
        ) {
            $userRoomItem = $infoArray['user']->getLinkedUserroomItem();
            $moderators = $infoArray['user']->getLinkedUserroomItem()->getModeratorList();
            foreach ($moderators as $moderator) {
                $moderatorIds[] = $moderator->getItemId();
            }
        }

        return $this->render('user/detail.html.twig', [
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
            'userRoomItemMemberCount' => null == $userRoomItem ? [] : $userRoomItem->getUserList()->getCount(),
            'userRoomLinksCount' => count(null == $userRoomItem ? [] : $userRoomItem->getAllLinkedItemIDArray()),
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'currentUser' => $infoArray['currentUser'],
            'linkedGroups' => $infoArray['linkedGroups'],
            'userComment' => $infoArray['comment'],
            'status' => $infoArray['status'],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isSelf' => $isSelf,
            'moderatorListLength' => $moderatorListLength
        ]);
    }

    private function getDetailInfo(
        $roomId,
        $itemId
    ) {
        $infoArray = [];
        $user = $this->userService->getUser($itemId);

        $item = $user;
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

        $current_user = $user_list->getFirst();
        $id_array = [];
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $user->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($user->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $user->getModificationDate()) {
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }
        $readerList = [];
        $modifierList = [];
        $reader = $this->readerService->getLatestReader($user->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $user->getModificationDate()) {
            $readerList[$user->getItemId()] = 'changed';
        }

        $modifierList[$user->getItemId()] = $this->itemService->getAdditionalEditorsForItem($user);

        $users = $this->userService->getListUsers($roomId);
        $userList = [];
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
                    ++$counterBefore;
                }
                $userList[] = $tempUser;
                if ($tempUser->getItemID() == $user->getItemID()) {
                    $foundUser = true;
                }
                if (!$foundUser) {
                    $prevItemId = $tempUser->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $userList[] = $tempUser;
                    ++$counterAfter;
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
                $lastItemId = $users[sizeof($users) - 1]->getItemId();
            }
        }

        $groups = [];
        $context_item = $this->legacyEnvironment->getCurrentContextItem();
        $conf = $context_item->getHomeConf();
        if (strpos((string) $conf, 'group_show')) {
            $groups = $this->userService->getUser($itemId)->getGroupList()->to_array();
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
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = false;
        $infoArray['showWorkflow'] = false;
        $infoArray['currentUser'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['linkedGroups'] = $groups;
        $infoArray['comment'] = $user->getUserComment();
        $infoArray['status'] = $user->getStatus();

        return $infoArray;
    }
    #[Route(path: '/room/{roomId}/user/{itemId}/send')]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    #[IsGranted('RUBRIC_USER')]
    public function send(
        Request $request,
        TranslatorInterface $translator,
        MailAssistant $mailAssistant,
        Mailer $mailer,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $defaultBodyMessage = '<br/><br/><br/>--<br/>'.$translator->trans(
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

            if ('save' == $saveType) {
                $formData = $form->getData();

                $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

                // TODO: validate sender & recipient email addresses (similar to sendMultipleAction())
                $sender = new Address($currentUser->getEmail(), $currentUser->getFullName());
                $recipient = new Address($item->getEmail(), $item->getFullName());

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $email = (new Email())
                    ->subject($formData['subject'])
                    ->html($formData['message']);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $email = $mailAssistant->addAttachments($formDataFiles, $email);
                }

                if ($currentUser->isEmailVisible()) {
                    $email->replyTo($sender);
                }

                $recipients = [$recipient];

                // form option: copy_to_sender
                if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                    $recipients[] = $sender;
                }

                // send mail
                foreach ($recipients as $rec) {
                    $email->to($rec);
                    $mailer->sendEmailObject($email, $portalItem->getTitle());
                }

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

        return $this->render('user/send.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/send/success')]
    public function sendSuccess(
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        return $this->render('user/send_success.html.twig', [
            'link' => $this->generateUrl('app_user_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getFullname(),
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/send/success/contact/{originPath}')]
    public function sendSuccessContact($roomId, $itemId, $originPath): Response
    {
        // get item
        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        return $this->render('user/send_success_contact.html.twig', [
            'link' => $this->generateUrl($originPath, [
                'roomId' => $roomId,
                'itemId' => $itemId,
                'portalId' => $roomId,
            ]),
            'title' => $item->getFullname(),
        ]);
    }

    #[Route(path: '/room/user/guestimage')]
    public function guestimage(
        AvatarService $avatarService
    ): Response {
        $response = new Response($avatarService->getUnknownUserImage(), Response::HTTP_OK,
            ['content-type' => 'image']);
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,
            Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/initials')]
    public function initials(
        AvatarService $avatarService,
        int $itemId
    ): Response {
        $response = new Response($avatarService->getAvatar($itemId), Response::HTTP_OK,
            ['content-type' => 'image']);
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,
            Strings::webalize('user_unknown.gif'));
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/image')]
    public function image(
        AvatarService $avatarService,
        DataManager $dataManager,
        FilterManager $filterManager,
        int $roomId,
        int $itemId
    ): Response {
        $content = null;
        $user = $this->userService->getUser($itemId);
        $picture = $user->getPicture();

        $foundUserImage = false;
        $file = 'user_unknown.gif';
        if ('' != $picture) {
            $disc_manager = $this->legacyEnvironment->getDiscManager();
            $portalId = $this->legacyEnvironment->getCurrentPortalID();
            $filePath = $disc_manager->getAbsoluteFilePath($portalId, $roomId, $picture);
            $relativePath = Path::makeRelative($filePath, getcwd());

            if (file_exists($relativePath)) {
                $processedImage = $dataManager->find('commsy_user_image', $relativePath);
                $content = $filterManager->applyFilter($processedImage,
                    'commsy_user_image')->getContent();

                if ($content) {
                    $foundUserImage = true;
                    $file = $picture;
                }
            }
        }

        if (!$foundUserImage) {
            $content = $avatarService->getAvatar($itemId);
        }
        $response = new Response($content, Response::HTTP_OK, ['content-type' => 'image']);
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,
            Strings::webalize($file));
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }

    /**
     * Displays the global user actions in top navbar.
     * This is an embedded controller action.
     */
    public function globalNavbar(
        $contextId,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        bool $uikit3 = false
    ): Response {
        $currentUserItem = $this->userService->getCurrentUserItem();
        $privateRoomItem = $currentUserItem->getOwnRoom();

        $portalItem = $entityManager->getRepository(Portal::class)->find($contextId);
        if (!$portalItem) {
            $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
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
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();

        return $this->render('user/global_navbar.html.twig', [
            'privateRoomItem' => $privateRoomItem,
            'count' => sizeof($currentClipboardIds),
            'roomId' => $this->legacyEnvironment->getCurrentContextId(),
            'supportLink' => $portalItem ? $portalItem->getSupportPageLink() : '',
            'tooltip' => $portalItem ? $portalItem->getSupportPageLinkTooltip() : '',
            'showPortalConfigurationLink' => $showPortalConfigurationLink,
            'portal' => $portalItem,
            'uikit3' => $uikit3,
        ]);
    }

    /**
     * Displays the all room link in top navbar.
     * This is an embedded controller action.
     */
    public function allRoomsNavbar(
        LegacyEnvironment $legacyEnvironment,
        bool $uikit3 = false
    ): Response {
        $currentUserItem = $this->userService->getCurrentUserItem();

        $privateRoomItem = $currentUserItem->getOwnRoom();

        if ($privateRoomItem) {
            $itemId = $privateRoomItem->getItemId();
        } else {
            $itemId = $this->legacyEnvironment->getCurrentContextId();
        }

        return $this->render('user/all_rooms_navbar.html.twig', [
            'itemId' => $itemId,
            'uikit3' => $uikit3,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/print')]
    public function print(
        PrintService $printService,
        int $roomId,
        int $itemId
    ): Response {
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

    private function gatherUsers(
        $roomId,
        $view,
        Request $request,
        $max = 10,
        $start = 0,
        $sort = ''
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $userFilter = $request->get('userFilter');
        if (!$userFilter) {
            $userFilter = $request->query->all('user_filter');
        }

        // $this->userManager->get()->to_array()

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the user manager service
        $this->userService->resetLimits();

        if ($userFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($roomItem, $view);

            // manually bind values from the request
            $filterForm->submit($userFilter);

            // set filter conditions in user manager
            $this->userService->setFilterConditions($filterForm);
        } else {
            $this->userService->hideDeactivatedEntries();
            $this->userService->showUserStatus(8);
        }

        if (empty($sort)) {
            $sort = $request->getSession()->get('sortUsers', 'name');
        }
        $request->getSession()->set('sortUsers', $sort);

        // get user list from manager service
        $users = $this->userService->getListUsers($roomId, $max, $start, $currentUser->isModerator(), $sort, false);

        $readerList = [];
        $allowedActions = [];
        $linkedUserRooms = [];
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($currentUser->isModerator()) {
                $allowedActions[$item->getItemID()] = [
                    'markread',
                    'sendmail',
                    'insertuserroom',
                    'copy',
                    'save',
                    'user-delete',
                    'user-block',
                    'user-confirm',
                    'user-status-reading-user',
                    'user-status-user',
                    'user-status-moderator',
                    'user-contact',
                    'user-contact-remove',
                ];
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

    #[Route(path: '/room/{roomId}/user/insertUserroom')]
    public function insertUserroom(
        $roomId,
        InsertUserroomAction $action,
        Request $request
    ): Response {
        $room = $this->getRoom($roomId);
        $users = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $users);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/user/sendMultiple')]
    public function sendMultiple(
        Request $request,
        TranslatorInterface $translator,
        MailAssistant $mailAssistant,
        Mailer $mailer,
        int $roomId
    ): Response {
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

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        // include a footer message in the email body (which may be esp. useful if some emails are sent via BCC mail)
        $userCount = is_countable($userIds) ? count($userIds) : 0;
        $defaultBodyMessage = '';
        if ($userCount) {
            $defaultBodyMessage .= '<br/><br/><br/>--<br/>';
            if (1 == $userCount) {
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
                    [
                        '%sender_name%' => $currentUser->getFullName(),
                        '%user_count%' => is_countable($userIds) ? count($userIds) : 0,
                        '%room_name%' => $room->getTitle(),
                    ],
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

            if ('save' == $saveType) {
                $formData = $form->getData();

                $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $recipients = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
                    if ($validator->isValid($user->getEmail(), new RFCValidation())) {
                        $recipients[] = new Address($user->getEmail(), $user->getFullName());
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                if ($validator->isValid($currentUser->getEmail(), new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[] = new Address($currentUser->getEmail(), $currentUser->getFullName());
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        $recipients[] = new Address($currentUser->getEmail(), $currentUser->getFullName());
                    }
                }

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $email = (new Email())
                    ->subject($formData['subject'])
                    ->html($formData['message'])
                    ->replyTo(...$replyTo);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $email = $mailAssistant->addAttachments($formDataFiles, $email);
                }

                $mailSend = true;
                foreach ($recipients as $recipient) {
                    $email->to($recipient);
                    $send = $mailer->sendEmailObject($email, $portalItem->getTitle());
                    $mailSend = $mailSend && $send;
                }

                $this->addFlash('recipientCount', $recipients);

                // send mail
                $mailSend = $mailSend && empty($failedUsers);
                $this->addFlash('mailSend', $mailSend);

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

        return $this->render('user/send_multiple.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/sendMultiple/success')]
    public function sendMultipleSuccess(
        int $roomId
    ): Response {
        return $this->render('user/send_multiple_success.html.twig', [
            'link' => $this->generateUrl('app_user_list', [
                'roomId' => $roomId,
            ]),
        ]);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/user/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkRead(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @param cs_room_item $room
     * @param string        $view
     *
     * @return FormInterface
     */
    private function createFilterForm(
        $room,
        $view = null,
        $user_status = 8
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'activated' => true,
            'user_status' => $user_status,
        ];

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

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
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
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
                $currentFilter = $request->query->all('user_filter');
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
