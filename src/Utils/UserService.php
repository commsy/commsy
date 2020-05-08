<?php

namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;


class UserService
{
    private $legacyEnvironment;

    private $userManager;

    private $roomManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    { 
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        
        $this->userManager = $this->legacyEnvironment->getUserManager();
        $this->userManager->reset();

        $this->roomManager = $this->legacyEnvironment->getRoomManager();
        $this->roomManager->reset();
    }

    public function getCountArray($roomId, $moderation = false)
    {
        $this->userManager->setContextLimit($roomId);
        if (!$moderation) {
            $this->userManager->setUserLimit();
        }
        $this->userManager->select();
        $countUser = array();
        $countUserArray['count'] = sizeof($this->userManager->get()->to_array());
        $this->userManager->resetLimits();
        if (!$moderation) {
            $this->userManager->setUserLimit();
        }
        $this->userManager->select();
        $countUserArray['countAll'] = $this->userManager->getCountAll();

        return $countUserArray;
    }


    public function resetLimits()
    {
        $this->userManager->resetLimits();
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return \cs_user_item[]
     */
    public function getListUsers($roomId, $max = NULL, $start = NULL, $moderation = false, $sort = NULL)
    {
        $this->userManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->userManager->setIntervalLimit($start, $max);
        }
        if (!$moderation) {
            $this->userManager->setUserLimit();
        }

        if ($sort) {
            $this->userManager->setSortOrder($sort);
        }

        $this->userManager->setOrder('name');
        $this->userManager->select();
        $userList = $this->userManager->get();

        $user_array = $userList->to_array();

        return $user_array;
    }

