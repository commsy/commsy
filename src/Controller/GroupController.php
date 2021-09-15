<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Download\DownloadAction;
use App\Http\JsonDataResponse;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\CategoryService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\RoomService;
use App\Utils\UserService;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use App\Filter\GroupFilterType;
use App\Form\Type\GroupType;
use App\Form\Type\GrouproomType;
use App\Form\Type\AnnotationType;
use App\Form\Type\GroupSendType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use App\Event\CommsyEditEvent;

/**
 * Class GroupController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'group')")
 */
class GroupController extends BaseController
{
    /** @var RoomService $roomService */
    private $roomService;

    /** @var UserService $userService */
    private $userService;

    public function __construct(RoomService $roomService, UserService $userService)
    {
        // TODO: uncomment this line for CommSy10
        // parent::__construct($roomService);

        $this->roomService = $roomService;
        $this->userService = $userService;
    }

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
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $groupService->setFilterConditions($filterForm);
        } else {
            $groupService->hideDeactivatedEntries();
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
            'showAssociations' => false,
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/print/{sort}", defaults={"sort" = "none"})
     */
    public function printlistAction($roomId, Request $request, $sort, PrintService $printService)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // get the group manager service
        $groupService = $this->get('commsy_legacy.group_service');
        $numAllGroups = $groupService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $groupService->setFilterConditions($filterForm);
        } else {
            $groupService->hideDeactivatedEntries();
        }

        // get group list from manager service 
        if ($sort != "none") {
            $groups = $groupService->getListGroups($roomId, $numAllGroups, 0, $sort);
        } elseif ($this->get('session')->get('sortGroups')) {
            $groups = $groupService->getListGroups($roomId, $numAllGroups, 0, $this->get('session')->get('sortGroups'));
        } else {
            $groups = $groupService->getListGroups($roomId, $numAllGroups, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get group list from manager service 
        $itemsCountArray = $groupService->getCountArray($roomId);


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

    /**
     * @Route("/room/{roomId}/group/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
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
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($groupFilter);

            $groupService->setFilterConditions($filterForm);
        } else {
            $groupService->hideDeactivatedEntries();
        }

        // get group list from manager service 
        $groups = $groupService->getListGroups($roomId, $max, $start, $sort);

        $this->get('session')->set('sortGroups', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        // contains member status of current user for each group and grouproom
        $allGroupsMemberStatus = [];

        $readerList = array();
        $allowedActions = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'sendmail', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'sendmail');
            }

            // add groupMember and groupRoomMember status to each group!
            $groupMemberStatus = [];

            // group member status
            $membersList = $item->getMemberItemList();
            $members = $membersList->to_array();
            $groupMemberStatus['groupMember'] = $membersList->inList($legacyEnvironment->getCurrentUserItem());

            // grouproom member status
            if ($item->isGroupRoomActivated()) {
                if ($item->getGroupRoomItem()) {
                    $groupMemberStatus['groupRoomMember'] = $this->userService->getMemberStatus(
                        $item->getGroupRoomItem(),
                        $legacyEnvironment->getCurrentUser()
                    );
                } else {
                    $groupMemberStatus['groupRoomMember'] = 'deactivated';
                }
            } else {
                $groupMemberStatus['groupRoomMember'] = 'deactivated';
            }
            $allGroupsMemberStatus[$item->getItemID()] = $groupMemberStatus;
        }

        return array(
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
            'memberStatus' => $allGroupsMemberStatus,
            'isRoot' => $legacyEnvironment->getCurrentUser()->isRoot(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
     */
    public function detailAction($roomId, $itemId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $memberStatus = '';

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($infoArray['group']->isGroupRoomActivated()) {
            $groupRoomItem = $infoArray['group']->getGroupRoomItem();
            if ($groupRoomItem && !empty($groupRoomItem)) {
                $memberStatus = $this->userService->getMemberStatus(
                    $groupRoomItem,
                    $legacyEnvironment->getCurrentUser()
                );
            } else {
                $memberStatus = 'deactivated';
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['group']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $itemService = $this->get('commsy_legacy.item_service');
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        $currentUserIsLastGrouproomModerator = $this->userService->userIsLastModeratorForRoom($infoArray['group']->getGroupRoomItem());

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
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $infoArray['roomCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'userIsMember' => $infoArray['userIsMember'],
            'memberStatus' => $memberStatus,
            'annotationForm' => $form->createView(),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isArchived' => $roomItem->isArchived(),
            'lastModeratorStanding' => $currentUserIsLastGrouproomModerator,
            'userRubricVisible' => in_array("user", $this->roomService->getRubricInformation($roomId)),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/print")
     */
    public function printAction($roomId, $itemId, PrintService $printService)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

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


    private function getDetailInfo($roomId, $itemId)
    {
        $infoArray = array();

        $groupService = $this->get('commsy_legacy.group_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');

        $group = $groupService->getGroup($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $group;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "reader['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($reader) || $reader['read_date'] <= $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "noticed['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($noticed) || $noticed['read_date'] <= $item->getModificationDate()) {
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
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(), $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);
        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $modifierList = array();
        $reader = $readerService->getLatestReader($group->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $group->getModificationDate()) {
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
                $lastItemId = $groups[sizeof($groups) - 1]->getItemId();
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
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['roomCategories'] = $categories;
        $infoArray['members'] = $members;
        $infoArray['userIsMember'] = $membersList->inList($infoArray['user']);

        return $infoArray;
    }

    private function getTagDetailArray($baseCategories, $itemCategories)
    {
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
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'group')")
     */
    public function createAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $groupService = $this->get('commsy_legacy.group_service');

        // create new group item
        $groupItem = $groupService->getNewGroup();
        $groupItem->setDraftStatus(1);
        $groupItem->setPrivateEditing(1);
        $groupItem->save();

        // add current user to new group
        $groupItem->addMember($legacyEnvironment->getCurrentUser());

        return $this->redirectToRoute('app_group_detail', array('roomId' => $roomId, 'itemId' => $groupItem->getItemId()));
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
     */
    public function editAction($roomId, $itemId, Request $request, LabelService $labelService, CategoryService $categoryService)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $groupService = $this->get('commsy_legacy.group_service');
        $transformer = $this->get('commsy_legacy.transformer.group');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $groupItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        // get date from DateService
        $groupItem = $groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id ' . $itemId);
        }
        $itemController = $this->get('commsy.item_controller');
        $formData = $transformer->transform($groupItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['draft'] = $isDraft;
        $translator = $this->get('translator');
        $form = $this->createForm(GroupType::class, $formData, array(
            'action' => $this->generateUrl('app_group_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '[' . $translator->trans('insert title') . ']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $categoryService),
                'categoryPlaceholderText' => $translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId])
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $categoryIds = $formData['category_mapping']['categories'] ?? [];

                    if (isset($formData['category_mapping']['newCategory'])) {
                        $newCategoryTitle = $formData['category_mapping']['newCategory'];
                        $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                        $categoryIds[] = $newCategory->getItemID();
                    }

                    $groupItem->setTagListByID($categoryIds);
                }
                if ($hashtagsMandatory) {
                    $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];

                    if (isset($formData['hashtag_mapping']['newHashtag'])) {
                        $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];
                        $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                        $hashtagIds[] = $newHashtag->getItemID();
                    }

                    $groupItem->setBuzzwordListByID($hashtagIds);
                }

                $groupItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('app_group_save', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($groupItem));

        return array(
            'form' => $form->createView(),
            'group' => $groupItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
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
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(), $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);
        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
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
            'action' => $this->generateUrl('app_group_editgrouproom', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'templates' => $this->getAvailableTemplates(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {

                $originalGroupName = "";
                if ($groupItem->getGroupRoomItem() && !empty($groupItem->getGroupRoomItem())) {
                    $originalGroupName = $groupItem->getGroupRoomItem()->getTitle();
                }

                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $groupItem->save(true);

                $groupRoom = $groupItem->getGroupRoomItem();

                // only initialize the name of the grouproom the first time it is created!
                if ($groupRoom && !empty($groupRoom)) {
                    if ($originalGroupName == "") {
                        $translator = $this->get('translator');
                        $groupRoom->setTitle($groupItem->getTitle() . " (" . $translator->trans('grouproom', [], 'group') . ")");
                    } else {
                        $groupRoom->setTitle($originalGroupName);
                    }
                    $groupRoom->save(false);

                    $calendarsService = $this->get('commsy.calendars_service');
                    $calendarsService->createCalendar($groupRoom, null, null, true);

                    // take values from a template?
                    if ($form->has('master_template')) {
                        $masterTemplate = $form->get('master_template')->getData();

                        $masterRoom = $this->get('commsy_legacy.room_service')->getRoomItem($masterTemplate);
                        if ($masterRoom) {
                            $groupRoom = $this->copySettings($masterRoom, $groupRoom);
                        }
                    }
                    $groupItem->save(true);
                }

            } else {
                // ToDo ...
            }
            return $this->redirectToRoute('app_group_savegrouproom', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($groupItem));

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/date/{itemId}/savegrouproom")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
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
     * @Route("/room/{roomId}/group/{itemId}/join/{joinRoom}", defaults={"joinRoom"=false})
     */
    public function joinAction($roomId, $itemId, $joinRoom)
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

        // first, join group
        if ($groupItem->getMemberItemList()->inList($current_user)) {
            throw new \Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join group '" . $groupItem->getName() . "' since (s)he already is a member!");
        } else {
            $groupItem->addMember($current_user);
        }

        // then, join grouproom
        if ($joinRoom) {
            $grouproomItem = $groupItem->getGroupRoomItem();
            if ($grouproomItem) {
                $memberStatus = $this->userService->getMemberStatus($grouproomItem, $legacyEnvironment->getCurrentUser());
                if ($memberStatus == 'join') {
                    return $this->redirectToRoute('app_context_request', [
                        'roomId' => $roomId,
                        'itemId' => $grouproomItem->getItemId(),
                    ]);
                } else {
                    throw new \Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join group room '" . $grouproomItem->getTitle() . "' since (s)he has room member status '" . $memberStatus . "' (requires status 'join' to become a room member)!");
                }
            } else {
                throw new \Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join the group room of group '" . $groupItem->getName() . "' since it does not exist!");
            }
        }

        return new JsonDataResponse([
            'title' => $groupItem->getTitle(),
            'groupId' => $itemId,
            'memberId' => $current_user->getItemId(),
        ]);
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/leave")
     */
    public function leaveAction($roomId, $itemId)
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

        return new JsonDataResponse([
            'title' => $groupItem->getTitle(),
            'groupId' => $itemId,
            'memberId' => $current_user->getItemId(),
        ]);
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/members", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
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
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
     */
    public function groupRoomAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $groupService = $this->get('commsy_legacy.group_service');
        $group = $groupService->getGroup($itemId);

        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        $memberStatus = '';
        $memberStatus = $this->userService->getMemberStatus(
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

    /**
     * @Route("/room/{roomId}/group/sendMultiple")
     * @Template()
     */
    public function sendMultipleAction($roomId, Request $request, MailAssistant $mailAssistant)
    {

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

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $groupService = $this->get('commsy_legacy.group_service');

        // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
        $users = $this->userService->getUsersByGroupIds($roomId, $groupIds, true);

        // include a footer message in the email body
        $groupCount = count($groupIds);
        $defaultBodyMessage = '';
        if ($groupCount) {
            $defaultBodyMessage .= '<br/><br/><br/>' . '--' . '<br/>';
            $translator = $this->get('translator');
            if ($groupCount == 1) {
                $group = $groupService->getGroup(reset($groupIds));
                if ($group) {
                    $defaultBodyMessage .= $translator->trans(
                        'This email has been sent to all users of this group',
                        ['%sender_name%' => $currentUser->getFullName(), '%group_name%' => $group->getName(), '%room_name%' => $room->getTitle()],
                        'mail'
                    );
                }
            } elseif ($groupCount > 1) {
                $defaultBodyMessage .= $translator->trans(
                    'This email has been sent to multiple users of this room',
                    ['%sender_name%' => $currentUser->getFullName(), '%user_count%' => count($users), '%room_name%' => $room->getTitle()],
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
                        $replyTo[$currentUserEmail] = $currentUserName;
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
                    $failedUser = array_filter($users, function ($user) use ($failedRecipient) {
                        return $user->getEmail() == $failedRecipient;
                    });

                    if ($failedUser) {
                        $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
                    }
                }

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

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/group/sendMultiple/success")
     * @Template()
     **/
    public function sendMultipleSuccessAction($roomId)
    {
        return [
            'link' => $this->generateUrl('app_group_list', [
                'roomId' => $roomId,
            ]),
        ];
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/send")
     * @Template()
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
        $room = $this->getRoom($roomId);

        $translator = $this->get('translator');
        $defaultBodyMessage = '<br/><br/><br/>' . '--' . '<br/>' . $translator->trans(
                'This email has been sent to all users of this group',
                ['%sender_name%' => $currentUser->getFullName(), '%group_name%' => $item->getName(), '%room_name%' => $room->getTitle()],
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

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $legacyEnvironment->getCurrentPortalItem();

                $from = $this->getParameter('commsy.email.from');

		        // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
                $users = $this->userService->getUsersByGroupIds($roomId, $item->getItemID(), true);

                // NOTE: as of #2461 all mail should be sent as BCC mail; but, for now, we keep the original logic here
                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $forceBCCMail = true;

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
                        $replyTo[$currentUserEmail] = $currentUserName;
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
                    $failedUser = array_filter($users, function ($user) use ($failedRecipient) {
                        return $user->getEmail() == $failedRecipient;
                    });

                    if ($failedUser) {
                        $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
                    }
                }

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

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/group/download")
     * @throws \Exception
     */
    public function downloadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(DownloadAction::class);
        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/group/xhr/markread", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrMarkReadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/group/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    private function copySettings($masterRoom, $targetRoom)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

        $old_room_id = $old_room->getItemID();

        $environment = $this->get('commsy_legacy.environment')->getEnvironment();

        /**/
        $user_manager = $environment->getUserManager();
        $creator_item = $user_manager->getItem($new_room->getCreatorID());
        if ($creator_item->getContextID() == $new_room->getItemID()) {
            $creator_id = $creator_item->getItemID();
        } else {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($new_room->getItemID());
            $user_manager->setUserIDLimit($creator_item->getUserID());
            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
            $user_manager->setModeratorLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
                $creator_item = $user_list->getFirst();
                $creator_id = $creator_item->getItemID();
            } else {
                throw new \Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        require_once('include/inc_room_copy_config.php');

        // save new room
        $new_room->save(false);

        // copy data
        require_once('include/inc_room_copy_data.php');
        /**/

        $targetRoom = $new_room;

        return $targetRoom;
    }

    private function getAvailableTemplates()
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $templates = [];

        $currentPortal = $legacyEnvironment->getCurrentPortalItem();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($currentPortal->getItemID());
        $roomManager->setOnlyGrouproom();
        $roomManager->setTemplateLimit();
        $roomManager->select();
        $roomList = $roomManager->get();

        $defaultId = $legacyEnvironment->getCurrentPortalItem()->getDefaultProjectTemplateID();
        if ($roomList->isNotEmpty() or $defaultId != '-1') {
            $currentUser = $legacyEnvironment->getCurrentUser();
            if ($defaultId != '-1') {
                $defaultItem = $roomManager->getItem($defaultId);
                if (isset($defaultItem)) {
                    $template_availability = $defaultItem->getTemplateAvailability();
                    if ($template_availability == '0') {
                        $templates[$defaultItem->getTitle()] = $defaultItem->getItemID();
                    }
                }
            }
            $item = $roomList->getFirst();
            while ($item) {
                $templateAvailability = $item->getTemplateAvailability();

                if (($templateAvailability == '0') OR
                    ($legacyEnvironment->inCommunityRoom() and $templateAvailability == '3') OR
                    ($templateAvailability == '1' and $item->mayEnter($currentUser)) OR
                    ($templateAvailability == '2' and $item->mayEnter($currentUser) and ($item->isModeratorByUserID($currentUser->getUserID(), $currentUser->getAuthSource())))
                ) {
                    if ($item->getItemID() != $defaultId or $item->getTemplateAvailability() != '0') {
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
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_user_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        // get the user service
        $groupService = $this->get('commsy_legacy.group_service');

        if ($selectAll) {
            if ($request->query->has('group_filter')) {
                $currentFilter = $request->query->get('group_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $groupService->setFilterConditions($filterForm);
            } else {
                $groupService->hideDeactivatedEntries();
            }

            return $groupService->getListGroups($roomItem->getItemID());
        } else {
            return $groupService->getGroupsById($roomItem->getItemID(), $itemIds);
        }
    }
}
