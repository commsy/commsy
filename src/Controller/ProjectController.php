<?php

namespace App\Controller;

use App\Form\Type\ProjectType;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Utils\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\Room\DeleteType;
use App\Filter\ProjectFilterType;
use App\Entity\Room;

/**
 * Class ProjectController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ProjectController extends Controller
{    
    /**
     * @Route("/room/{roomId}/project/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date_rev', Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('app_project_list', array('roomId' => $roomId)),
        ));

        // get the project manager service
        $projectService = $this->get('commsy_legacy.project_service');

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

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        foreach ($projects as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
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
     */
    public function listAction($roomId, Request $request, LegacyEnvironment $legacyEnvironment)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('app_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        $usageInfo = false;

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
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
     */
    public function detailAction($roomId, $itemId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $userService = $this->get('commsy_legacy.user_service');
                
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);
        
        $currentUser = $legacyEnvironment->getCurrentUser();

        $infoArray = $this->getDetailInfo($roomItem);

        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);

        $roomService = $this->get('commsy_legacy.room_service');
        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);

        $itemService = $this->get('commsy_legacy.item_service');
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
     * @param $roomId
     * @param Request $request
     *
     * @Route("/room/{roomId}/project/create", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function createAction($roomId, Request $request, LegacyEnvironment $legacyEnvironment, RoomService $roomService)
    {
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
        $templates = $this->getAvailableTemplates();
        $roomCategoriesService = $this->get('commsy.roomcategories_service');
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

                $context = $request->get('project');
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

                $calendarsService = $this->get('commsy.calendars_service');
                $calendarsService->createCalendar($legacyRoom, null, null, true);

                // take values from a template?
                if ($form->has('master_template')) {
                    $masterTemplate = $form->get('master_template')->getData();

                    $masterRoom = $roomService->getRoomItem($masterTemplate);
                    if ($masterRoom) {
                        $legacyRoom = $this->copySettings($masterRoom, $legacyRoom);
                    }
                }

                // NOTE: we can only set the language after copying settings from any room template, otherwise the language
                // would get overwritten by the room template's language setting
                $legacyRoom->setLanguage($room->getLanguage());
                $legacyRoom->save();

                // mark the room as edited
                $linkModifierItemManager = $legacyEnvironment->getLinkModifierItemManager();
                $linkModifierItemManager->markEdited($legacyRoom->getItemID());

                if ($form->has('categories')) {
                    $roomCategoriesService = $this->get('commsy.roomcategories_service');
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
     */
    public function deleteAction($roomId, $itemId, Request $request)
    {
        $form = $this->createForm(DeleteType::class, ['confirm_string' => $this->get('translator')->trans('delete', [], 'profile')], []);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // get room from RoomService
            $roomService = $this->get('commsy_legacy.room_service');
            $roomItem = $roomService->getRoomItem($itemId);

            if (!$roomItem) {
                throw $this->createNotFoundException('No room found for id ' . $itemId);
            }

            $roomItem->delete();
            $roomItem->save();

            // redirect back to portal
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

            return $this->redirectToRoute('app_project_list', ['roomId' => $roomId]);
        }

        return [
            'form' => $form->createView(),
        ];
    }



    private function getDetailInfo($room)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();

        $info = [];

        // modifier
        $info['modifierList'][$room->getItemId()] = $itemService->getAdditionalEditorsForItem($room);

        // total user count
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
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
        $new_room->save();

        // copy data
        require_once('include/inc_room_copy_data.php');
        /**/

        $targetRoom = $new_room;

        return $targetRoom;
    }

    private function getAvailableTemplates($type = 'project')
    {
        $templates = [];

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
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
