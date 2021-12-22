<?php

namespace App\Controller;

use App\Entity\Room;
use App\Event\UserJoinedRoomEvent;
use App\Filter\ProjectFilterType;
use App\Form\Type\ProjectType;
use App\Form\Type\Room\DeleteType;
use App\Room\Copy\LegacyCopy;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\RoomCategoriesService;
use App\Utils\ItemService;
use App\Utils\ProjectService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\UserService;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ProjectController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ProjectController extends AbstractController
{
    private $legacyEnvironment;
    private $translator;


    /**
     * ProjectController constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param TranslatorInterface $translator
     */
    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->translator = $translator;
    }


    /**
     * @Route("/room/{roomId}/project/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param ProjectService $projectService
     * @param ReaderService $readerService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        ProjectService $projectService,
        ReaderService $readerService,
        LegacyEnvironment $environment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date_rev'
    ) {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('app_project_list', array('roomId' => $roomId)),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $projects = $projectService->getListProjects($roomId, $max, $start, $sort);
        $projectsMemberStatus = array();
        foreach ($projects as $project) {
            $projectsMemberStatus[$project->getItemId()] = $this->memberStatus($project);
        }

        $readerList = array();
        foreach ($projects as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        $legacyEnvironment = $environment->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUser();

        return array(
            'roomId' => $roomId,
            'projects' => $projects,
            'projectsMemberStatus' => $projectsMemberStatus,
            'readerList' => $readerList,
            'currentUser' => $currentUser
        );
    }

    /**
     * @Route("/room/{roomId}/project")
     * @Template()
     * @param Request $request
     * @param ProjectService $projectService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        ProjectService $projectService,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('app_project_list', array('roomId' => $roomId)),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        $usageInfo = false;

        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        if ($roomItem->getUsageInfoTextForRubricInForm('project') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('project');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('project');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'project',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo,
            'userCanCreateContext' => $legacyEnvironment->getCurrentUserItem()->isAllowedToCreateContext(),
        );
    }

    /**
     * @Route("/room/{roomId}/project/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     * @param ItemService $itemService
     * @param RoomService $roomService
     * @param UserService $userService
     * @param LegacyMarkup $legacyMarkup
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        ItemService $itemService,
        RoomService $roomService,
        UserService $userService,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);
        
        $currentUser = $legacyEnvironment->getCurrentUser();
        $infoArray = $this->getDetailInfo($itemId, $environment, $itemService);
        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);
        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);

        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));
        
        return [
            'roomId' => $roomId,
            'item' => $roomItem,
            'currentUser' => $currentUser,
            'modifierList' => $infoArray['modifierList'],
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'memberStatus' => $memberStatus,
            'contactModeratorItems' => $contactModeratorItems,
        ];
    }

    /**
     * @Route("/room/{roomId}/project/create", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @param Request $request
     *
     * @param CalendarsService $calendarsService
     * @param RoomCategoriesService $roomCategoriesService
     * @param RoomService $roomService
     * @param UserService $userService
     * @param LegacyEnvironment $legacyEnvironment
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyCopy $legacyCopy
     * @param int $roomId
     * @return array|RedirectResponse
     * @throws Exception
     */
    public function createAction(
        Request $request,
        CalendarsService $calendarsService,
        RoomCategoriesService $roomCategoriesService,
        RoomService $roomService,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher,
        LegacyCopy $legacyCopy,
        int $roomId
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $currentUser = $legacyEnvironment->getCurrentUserItem();
        if (!$currentUser->isAllowedToCreateContext()) {
            throw $this->createAccessDeniedException();
        }

        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        $defaultId = $legacyEnvironment->getCurrentPortalItem()->getDefaultProjectTemplateID();
        $defaultTemplateIDs = ($defaultId === '-1') ? [] : [ $defaultId ];

        $timesDisplay = ucfirst($currentPortalItem->getCurrentTimeName());
        $times = $roomService->getTimePulses(true);

        $room = new Room();
        $templates = $this->getAvailableTemplates($legacyEnvironment);
        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($currentPortalItem->getItemId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;

        $form = $this->createForm(ProjectType::class, $room, [
            'templates' => array_flip($templates['titles']),
            'descriptions' => $templates['descriptions'],
            'preferredChoices' => $defaultTemplateIDs,
            'timesDisplay' => $timesDisplay,
            'times' => $times,
            'roomCategories' => $roomCategories,
            'linkRoomCategoriesMandatory' => $linkRoomCategoriesMandatory,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // create a new room using the legacy code
                $communityRoom = $roomService->getRoomItem($roomId);
                $context = $request->get('project');
                $projectManager = $legacyEnvironment->getProjectManager();

                $legacyRoom = $projectManager->getNewItem();

                $currentUser = $legacyEnvironment->getCurrentUserItem();
                $legacyRoom->setCreatorItem($currentUser);
                $legacyRoom->setCreationDate(getCurrentDateTimeInMySQL());
                $legacyRoom->setModificatorItem($currentUser);
                $legacyRoom->setContextID($legacyEnvironment->getCurrentPortalID());
                $legacyRoom->open();
                $legacyRoom->setRoomContext($communityRoom->getRoomContext());
                $legacyRoom->setCommunityListByID([$roomId]);

                // fill in form values from the new entity object
                $legacyRoom->setTitle($room->getTitle());
                $legacyRoom->setDescription($room->getRoomDescription());

                if (isset($context['createUserRooms'])) {
                    $legacyRoom->setShouldCreateUserRooms($context['createUserRooms']);
                }
                if (isset($context['userroom_template'])) {
                    $userroomTemplate = $roomService->getRoomItem($context['userroom_template']);
                    if ($userroomTemplate) {
                        $legacyRoom->setUserRoomTemplateID($userroomTemplate->getItemID());
                    }
                }

                $timeIntervals = $context['time_interval'] ?? [];
                if (empty($timeIntervals) || in_array('cont', $timeIntervals)) {
                    $legacyRoom->setContinuous();
                    $legacyRoom->setTimeListByID([]);
                } else {
                    $legacyRoom->setNotContinuous();
                    $legacyRoom->setTimeListByID($timeIntervals);
                }

                // persist with legacy code
                $legacyRoom->save();

                $calendarsService->createCalendar($legacyRoom, null, null, true);

                // take values from a template?
                if ($form->has('master_template')) {
                    $masterTemplate = $form->get('master_template')->getData();

                    $masterRoom = $roomService->getRoomItem($masterTemplate);
                    if ($masterRoom) {
                        $legacyRoom = $this->copySettings($masterRoom, $legacyRoom, $legacyCopy);
                    }
                }

                // NOTE: we can only set the language after copying settings from any room template, otherwise the language
                // would get overwritten by the room template's language setting
                $legacyRoom->setLanguage($room->getLanguage());
                $legacyRoom->save();

                $legacyRoomUsers = $userService->getListUsers($legacyRoom->getItemID(), null, null, true);
                foreach ($legacyRoomUsers as $user) {
                    $event = new UserJoinedRoomEvent($user, $legacyRoom);
                    $eventDispatcher->dispatch($event);
                }

                // mark the room as edited
                $linkModifierItemManager = $legacyEnvironment->getLinkModifierItemManager();
                $linkModifierItemManager->markEdited($legacyRoom->getItemID());

                if ($form->has('categories')) {
                    $roomCategoriesService->setRoomCategoriesLinkedToContext($legacyRoom->getItemId(), $form->get('categories')->getData());
                }

                // redirect to the project detail page
                return $this->redirectToRoute('app_project_detail', [
                    'roomId' => $roomId,
                    'itemId' => $legacyRoom->getItemId(),
                ]);
            } else {
                return $this->redirectToRoute('app_project_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/project/{itemId}/edit", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction()
    {
    }

    /**
     * @Route("/room/{roomId}/project/{itemId}/delete", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('MODERATOR', itemId)")
     * @param Request $request
     * @param RoomService $roomService
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function deleteAction(
        Request $request,
        RoomService $roomService,
        int $roomId,
        int $itemId
    ) {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $itemId);
        }

        $form = $this->createForm(DeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $this->translator->trans('delete', [], 'profile')
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roomItem->delete();
            $roomItem->save();

            return $this->redirectToRoute('app_project_list', ['roomId' => $roomId]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    private function getDetailInfo(
        int $roomId,
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService
    ) {
        $readerManager = $legacyEnvironment->getEnvironment()->getReaderManager();

        $info = [];

        // modifier
        $room = $itemService->getItem($roomId);
        $info['modifierList'][$roomId] = $itemService->getAdditionalEditorsForItem($room);

        // total user count
        $userManager = $legacyEnvironment->getEnvironment()->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getEnvironment()->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $userList = $userManager->get();

        $info['userCount'] = $userList->getCount();

        // total and since modification reader count
        $readerCount = 0;
        $readSinceModificationCount = 0;
        $currentUser = $userList->getFirst();

        $userIds = array();
        while ($currentUser) {
            $userIds[] = $currentUser->getItemID();

            $currentUser = $userList->getNext();
        }

        $readerManager->getLatestReaderByUserIDArray($userIds, $room->getItemID());
        $currentUser = $userList->getFirst();
        while ($currentUser) {
            $currentReader = $readerManager->getLatestReaderForUserByID($room->getItemID(), $currentUser->getItemID());
            if ( !empty($currentReader) ) {
                if ($currentReader['read_date'] >= $room->getModificationDate()) {
                    $readSinceModificationCount++;
                }

                $readerCount++;
            }
            $currentUser = $userList->getNext();
        }

        $info['readCount'] = $readerCount;
        $info['readSinceModificationCount'] = $readSinceModificationCount;

        return $info;
    }

    private function copySettings($masterRoom, $targetRoom, LegacyCopy $legacyCopy)
    {
        /**/
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
            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
                $creator_item = $user_list->getFirst();
            } else {
                throw new Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($masterRoom, $targetRoom);

        // save new room
        $targetRoom->save();

        // copy data
        $legacyCopy->copyData($masterRoom, $targetRoom, $creator_item);
        /**/

        return $targetRoom;
    }

    /**
     * @param \cs_environment $legacyEnvironment
     * @param string $type
     * @return array
     */
    private function getAvailableTemplates(\cs_environment $legacyEnvironment, $type = 'project')
    {
        $templates = [];

        $currentUserItem = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($legacyEnvironment->getCurrentPortalItem()->getItemID());
        $roomManager->setTemplateLimit();
        $roomManager->select();

        $templateList = $roomManager->get();

        $titles = [];
        $descriptions = [];

        if ($templateList->isNotEmpty()) {
            $template = $templateList->getFirst();
            while ($template) {
                $availability = $template->getTemplateAvailability();

                $add = false;

                // free for all?
                if (!$add && $availability == '0') {
                    $add = true;
                }

                // only in community rooms
                if (!$add && $legacyEnvironment->inCommunityRoom() && $availability == '3') {
                    $add = true;
                }

                // same as above, but from portal context
                if (!$add && $legacyEnvironment->inPortal() && $availability == '3') {
                    // check if user is member in one of the templates community rooms
                    $communityList = $template->getCommunityList();
                    if ($communityList->isNotEmpty()) {
                        $userCommunityList = $currentUserItem->getRelatedCommunityList();
                        if ($userCommunityList->isNotEmpty()) {
                            $communityItem = $communityList->getFirst();
                            while ($communityItem) {
                                $userCommunityItem = $userCommunityList->getFirst();
                                while ($userCommunityItem) {
                                    if ($userCommunityItem->getItemID() == $communityItem->getItemID()) {
                                        $add = true;
                                        break;
                                    }

                                    $userCommunityItem = $userCommunityList->getNext();
                                }

                                $communityItem = $communityList->getNext();
                            }
                        }
                    }
                }

                // only for members
                if (!$add && $availability == '1' && $template->mayEnter($currentUserItem)) {
                    $add = true;
                }

                // only mods
                if (!$add && $availability == '2' && $template->mayEnter($currentUserItem)) {
                    if ($template->isModeratorByUserID($currentUserItem->getUserID(), $currentUserItem->getAuthSource())) {
                        $add = true;
                    }
                }

                if ($type != $template->getItemType()) {
                    $add = false;
                }

                if ($add) {
                    $label = $template->getTitle() . ' (ID: ' . $template->getItemID() . ')';
                    $titles[$template->getItemID()] = $label;
                    $descriptions[$template->getItemID()] = $template->getDescription();
                }

                $template = $templateList->getNext();
            }
        }
        $templates['titles'] = $titles;
        $templates['descriptions'] = $descriptions;

        return $templates;
    }
    
    private function memberStatus($item)
    {
        $status = 'closed';
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();

        $relatedUserArray = $currentUser->getRelatedUserList()->to_array();
        $roomUser = null;
        foreach ($relatedUserArray as $relatedUser) {
            if ($relatedUser->getContextId() == $item->getItemId()) {
                $roomUser = $relatedUser;
            }
        }

        $mayEnter = false;
        if ($currentUser->isRoot()) {
            $mayEnter = true;
        } elseif (!empty($roomUser)) {
            $mayEnter = $item->mayEnter($roomUser);
        } else {
            // in case of the guest user, $roomUser is null
            if ($currentUser->isReallyGuest()) {
                $mayEnter = $item->mayEnter($currentUser);
            }
        }
        
        if ($mayEnter) {
            if ($item->isOpen()) {
                $status = 'enter';
            } else {
                $status = 'join';
            }
        } elseif ($item->isLocked()) {
            $status = 'locked';
        } elseif(!empty($roomUser) and $roomUser->isRequested()) {
            $status = 'requested';
        } elseif(!empty($roomUser) and $roomUser->isRejected()) {
            $status = 'rejected';
        } else {
            if ($currentUser->isReallyGuest()) {
                return 'forbidden';
            }
        }
        
        return $status;
    }
}
