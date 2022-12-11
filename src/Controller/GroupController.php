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

use App\Action\Activate\ActivateAction;
use App\Action\Activate\DeactivateAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\MarkRead\MarkReadAction;
use App\Entity\Account;
use App\Event\CommsyEditEvent;
use App\Facade\MembershipManager;
use App\Filter\GroupFilterType;
use App\Form\DataTransformer\GroupTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\GroupSendType;
use App\Form\Type\GroupType;
use App\Http\JsonDataResponse;
use App\Mail\Mailer;
use App\Room\Copy\LegacyCopy;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\GroupService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\TopicService;
use App\Utils\UserService;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class GroupController.
 */
#[Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'group')")]
class GroupController extends BaseController
{
    private GroupService $groupService;

    private SessionInterface $session;

    private UserService $userService;

    private Mailer $mailer;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setGroupService(GroupService $groupService): void
    {
        $this->groupService = $groupService;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setUserService(UserService $userService): void
    {
        $this->userService = $userService;
    }

    #[Route(path: '/room/{roomId}/group')]
    public function listAction(
        Request $request,
        int $roomId
    ): Response {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        $sort = $this->session->get('sortGroups', 'date');

        // get group list from manager service
        $itemsCountArray = $this->groupService->getCountArray($roomId);

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('group')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('group');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('group');
        }

        return $this->render('group/list.html.twig', ['roomId' => $roomId, 'form' => $filterForm->createView(), 'module' => 'group', 'itemsCountArray' => $itemsCountArray, 'showRating' => false, 'showHashTags' => $roomItem->withBuzzwords(), 'showCategories' => $roomItem->withTags(), 'showAssociations' => false, 'usageInfo' => $usageInfo, 'isArchived' => $roomItem->getArchived(), 'user' => $this->legacyEnvironment->getCurrentUserItem(), 'sort' => $sort]);
    }

    #[Route(path: '/room/{roomId}/group/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlistAction(
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
        $filterForm = $this->createFilterForm($roomItem);
        $numAllGroups = $this->groupService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        // get group list from manager service
        if ('none' === $sort || empty($sort)) {
            $sort = $this->session->get('sortGroups', 'date');
        }
        $groups = $this->groupService->getListGroups($roomId, $numAllGroups, 0, $sort);

        $readerList = [];
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        // get group list from manager service
        $itemsCountArray = $this->groupService->getCountArray($roomId);

        $html = $this->renderView('group/list_print.html.twig', [
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'module' => 'group',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $printService->buildPdfResponse($html);
    }

    #[Route(path: '/room/{roomId}/group/feed/{start}/{sort}')]
    public function feedAction(
        Request $request,
        UserService $userService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $groupFilter = $request->get('groupFilter');
        if (!$groupFilter) {
            $groupFilter = $request->query->get('group_filter');
        }

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($groupFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($groupFilter);

            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        if (empty($sort)) {
            $sort = $this->session->get('sortGroups', 'date');
        }
        $this->session->set('sortGroups', $sort);

        // get group list from manager service
        $groups = $this->groupService->getListGroups($roomId, $max, $start, $sort);

        // contains member status of current user for each group and grouproom
        $allGroupsMemberStatus = [];

        $readerList = [];
        $allowedActions = [];
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = ['markread', 'categorize', 'hashtag', 'activate', 'deactivate', 'sendmail', 'delete'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'sendmail'];
            }

            // add groupMember and groupRoomMember status to each group!
            $groupMemberStatus = [];

            // group member status
            $membersList = $item->getMemberItemList();
            $members = $membersList->to_array();
            $groupMemberStatus['groupMember'] = $membersList->inList($this->legacyEnvironment->getCurrentUserItem());

            // grouproom member status
            if ($item->isGroupRoomActivated()) {
                if ($item->getGroupRoomItem()) {
                    $groupMemberStatus['groupRoomMember'] = $this->userService->getMemberStatus(
                        $item->getGroupRoomItem(),
                        $this->legacyEnvironment->getCurrentUser()
                    );
                } else {
                    $groupMemberStatus['groupRoomMember'] = 'deactivated';
                }
            } else {
                $groupMemberStatus['groupRoomMember'] = 'deactivated';
            }
            $allGroupsMemberStatus[$item->getItemID()] = $groupMemberStatus;
        }

        return $this->render('group/feed.html.twig', ['roomId' => $roomId, 'groups' => $groups, 'readerList' => $readerList, 'showRating' => false, 'allowedActions' => $allowedActions, 'memberStatus' => $allGroupsMemberStatus, 'isRoot' => $this->legacyEnvironment->getCurrentUser()->isRoot()]);
    }

