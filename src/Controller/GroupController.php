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

use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\Pin\PinAction;
use App\Action\Pin\UnpinAction;
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
use App\Security\Authorization\Voter\CategoryVoter;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\GroupService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\TopicService;
use App\Utils\UserService;
use cs_group_item;
use cs_grouproom_item;
use cs_room_item;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class GroupController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_GROUP')]
class GroupController extends BaseController
{
    private GroupService $groupService;

    private UserService $userService;

    private Mailer $mailer;

    #[Required]
    public function setGroupService(GroupService $groupService): void
    {
        $this->groupService = $groupService;
    }

    #[Required]
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Required]
    public function setUserService(UserService $userService): void
    {
        $this->userService = $userService;
    }

    #[Route(path: '/room/{roomId}/group')]
    public function list(
        Request $request,
        int $roomId,
        ItemService $itemService
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

        $sort = $request->getSession()->get('sortGroups', 'date');

        // get group list from manager service
        $itemsCountArray = $this->groupService->getCountArray($roomId);

        $pinnedItems = $itemService->getPinnedItems($roomId, [ CS_GROUP_TYPE, CS_LABEL_TYPE ]);

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('group')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('group');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('group');
        }

        return $this->render('group/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => CS_GROUP_TYPE,
            'relatedModule' => CS_LABEL_TYPE,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'showAssociations' => false,
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->getArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'sort' => $sort,
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/group/print/{sort}', defaults: ['sort' => 'none'])]
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
            $sort = $request->getSession()->get('sortGroups', 'date');
        }
        $groups = $this->groupService->getListGroups($roomId, $numAllGroups, 0, $sort);

        $readerList = $this->readerService->getChangeStatusForItems(...$groups);

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
    public function feed(
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
            $groupFilter = $request->query->all('group_filter');
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
            $sort = $request->getSession()->get('sortGroups', 'date');
        }
        $request->getSession()->set('sortGroups', $sort);

        // get group list from manager service
        $groups = $this->groupService->getListGroups($roomId, $max, $start, $sort);

        // contains member status of current user for each group and grouproom
        $allGroupsMemberStatus = [];

        $readerList = $this->readerService->getChangeStatusForItems(...$groups);

        $allowedActions = [];
        foreach ($groups as $item) {
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

        return $this->render('group/feed.html.twig', [
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
            'memberStatus' => $allGroupsMemberStatus,
            'isRoot' => $this->legacyEnvironment->getCurrentUser()->isRoot(),
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
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
            if (!empty($groupRoomItem)) {
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
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $currentUserIsLastGrouproomModerator = $this->userService->userIsLastModeratorForRoom($infoArray['group']->getGroupRoomItem());

        return $this->render('group/detail.html.twig', [
            'roomId' => $roomId,
            'group' => $infoArray['group'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'count' => $infoArray['count'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'pinned' => $infoArray['pinned'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $infoArray['roomCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'userIsMember' => $infoArray['userIsMember'],
            'memberStatus' => $memberStatus,
            'annotationForm' => $form,
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isArchived' => $roomItem->getArchived(),
            'lastModeratorStanding' => $currentUserIsLastGrouproomModerator,
            'userRubricVisible' => in_array('user', $this->roomService->getRubricInformation($roomId))
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/print')]
    public function print(
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
            'count' => $infoArray['count'],
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

        $this->readerService->markItemAsRead($group);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($group);

        $readerList = [];
        $modifierList = [];

        $readerList[$group->getItemId()] = $this->readerService->getStatusForItem($group)->value;
        $modifierList[$group->getItemId()] = $this->itemService->getAdditionalEditorsForItem($group);

        $groups = $this->groupService->getListGroups($roomId);

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
        $infoArray['count'] = sizeof($groups);
        $infoArray['readCount'] = $readCountDescription->getReadTotal();
        $infoArray['readSinceModificationCount'] = $readCountDescription->getReadSinceModification();
        $infoArray['userCount'] = $readCountDescription->getUserTotal();
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['pinned'] = $this->itemService->getItem($itemId)->isPinned();
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

    #[Route(path: '/room/{roomId}/group/create')]
    #[IsGranted('ITEM_NEW')]
    public function create(
        int $roomId
    ): RedirectResponse {
        // create new group item
        $groupItem = $this->groupService->getNewGroup();
        $groupItem->setDraftStatus(1);
        $groupItem->setPrivateEditing(1);
        $groupItem->save(false);

        return $this->redirectToRoute('app_group_detail', [
            'roomId' => $roomId,
            'itemId' => $groupItem->getItemId(),
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/edit')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        GroupTransformer $transformer,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $current_context = $this->legacyEnvironment->getCurrentContextItem();

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
        $form = $this->createForm(GroupType::class, $formData, [
            'action' => $this->generateUrl('app_group_edit', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'placeholderText' => '['.$this->translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $labelService->getCategories($roomId),
                'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
            ], 'hashtagMappingOptions' => [
                'hashtags' => $labelService->getHashtags($roomId),
                'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            ],
            'room' => $current_context,
            'templates' => $this->getAvailableTemplates(),
        ]);

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

                    if (isset($formData['category_mapping']['newCategory']) && $this->isGranted(CategoryVoter::EDIT)) {
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
            }

            return $this->redirectToRoute('app_group_save', [
                'roomId' => $roomId,
                'itemId' => $itemId,
                ]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($groupItem), CommsyEditEvent::EDIT);

        return $this->render('group/edit.html.twig', [
            'form' => $form,
            'group' => $groupItem,
            'isDraft' => $isDraft,
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/save')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function save(
        int $roomId,
        int $itemId
    ): Response {
        $group = $this->groupService->getGroup($itemId);

        $itemArray = [$group];

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($group);

        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($group), CommsyEditEvent::SAVE);

        return $this->render('group/save.html.twig', [
            'roomId' => $roomId,
            'item' => $group,
            'modifierList' => $modifierList,
            'userCount' => $readCountDescription->getUserTotal(),
            'readCount' => $readCountDescription->getReadTotal(),
            'readSinceModificationCount' => $readCountDescription->getReadSinceModification(),
        ]);
    }

    /**
     * @throws Exception
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
                throw new Exception("ERROR: User '".$currentUser->getUserID()."' cannot join group room '".$groupRoom->getTitle()."' since (s)he has room member status '".$memberStatus."' (requires status 'join' to become a room member)!");
            }
        } else {
            throw new Exception("ERROR: User '".$currentUser->getUserID()."' cannot join the group room of group '".$group->getName()."' since it does not exist!");
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

    #[Route(path: '/room/{roomId}/group/{itemId}/members', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function members(
        int $itemId
    ): Response {
        $group = $this->groupService->getGroup($itemId);
        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        return $this->render('group/members.html.twig', [
            'group' => $group,
            'members' => $members,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/sendMultiple')]
    public function sendMultiple(
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
            $postData = $request->request->all('group_send');
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
                            'sender_name' => $currentUser->getFullName(),
                            'group_name' => $group->getName(),
                            'room_name' => $room->getTitle(),
                        ],
                        'mail'
                    );
                }
            } elseif ($groupCount > 1) {
                $defaultBodyMessage .= $this->translator->trans(
                    'This email has been sent to multiple users of this room',
                    [
                        'sender_name' => $currentUser->getFullName(),
                        'user_count' => count($users),
                        'room_name' => $room->getTitle(),
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
                $recipients = [];
                $validator = new EmailValidator();
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        $recipients[$userEmail] = $userName;
                    }
                }

                $replyTo = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[] = new Address($currentUserEmail, $currentUserName);
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        $recipients[$currentUserEmail] = $currentUserName;
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

                $mailSend = true;
                foreach ($recipients as $email => $name) {
                    $message->to(new Address($email, $name));
                    $send = $this->mailer->sendEmailObject($message, $portalItem->getTitle());
                    $mailSend = $mailSend && $send;
                }

                $this->addFlash('recipientCount', count($recipients));

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

        return $this->render('group/send_multiple.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/group/sendMultiple/success')]
    public function sendMultipleSuccess(
        int $roomId
    ): Response {
        return $this->render('group/send_multiple_success.html.twig', [
            'link' => $this->generateUrl('app_group_list', [
                'roomId' => $roomId,
            ]),
        ]);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/send')]
    public function send(
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
                'sender_name' => $currentUser->getFullName(),
                'group_name' => $item->getName(),
                'room_name' => $room->getTitle(),
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
                $recipients = [];
                $validator = new EmailValidator();
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        $recipients[$userEmail] = $userName;
                    }
                }

                $replyTo = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[] = new Address($currentUserEmail, $currentUserName);
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        $recipients[$currentUserEmail] = $currentUserName;
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
                foreach ($recipients as $userEmail => $userName) {
                    $email->to(new Address($userEmail, $userName));
                    $send = $this->mailer->sendEmailObject($email, $portalItem->getTitle());
                    $mailSend = $mailSend && $send;
                }

                $this->addFlash('recipientCount', count($recipients));
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
            'form' => $form,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/download')]
    public function download(
        Request $request,
        DownloadAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    #[Route(path: '/room/{roomId}/group/{itemId}/unlockgrouproom')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function unlockGrouproom($roomId, $itemId, GroupService $groupService): Response
    {
        $group = $groupService->getGroup($itemId);
        if ($group) {
            /** @var cs_grouproom_item $grouproomItem */
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
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/markread', condition: 'request.isXmlHttpRequest()')]
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
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/pin', condition: 'request.isXmlHttpRequest()')]
    public function xhrPinAction(
        Request $request,
        PinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/unpin', condition: 'request.isXmlHttpRequest()')]
    public function xhrUnpinAction(
        Request $request,
        UnpinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorize(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtag(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/group/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    private function copySettings($masterRoom, $targetRoom, LegacyCopy $legacyCopy): mixed
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
                throw new Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($masterRoom, $targetRoom);

        // save new room
        $targetRoom->save(false);

        // copy data
        $legacyCopy->copyData($masterRoom, $targetRoom, $creator_item);

        return $targetRoom;
    }

    private function getAvailableTemplates(): array
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
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(GroupFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_group_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_group_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = []): array
    {
        // get the user service
        if ($selectAll) {
            if ($request->query->has('group_filter')) {
                $currentFilter = $request->query->all('group_filter');
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