    /**
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_user_item[]
     */
    public function getUsersById($roomId, $ids) {
        $this->userManager->setContextLimit($roomId);
        $this->userManager->setIDArrayLimit($ids);

        $this->userManager->select();
        $userList = $this->userManager->get();

        return $userList->to_array();
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->userManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->userManager->setTopicLimit($relatedLabel->getItemId());
            }
        }
        
        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->userManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->userManager->setTagArrayLimit($categories);
                }
            }
        }
        
        // status
        if (isset($formData['user_status'])) {
            if ($formData['user_status'] != 'is contact') {
                $this->userManager->setStatusLimit($formData['user_status']);
            } else {
                $this->userManager->setContactModeratorLimit();
            }
        }

        if (isset($formData['user_search'])) {
            $this->userManager->setNameLimit('%'.$formData['user_search'].'%');
        }
    }

    public function getUser($userId):? \cs_user_item
    {
        $user = $this->userManager->getItem($userId);
        // hotfix for birthday strings not containing valid date strings
        if (!strtotime($user->getBirthday())) {
            $user->setBirthday("");
        }
        return $user;
    }
    
    public function getPortalUserFromSessionId()
    {
        if (isset($_COOKIE['SID'])) {
            $sid = $_COOKIE['SID'];
            
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionItem = $sessionManager->get($sid);

            if ($sessionItem) {
                $userManager = $this->legacyEnvironment->getUserManager();
                $userList = $userManager->getAllUserItemArray($sessionItem->getValue('user_id'));
                $portalUser = NULL;
                if (!empty($userList)) {
                    //$contextID = $userList[0]->getContextId();
                    //$portalUser = $userList[0];
                    foreach ($userList as $user) {
                        //if ($user->getContextId() < $contextID) {
                        //    $contextID = $user->getContextId();
                        //    $portalUser = $user;
                        //}
                        if ($user->getAuthSource() == $sessionItem->getValue('auth_source')) {
                            $portalUser = $user;
                        }
                    }
                }
                return $portalUser;
            }
        }
    }

    /**
     * Returns a array of archived room ids from userId
     * @param $userId
     * @return array
     */
    public function getArchivedRoomList($userId)
    {
        $archivedRoomManager = $this->legacyEnvironment->getZzzRoomManager();
        $archivedRoomList = $archivedRoomManager->getRelatedRoomListForUser($userId);

        return $archivedRoomList->to_array();

    }

    public function getRoomList($userId)
    {
        $roomList = $this->roomManager->getRelatedRoomListForUser($userId);
        return $roomList->to_array();
    }

    public function grantAccessToAllPendingApplications()
    {
       $this->userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
       $this->userManager->setRegisteredLimit();
       $this->userManager->select();
       $requested_user_list = $this->userManager->get();

       if (!empty($requested_user_list)){
          $requested_user = $requested_user_list->getFirst();
          while($requested_user){
             $requested_user->makeUser();
             $requested_user->save();
             $task_manager = $this->legacyEnvironment->getTaskManager();
             $task_list = $task_manager->getTaskListForItem($requested_user);
             if (!empty($task_list)){
                $task = $task_list->getFirst();
                while($task){
                   if ($task->getStatus() == 'REQUEST' and ($task->getTitle() == 'TASK_USER_REQUEST' or $task->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                      $task->setStatus('CLOSED');
                      $task->save();
                   }
                   $task = $task_list->getNext();
                }
             }
             $requested_user = $requested_user_list->getNext();
          }
       }
    }

    /**
     * Get the current user item
     * 
     * @return \cs_user_item The current user object
     */
    public function getCurrentUserItem()
    {
        return $this->legacyEnvironment->getCurrentUserItem();
    }
    
    /**
     * Returns a list of searchable rooms
     * 
     * @return array of searchable room items
     */
    public function getSearchableRooms($userItem)
    {
        // project rooms
        $projectRoomList = $userItem->getUserRelatedProjectList();

        // community rooms
        $communityRoomList = $userItem->getUserRelatedCommunityList();

        // group rooms
        $groupRoomList = $userItem->getUserRelatedGroupList();

        // merge all lists
        $searchableRoomList = $projectRoomList;
        $searchableRoomList->addList($communityRoomList);
        $searchableRoomList->addList($groupRoomList);

        return $searchableRoomList->to_array();
    }

    /**
     * @param int $contextId
     * @return \cs_user_item[]
     */
    public function getModeratorsForContext($contextId)
    {
        $this->userManager->reset();
        $this->userManager->setContextLimit($contextId);
        $this->userManager->setStatusLimit(3);
        $this->userManager->select();
        $moderatorList = $this->userManager->get();
        return $moderatorList->to_array();
    }

    public function showNoNotActivatedEntries()
    {
        $this->userManager->showNoNotActivatedEntries();
    }

    public function showUserStatus($status)
    {
        $this->userManager->setStatusLimit($status);
    }

    /**
     * @param \cs_context_item $room
     * @param \cs_user_item $currentUser
     * @return string
     */
    public function getMemberStatus($room, $currentUser)
    {
        /**
         * States: enter, join, locked, request, requested, rejected, forbidden
         */

        if ($currentUser->isRoot()) {
            return 'enter';
        } else {
            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->setUserIDLimit($currentUser->getUserID());
            $userManager->setAuthSourceLimit($currentUser->getAuthSource());
            $userManager->setContextLimit($room->getItemID());
            $userManager->select();
            $roomUserList = $userManager->get();
            $roomUser = $roomUserList->getFirst();

            if ($roomUser) {

                if ($room->mayEnter($roomUser)) {
                    return 'enter';
                }

                if ($room->isLocked()) {
                    return 'locked';
                }

                if ($roomUser->isRequested()) {
                    return 'requested';
                }

                if ($roomUser->isRejected()) {
                    return 'rejected';
                }
            } else {
                // in case of the guest user, $roomUser is null
                if ($currentUser->isReallyGuest()) {
                    return ($room->isOpenForGuests()) ? 'enter' : 'forbidden';
                }
            }
        }

        return 'join';
    }

    /**
     * Returns all users belonging to the group(s) with the given group ID(s).
     *
     * @param int $roomId The ID of the containing context
     * @param mixed $groupIds The ID (or array of IDs) for the group(s) whose users shall be returned
     * @param bool $excludeRejectedAndRegisteredUsers Whether to exclude any rejected and/or registered users
     * @return \cs_user_item[] An array of users belonging to the group(s) with the given group ID(s)
     */
    public function getUsersByGroupIds($roomId, $groupIds, $excludeRejectedAndRegisteredUsers = false)
    {
        $this->userManager->resetLimits();

        $this->userManager->setContextLimit($roomId);

        if (!is_array($groupIds)) {
            $this->userManager->setGroupArrayLimit([$groupIds]);
        } else {
            $this->userManager->setGroupArrayLimit($groupIds);
        }

        if ($excludeRejectedAndRegisteredUsers) {
            // NOTE: a status limit of `8` will cause `cs_user_manager->_performQuery()` to exclude
            // any locked/rejected users (status = 0) and registered users (status = 1)
            $this->userManager->setStatusLimit(8);
        }

        $this->userManager->setOrder('name');
        $this->userManager->select();
        $userList = $this->userManager->get();

        $user_array = $userList->to_array();

        return $user_array;
    }

    /**
     * @param \cs_user_item $user
     * @param int $roomId
     */
    public function updateAllGroupStatus($user, $roomId) {

        $userGroups = $user->getGroupList();
        if ($userGroups->isEmpty()) {
            // try to find the system group "all" for the current context
            // TODO: why is this not using the $roomId parameter instead?
            $groupManager = $this->legacyEnvironment->getLabelManager();
            $groupManager->setExactNameLimit('ALL');
            $groupManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
            $groupManager->select();
            $userGroups = $groupManager->get();

            // TODO: what is this for?
            if ($userGroups->getCount() == 1) {
                $group = $userGroups->getFirst();
                $group->setTitle('ALL');
            }

            // we found the system group
            if (isset($group)) {
                if ($user->getStatus() > 1 && !$group->isMember($user)) {
                    $group->addMember($user);
                } else {
                    if ($user->getStatus() < 2 && $group->isMember($user)) {
                        $group->removeMember($user);
                    }
                }

                $group->setModificatorItem($user);
                $group->save();
            }
        }
    }

    public function blockPossibleCommunityAccess($user, $roomId)
    {
        $legacyEnvironment = $this->legacyEnvironment;
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        $groupRooms = $roomItem->getGroupRoomList();
        $relatedRooms = $roomManager->getAllRelatedRoomListForUser($user);

        foreach($relatedRooms as $relatedRoom){
            if(in_array($relatedRoom->getItemID(), $groupRooms->getIDArray())){
                $relatedUsers = $user->getRelatedUserList();
                $roomUserList = $relatedRoom->getUserList();
                foreach($relatedUsers as $relatedUser){
                    if(in_array($relatedUser->getItemID(), $roomUserList->getIdArray())){
                        $relatedUser->reject();
                        $relatedUser->save();
                    }
                }
            }
        }
    }
}