    /**
     * @return array
     */
    #[Route(path: '/room/{roomId}/group/{itemId}', requirements: ['itemId' => '\d+'])]
    public function detail(
        Request $request,
        AnnotationService $annotationService,
        CategoryService $categoryService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($annotationService, $categoryService, $roomId, $itemId);

        $memberStatus = '';

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($infoArray['group']->isGroupRoomActivated()) {
            $groupRoomItem = $infoArray['group']->getGroupRoomItem();
            if ($groupRoomItem && !empty($groupRoomItem)) {
                $memberStatus = $this->userService->getMemberStatus(
                    $groupRoomItem,
                    $this->legacyEnvironment->getCurrentUser()
                );
            } else {
                $memberStatus = 'deactivated';
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['group']->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $currentUserIsLastGrouproomModerator = $this->userService->userIsLastModeratorForRoom($infoArray['group']->getGroupRoomItem());

        return ['roomId' => $roomId, 'group' => $infoArray['group'], 'readerList' => $infoArray['readerList'], 'modifierList' => $infoArray['modifierList'], 'groupList' => $infoArray['groupList'], 'counterPosition' => $infoArray['counterPosition'], 'count' => $infoArray['count'], 'firstItemId' => $infoArray['firstItemId'], 'prevItemId' => $infoArray['prevItemId'], 'nextItemId' => $infoArray['nextItemId'], 'lastItemId' => $infoArray['lastItemId'], 'readCount' => $infoArray['readCount'], 'readSinceModificationCount' => $infoArray['readSinceModificationCount'], 'userCount' => $infoArray['userCount'], 'draft' => $infoArray['draft'], 'showRating' => $infoArray['showRating'], 'showWorkflow' => $infoArray['showWorkflow'], 'showHashtags' => $infoArray['showHashtags'], 'showAssociations' => $infoArray['showAssociations'], 'showCategories' => $infoArray['showCategories'], 'roomCategories' => $infoArray['roomCategories'], 'buzzExpanded' => $infoArray['buzzExpanded'], 'catzExpanded' => $infoArray['catzExpanded'], 'members' => $infoArray['members'], 'user' => $infoArray['user'], 'userIsMember' => $infoArray['userIsMember'], 'memberStatus' => $memberStatus, 'annotationForm' => $form->createView(), 'alert' => $alert, 'pathTopicItem' => $pathTopicItem, 'isArchived' => $roomItem->getArchived(), 'lastModeratorStanding' => $currentUserIsLastGrouproomModerator, 'userRubricVisible' => in_array('user', $this->roomService->getRubricInformation($roomId))];
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/print')]
    public function printAction(
        AnnotationService $annotationService,
        CategoryService $categoryService,
        PrintService $printService,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($annotationService, $categoryService, $roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('group/detail_print.html.twig', [
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
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    private function getDetailInfo(
        AnnotationService $annotationService,
        CategoryService $categoryService,
        int $roomId,
        int $itemId
    ) {
        $infoArray = [];

        $group = $this->groupService->getGroup($itemId);

        $item = $group;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "reader['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($reader) || $reader['read_date'] <= $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $this->legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "noticed['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($noticed) || $noticed['read_date'] <= $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $readerManager = $this->legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($group->getContextId());
        $numTotalMember = $roomItem->getAllUsers();

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
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = [];
        $modifierList = [];
        $reader = $this->readerService->getLatestReader($group->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $group->getModificationDate()) {
            $readerList[$group->getItemId()] = 'changed';
        }

        $modifierList[$group->getItemId()] = $this->itemService->getAdditionalEditorsForItem($group);

        $groups = $this->groupService->getListGroups($roomId);
        $groupList = [];
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
                    ++$counterBefore;
                }
                $groupList[] = $tempGroup;
                if ($tempGroup->getItemID() == $group->getItemID()) {
                    $foundGroup = true;
                }
                if (!$foundGroup) {
                    $prevItemId = $tempGroup->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $groupList[] = $tempGroup;
                    ++$counterAfter;
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
                $lastItemId = $groups[sizeof($groups) - 1]->getItemId();
            }
        }

        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
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
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['roomCategories'] = $categories;
        $infoArray['members'] = $members;
        $infoArray['userIsMember'] = $membersList->inList($infoArray['user']);

        return $infoArray;
    }

    private function getTagDetailArray(
        $baseCategories,
        $itemCategories
    ) {
        $result = [];
        $tempResult = [];
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $tempArray = [];
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                    } else {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']];
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                }
            }
            $tempResult = [];
            $addCategory = false;
        }

        return $result;
    }

    /**
     * @return RedirectResponse
     */
    #[Route(path: '/room/{roomId}/group/create')]
    #[Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'group')")]
    public function createAction(
        int $roomId
    ): Response {
        // create new group item
        $groupItem = $this->groupService->getNewGroup();
        $groupItem->setDraftStatus(1);
        $groupItem->setPrivateEditing(1);
        $groupItem->save();

        return $this->redirectToRoute('app_group_detail',
            ['roomId' => $roomId, 'itemId' => $groupItem->getItemId()]);
    }

    #[Route(path: '/room/{roomId}/group/new')]
    public function newAction(int $roomId): Response
    {
        return $this->render('group/new.html.twig');
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/edit')]
    #[Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")]
    public function editAction(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        GroupTransformer $transformer,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $groupItem = null;

        $isDraft = $item->isDraft();

        // get date from DateService
        $groupItem = $this->groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id '.$itemId);
        }
        $formData = $transformer->transform($groupItem);
        $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
        $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $formData['draft'] = $isDraft;
        $form = $this->createForm(GroupType::class, $formData, ['action' => $this->generateUrl('app_group_edit', ['roomId' => $roomId, 'itemId' => $itemId]), 'placeholderText' => '['.$this->translator->trans('insert title').']', 'categoryMappingOptions' => [
            'categories' => $labelService->getCategories($roomId),
            'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
            'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
        ], 'hashtagMappingOptions' => [
            'hashtags' => $labelService->getHashtags($roomId),
            'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
        ], 'room' => $current_context, 'templates' => $this->getAvailableTemplates()]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // add current user to new group
                $groupItem->addMember($this->legacyEnvironment->getCurrentUser());

                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

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
                        $groupItem->setTagListByID($categoryIds);
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
                        $groupItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $groupItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            }

            return $this->redirectToRoute('app_group_save', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($groupItem), CommsyEditEvent::EDIT);

        return $this->render('group/edit.html.twig', ['form' => $form->createView(), 'group' => $groupItem, 'isDraft' => $isDraft, 'currentUser' => $this->legacyEnvironment->getCurrentUserItem()]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/save')]
    #[Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")]
    public function saveAction(
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $group = $this->groupService->getGroup($itemId);

        $itemArray = [$group];
        $modifierList = [];
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

        $current_user = $user_list->getFirst();
        $id_array = [];
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($group), CommsyEditEvent::SAVE);

        return $this->render('group/save.html.twig', ['roomId' => $roomId, 'item' => $group, 'modifierList' => $modifierList, 'userCount' => $all_user_count, 'readCount' => $read_count, 'readSinceModificationCount' => $read_since_modification_count]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/{itemId}/join')]
    public function join(
        int $roomId,
        int $itemId,
        MembershipManager $membershipManager
    ): JsonDataResponse|RedirectResponse {
        $roomManager = $this->legacyEnvironment->getRoomManager();

        $room = $roomManager->getItem($roomId);
        $group = $this->groupService->getGroup($itemId);

        if (!$room) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if (!$group) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        /** @var Account $account */
        $account = $this->getUser();
        if (!$account) {
            throw $this->createAccessDeniedException();
        }

        // join group
        $membershipManager->joinGroup($group, $account);

        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $groupRoom = $group->getGroupRoomItem();
        if ($groupRoom) {
            $memberStatus = $this->userService->getMemberStatus($groupRoom, $currentUser);
            if ('join' == $memberStatus) {
                return $this->redirectToRoute('app_context_request', [
                    'roomId' => $roomId,
                    'itemId' => $groupRoom->getItemId(),
                ]);
            } else {
                throw new \Exception("ERROR: User '".$currentUser->getUserID()."' cannot join group room '".$groupRoom->getTitle()."' since (s)he has room member status '".$memberStatus."' (requires status 'join' to become a room member)!");
            }
        } else {
            throw new \Exception("ERROR: User '".$currentUser->getUserID()."' cannot join the group room of group '".$group->getName()."' since it does not exist!");
        }
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/leave')]
    public function leave(
        int $roomId,
        int $itemId,
        MembershipManager $membershipManager
    ): JsonDataResponse|RedirectResponse {
        $roomManager = $this->legacyEnvironment->getRoomManager();

        $room = $roomManager->getItem($roomId);
        $group = $this->groupService->getGroup($itemId);
        $groupRoom = $group->getGroupRoomItem();

        if (!$room) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if (!$group) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        /** @var Account $account */
        $account = $this->getUser();
        if (!$account) {
            throw $this->createAccessDeniedException();
        }

        // leave group
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        if ($membershipManager->isLastModerator($groupRoom, $currentUser)) {
            // Redirect delete group
            return $this->redirectToRoute('app_profile_deleteroomprofile', [
                'roomId' => $groupRoom->getItemID(),
                'itemId' => $currentUser->getItemID(),
                'groupId' => $itemId,
                'roomEndId' => $roomId,
            ]);
        }

        // leave group
        $membershipManager->leaveGroup($group, $account);
        $membershipManager->leaveWorkspace($groupRoom, $account);

        return $this->redirectToRoute('app_group_detail', [
            'roomId' => $roomId,
            'itemId' => $itemId,
        ]);
    }

    /**
     * @return array
     */
    #[Route(path: '/room/{roomId}/group/{itemId}/members', requirements: ['itemId' => '\d+'])]
    public function membersAction(
        int $itemId
    ): Response {
        $group = $this->groupService->getGroup($itemId);
        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        return [
            'group' => $group,
            'members' => $members,
        ];
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/sendMultiple')]
    public function sendMultipleAction(
        Request $request,
        MailAssistant $mailAssistant,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);

        $groupIds = [];
        if (!$request->request->has('group_send')) {
            $users = $this->getItemsForActionRequest($room, $request);

            foreach ($users as $user) {
                $groupIds[] = $user->getItemId();
            }
        } else {
            $postData = $request->request->get('group_send');
            $groupIds = $postData['groups'];
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
        $users = $this->userService->getUsersByGroupIds($roomId, $groupIds, true);

        // include a footer message in the email body
        $groupCount = is_countable($groupIds) ? count($groupIds) : 0;
        $defaultBodyMessage = '';
        if ($groupCount) {
            $defaultBodyMessage .= '<br/><br/><br/>--<br/>';
            if (1 == $groupCount) {
                $group = $this->groupService->getGroup(reset($groupIds));
                if ($group) {
                    $defaultBodyMessage .= $this->translator->trans(
                        'This email has been sent to all users of this group',
                        [
                            '%sender_name%' => $currentUser->getFullName(),
                            '%group_name%' => $group->getName(),
                            '%room_name%' => $room->getTitle(),
                        ],
                        'mail'
                    );
                }
            } elseif ($groupCount > 1) {
                $defaultBodyMessage .= $this->translator->trans(
                    'This email has been sent to multiple users of this room',
                    [
                        '%sender_name%' => $currentUser->getFullName(),
                        '%user_count%' => count($users),
                        '%room_name%' => $room->getTitle(),
                    ],
                    'mail'
                );
            }
        }

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
            'groups' => $groupIds,
        ];

        $form = $this->createForm(GroupSendType::class, $formData, [
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

                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        if ($user->isEmailVisible()) {
                            $to[$userEmail] = $userName;
                        } else {
                            $toBCC[$userEmail] = $userName;
                        }
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                $toCC = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[] = new Address($currentUserEmail, $currentUserName);
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        if ($currentUser->isEmailVisible()) {
                            $toCC[$currentUserEmail] = $currentUserName;
                        } else {
                            $toBCC[$currentUserEmail] = $currentUserName;
                        }
                    }
                }

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $message = (new Email())
                    ->subject($formData['subject'])
                    ->html($formData['message'])
                    ->replyTo(...$replyTo);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $message = $mailAssistant->addAttachments($formDataFiles, $message);
                }

                // NOTE: as of #2461 all mail should be sent as BCC mail
                $allRecipients = array_merge($to, $toCC, $toBCC);
                $message->bcc(...$mailAssistant->convertArrayToAddresses($allRecipients));

                $this->addFlash('recipientCount', count($allRecipients));

                // send mail
                $mailSend = $this->mailer->sendEmailObject($message, $portalItem->getTitle());
                $this->addFlash('mailSend', $mailSend);

                // redirect to success page
                return $this->redirectToRoute('app_group_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to group feed
                return $this->redirectToRoute('app_group_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return $this->render('group/sendMultiple.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/room/{roomId}/group/sendMultiple/success')]
    public function sendMultipleSuccessAction(
        int $roomId
    ): Response {
        return $this->render('group/sendMultipleSuccess.html.twig', [
            'link' => $this->generateUrl('app_group_list', [
                'roomId' => $roomId,
            ]),
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/send')]
    public function sendAction(
        Request $request,
        MailAssistant $mailAssistant,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $room = $this->getRoom($roomId);

        $defaultBodyMessage = '<br/><br/><br/>--<br/>'.$this->translator->trans(
            'This email has been sent to all users of this group',
            [
                '%sender_name%' => $currentUser->getFullName(),
                '%group_name%' => $item->getName(),
                '%room_name%' => $room->getTitle(),
            ],
            'mail'
        );

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(GroupSendType::class, $formData, [
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

                // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
                $users = $this->userService->getUsersByGroupIds($roomId, $item->getItemID(), true);

                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        if ($user->isEmailVisible()) {
                            $to[$userEmail] = $userName;
                        } else {
                            $toBCC[$userEmail] = $userName;
                        }
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                $toCC = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[] = new Address($currentUserEmail, $currentUserName);
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        if ($currentUser->isEmailVisible()) {
                            $toCC[$currentUserEmail] = $currentUserName;
                        } else {
                            $toBCC[$currentUserEmail] = $currentUserName;
                        }
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

                // NOTE: as of #2461 all mail should be sent as BCC mail
                $allRecipients = array_merge($to, $toCC, $toBCC);
                $email->bcc(...$mailAssistant->convertArrayToAddresses($allRecipients));

                $this->addFlash('recipientCount', count($allRecipients));

                // send mail
                $mailSend = $this->mailer->sendEmailObject($email, $portalItem->getTitle());
                $this->addFlash('mailSend', $mailSend);

                // redirect to success page
                return $this->redirectToRoute('app_group_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to group feed
                return $this->redirectToRoute('app_group_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return $this->render('group/send.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/download')]
    public function downloadAction(
        Request $request,
        DownloadAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/unlockgrouproom')]
    #[Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")]
    public function unlockGrouproom($roomId, $itemId, GroupService $groupService): Response
    {
        $group = $groupService->getGroup($itemId);
        if ($group) {
            /** @var \cs_grouproom_item $grouproomItem */
            $groupRoom = $group->getGroupRoomItem();
            if ($groupRoom) {
                $groupRoom->unlock();
                $groupRoom->save();
            }
        }

        return $this->redirectToRoute('app_group_detail', [
            'roomId' => $roomId,
            'itemId' => $itemId,
        ]);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorizeAction(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtagAction(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/activate', condition: 'request.isXmlHttpRequest()')]
    public function xhrActivateAction(
        Request $request,
        ActivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeactivateAction(
        Request $request,
        DeactivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    private function copySettings($masterRoom, $targetRoom, LegacyCopy $legacyCopy)
    {
        $user_manager = $this->legacyEnvironment->getUserManager();
        $creator_item = $user_manager->getItem($targetRoom->getCreatorID());
        if ($creator_item->getContextID() != $targetRoom->getItemID()) {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($targetRoom->getItemID());
            $user_manager->setUserIDLimit($creator_item->getUserID());
            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
            $user_manager->setModeratorLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->isNotEmpty() and 1 == $user_list->getCount()) {
                $creator_item = $user_list->getFirst();
            } else {
                throw new \Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($masterRoom, $targetRoom);

        // save new room
        $targetRoom->save(false);

        // copy data
        $legacyCopy->copyData($masterRoom, $targetRoom, $creator_item);

        return $targetRoom;
    }

    /**
     * @return array
     */
    private function getAvailableTemplates()
    {
        $templates = [];

        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($currentPortal->getItemID());
        $roomManager->setOnlyGrouproom();
        $roomManager->setTemplateLimit();
        $roomManager->select();
        $roomList = $roomManager->get();

        $defaultId = $this->legacyEnvironment->getCurrentPortalItem()->getDefaultProjectTemplateID();
        if ($roomList->isNotEmpty() or '-1' != $defaultId) {
            $currentUser = $this->legacyEnvironment->getCurrentUser();
            if ('-1' != $defaultId) {
                $defaultItem = $roomManager->getItem($defaultId);
                if (isset($defaultItem)) {
                    $template_availability = $defaultItem->getTemplateAvailability();
                    if ('0' == $template_availability) {
                        $templates[$defaultItem->getTitle()] = $defaultItem->getItemID();
                    }
                }
            }
            $item = $roomList->getFirst();
            while ($item) {
                $templateAvailability = $item->getTemplateAvailability();

                if (('0' == $templateAvailability) or
                    ($this->legacyEnvironment->inCommunityRoom() and '3' == $templateAvailability) or
                    ('1' == $templateAvailability and $item->mayEnter($currentUser)) or
                    ('2' == $templateAvailability and $item->mayEnter($currentUser) and $item->isModeratorByUserID($currentUser->getUserID(),
                        $currentUser->getAuthSource()))
                ) {
                    if ($item->getItemID() != $defaultId or '0' != $item->getTemplateAvailability()) {
                        $templates[$item->getTitle()] = $item->getItemID();
                    }
                }
                $item = $roomList->getNext();
            }
            unset($currentUser);
        }

        return $templates;
    }

    /**
     * @param \cs_room_item $room
     *
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(GroupFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_group_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => false,
            'hasCategories' => false,
        ]);
    }

    /**
     * @param \cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return \cs_user_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        // get the user service
        if ($selectAll) {
            if ($request->query->has('group_filter')) {
                $currentFilter = $request->query->get('group_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->groupService->setFilterConditions($filterForm);
            } else {
                $this->groupService->hideDeactivatedEntries();
            }

            return $this->groupService->getListGroups($roomItem->getItemID());
        } else {
            return $this->groupService->getGroupsById($roomItem->getItemID(), $itemIds);
        }
    }
}
