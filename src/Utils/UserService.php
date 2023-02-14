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

namespace App\Utils;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_environment;
use cs_group_item;
use cs_grouproom_item;
use cs_label_item;
use cs_list;
use cs_manager;
use cs_room_item;
use cs_room_manager;
use cs_user_item;
use cs_user_manager;
use cs_userroom_item;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Form\FormInterface;

class UserService
{
    private cs_environment $legacyEnvironment;

    private cs_user_manager $userManager;

    /**
     * @var cs_room_manager|cs_manager|
     */
    private cs_room_manager $roomManager;

    /**
     * UserService constructor.
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RoomService $roomService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->userManager = $this->legacyEnvironment->getUserManager();
        $this->userManager->reset();

        $this->roomManager = $this->legacyEnvironment->getRoomManager();
        $this->roomManager->reset();
    }

    public function getCountArray($roomId, bool $moderation = false): array
    {
        $countUserArray = [];
        $this->userManager->setContextLimit($roomId);
        if (!$moderation) {
            $this->userManager->setUserLimit();
        }
        $this->userManager->select();
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
     * Creates a new user in the given room context based on the given source user
     * NOTE: if the room context already contains a user with identical ID, that existing user is returned.
     *
     * @param cs_user_item $sourceUser the user whose attributes shall be cloned to the new user
     * @param int           $contextID  the ID of the room which contains the created user
     * @param int           $userStatus (optional) the user status of the created user; defaults to a regular user
     * @param cs_user_item|null (optional) $creator the user who will be specified as the new user's creator; if left
     * out, the new user will be also set as his/her own creator
     *
     * @return cs_user_item|null the newly created user, or null if an error occurred
     */
    public function cloneUser(
        cs_user_item $sourceUser,
        int $contextID,
        int $userStatus = 2,
        cs_user_item $creator = null
    ): ?cs_user_item {
        // TODO: use a facade/factory to create a new room (also compare with UserCreatorFacade->addUserToRoomsWithIds())

        if (!isset($sourceUser) || empty($contextID)) {
            return null;
        }

        if ($sourceUser->isReallyGuest() || $sourceUser->isRoot()) {
            return null;
        }

        $newUser = $sourceUser->cloneData();

        $newUser->setContextID($contextID);
        $newUser->setStatus($userStatus);

        $this->cloneUserPicture($sourceUser, $newUser);

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($contextID);
        if ($roomItem->getAGBStatus()) {
            $newUser->setAGBAcceptanceDate(new DateTimeImmutable());
        }

        if ($this->legacyEnvironment->getCurrentPortalItem()->getConfigurationHideMailByDefault()) {
            $newUser->setEmailNotVisible();
        }

        if ($creator) {
            $newUser->setCreatorItem($creator);
        }

        // TODO: set modification date?

        // check if the user ID already exists within the room
        $existingUser = $newUser->getRelatedUserItemInContext($contextID);
        if ($existingUser) {
            return $existingUser;
        }

        $newUser->save();

        if (!$creator) {
            $newUser->setCreatorID2ItemID();
        }

        // link user with group "all"
        $this->addUserToSystemGroupAll($newUser, $roomItem);

        return $newUser;
    }

    /**
     * Copies the source user's picture to the given target user, and returns the target user.
     *
     * @param cs_user_item $sourceUser the user whose picture shall be copied to the target user
     * @param cs_user_item $targetUser the user whose picture will be set to the picture of the source user
     *
     * @return cs_user_item|null the target user whose picture has been set, or null if the source user had no picture
     */
    public function cloneUserPicture(cs_user_item $sourceUser, cs_user_item $targetUser): ?cs_user_item
    {
        $userPicture = $sourceUser->getPicture(); // example userPicture value: "cid123_jdoe_Jon-Doe-01.jpg"
        if (empty($userPicture)) {
            return null;
        }

        $values = explode('_', $userPicture);
        $values[0] = 'cid'.$targetUser->getContextID();

        $userPictureName = implode('_', $values);

        $discManager = $this->legacyEnvironment->getDiscManager();
        $discManager->copyImageFromRoomToRoom($userPicture, $targetUser->getContextID());
        $targetUser->setPicture($userPictureName);

        return $targetUser;
    }

    /**
     * Links the given user with the given room's system group "All" and returns that group.
     *
     * @param cs_user_item $user the user who shall be linked to the system group "All"
     * @param cs_room_item $room the room whose system group "All" shall be used
     *
     * @return cs_label_item|null the system group "All" to which the given user was added, or null if an error occurred
     */
    public function addUserToSystemGroupAll(cs_user_item $user, cs_room_item $room): ?cs_label_item
    {
        $groupManager = $this->legacyEnvironment->getLabelManager();
        $groupManager->setExactNameLimit('ALL');
        $groupManager->setContextLimit($room->getItemID());
        $groupManager->select();
        $groupList = $groupManager->get();

        /** @var cs_group_item $group */
        $systemGroupAll = $groupList->getFirst();

        if ($systemGroupAll) {
            // if a DriverException occurs, it should be investigated, why a user cannot be added to group all
            $systemGroupAll->addMember($user);

            return $systemGroupAll;
        }

        return null;
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return cs_user_item[]
     */
    public function getListUsers(
        $roomId,
        $max = null,
        $start = null,
        $moderation = false,
        $sort = null,
        $resetLimits = true
    ): array {
        if ($resetLimits) {
            $this->userManager->reset();
            $this->userManager->resetLimits();
        }

        $this->userManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
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
     * @param int   $roomId
     * @param int[] $ids
     *
     * @return cs_user_item[]
     */
    public function getUsersById($roomId, $ids)
    {
        $this->resetLimits();
        $this->userManager->setContextLimit($roomId);
        $this->userManager->setIDArrayLimit($ids);

        $this->userManager->select();
        $userList = $this->userManager->get();

        return $userList->to_array();
    }

    public function getPortalUser(Account $account): cs_user_item
    {
        $this->userManager->resetLimits();
        $this->userManager->setContextLimit($account->getContextId());
        $this->userManager->setUserIDLimit($account->getUsername());
        $this->userManager->setAuthSourceLimit($account->getAuthSource()->getId());
        $this->userManager->select();
        $userList = $this->userManager->get();

        if (1 !== $userList->getCount()) {
            throw new LogicException();
        }

        /** @var cs_user_item $user */
        $user = $userList->getFirst();

        return $user;
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
            if ('is contact' != $formData['user_status']) {
                $this->userManager->setStatusLimit($formData['user_status']);
            } else {
                $this->userManager->setContactModeratorLimit();
            }
        }

        if (isset($formData['user_search'])) {
            $this->userManager->setNameLimit('%'.$formData['user_search'].'%');
        }
    }

    public function getUser($userId): ?cs_user_item
    {
        $user = $this->userManager->getItem($userId);
        // hotfix for birthday strings not containing valid date strings
        if (!is_null($user) && !strtotime($user->getBirthday())) {
            $user->setBirthday('');
        }

        return $user;
    }

    public function getUserInContext(Account $account, int $contextId): ?cs_user_item
    {
        $this->userManager->resetLimits();
        $this->userManager->setContextLimit($contextId);
        $this->userManager->setUserIDLimit($account->getUsername());
        $this->userManager->setAuthSourceLimit($account->getAuthSource()->getId());
        $this->userManager->select();

        $userList = $this->userManager->get();
        if ($userList && 1 == $userList->getCount()) {
            /* @noinspection PhpIncompatibleReturnTypeInspection */
            return $userList->getFirst();
        }

        return null;
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

        if (!empty($requested_user_list)) {
            $requested_user = $requested_user_list->getFirst();
            while ($requested_user) {
                $requested_user->makeUser();
                $requested_user->save();
                $task_manager = $this->legacyEnvironment->getTaskManager();
                $task_list = $task_manager->getTaskListForItem($requested_user);
                if (!empty($task_list)) {
                    $task = $task_list->getFirst();
                    while ($task) {
                        if ('REQUEST' == $task->getStatus() and ('TASK_USER_REQUEST' == $task->getTitle() or 'TASK_PROJECT_MEMBER_REQUEST' == $task->getTitle())) {
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
     * Get the current user item.
     *
     * @return cs_user_item The current user object
     */
    public function getCurrentUserItem()
    {
        return $this->legacyEnvironment->getCurrentUserItem();
    }

    /**
     * Returns a list of searchable rooms.
     *
     * @return array of searchable room items, may be empty
     */
    public function getSearchableRooms(cs_user_item $userItem)
    {
        // project rooms
        $projectRoomList = $userItem->getUserRelatedProjectList();

        // community rooms
        $communityRoomList = $userItem->getUserRelatedCommunityList();

        // group rooms
        $groupRoomList = $userItem->getUserRelatedGroupList();

        // user rooms
        $userRoomList = $userItem->getRelatedUserroomsList();
        foreach ($userRoomList as $userRoom) {
            /** @var cs_userroom_item $userRoom */
            // we only want to add a user room, if the option is enabled in the project room
            $parentProjectRoom = $userRoom->getLinkedProjectItem();
            if (null !== $parentProjectRoom &&
                false === $parentProjectRoom->getShouldCreateUserRooms()
            ) {
                $userRoomList->removeElement($userRoom);
            }
        }

        // merge all lists
        $searchableRoomList = $projectRoomList;
        $searchableRoomList->addList($communityRoomList);
        $searchableRoomList->addList($groupRoomList);
        $searchableRoomList->addList($userRoomList);

        // add private room
        $privateRoomItem = $userItem->getOwnRoom();
        if ($privateRoomItem) {
            $searchableRoomList->add($privateRoomItem);
        }

        return $searchableRoomList->to_array();
    }

    /**
     * Returns the moderators of the context with the given ID, optionally ignoring all moderators
     * whose ID is contained in $ignoredUserIds.
     *
     * @param int   $contextId      The ID of the context whose moderators shall be returned
     * @param int[] $ignoredUserIds (optional) IDs of user items that (if present) shall be omitted
     *                              from the returned array of moderators
     *
     * @return cs_user_item[]
     */
    public function getModeratorsForContext(int $contextId, array $ignoredUserIds = []): array
    {
        $this->userManager->reset();
        $this->userManager->setContextLimit($contextId);
        $this->userManager->setStatusLimit(3);
        $this->userManager->select();
        $moderatorList = $this->userManager->get();

        $moderators = $moderatorList->to_array();
        if (!empty($ignoredUserIds)) {
            $moderators = array_filter($moderators, fn (cs_user_item $user) => !in_array($user->getItemID(), $ignoredUserIds));
        }

        return $moderators;
    }

    /**
     * Checks whether the context with the given ID has moderators other than the moderators whose IDs
     * are contained in $ignoredUserIds.
     *
     * @param int   $contextId      The ID of the context whose moderators shall be checked
     * @param int[] $ignoredUserIds (optional) IDs of user items that shall be ignored
     *
     * @return bool Whether the context has moderators with IDs other than the ones in $ignoredUserIds (true), or not (false)
     */
    public function contextHasModerators(int $contextId, array $ignoredUserIds = []): bool
    {
        $remainingModerators = $this->getModeratorsForContext($contextId, $ignoredUserIds);

        return !empty($remainingModerators);
    }

    /**
     * Returns all group rooms of the given project room which have no other moderators than the ones identified by
     * the IDs given in $userIds.
     *
     * @param cs_room_item   $room  The room whose group rooms shall be checked
     * @param cs_user_item[] $users (optional) User items that shall be ignored when checking rooms for additional moderators
     *
     * @return cs_grouproom_item[]
     */
    public function grouproomsWithoutOtherModeratorsInRoom(cs_room_item $room, array $users = []): array
    {
        if (!$room->isProjectRoom()) {
            return [];
        }

        $groupRooms = $room->getGroupRoomList();
        $orphanedGroupRooms = [];
        $userIds = [];

        foreach ($groupRooms as $groupRoom) {
            // for each user, gather item IDs for all corresponding group room users
            foreach ($users as $user) {
                $groupRoomUser = $user->getRelatedUserItemInContext($groupRoom->getItemID());
                if ($groupRoomUser) {
                    $userIds[] = $groupRoomUser->getItemID();
                }
            }

            // find group rooms which don't have any other moderators than those identified by IDs in $userIds
            if (!$this->contextHasModerators($groupRoom->getItemID(), $userIds)) {
                $orphanedGroupRooms[] = $groupRoom;
            }
        }

        return $orphanedGroupRooms;
    }

    /**
     * Checks whether the given (or otherwise the current) user is the given room's last moderator.
     *
     * @param cs_room_item|null $room The room for which this method will check whether the given user is its last moderator
     * @param cs_user_item|null $user (optional) The user for whom this method will check whether (s)he is the given
     *                                 room's last moderator (defaults to the current user if not given)
     *
     * @return bool Whether the given (or current) user is the last moderator in the given room (true), or not (false)
     */
    public function userIsLastModeratorForRoom(?cs_room_item $room, ?cs_user_item $user = null): bool
    {
        if (!$room) {
            return false;
        }

        $user ??= $this->legacyEnvironment->getCurrentUserItem();
        if (!$user) {
            return false;
        }

        $roomModeratorIds = $this->getIdsForUsers($room->getModeratorList()->to_array());
        $userIds = $this->getIdsForUsers($user->getRelatedUserList()->to_array());

        // also check the given/current user's own item ID
        $userIds[] = $user->getItemID();

        $userIsLastModerator = (1 == count($roomModeratorIds)) && (count(array_intersect($userIds, $roomModeratorIds)) > 0);

        return $userIsLastModerator;
    }

    /**
     * Checks whether the given (or otherwise the current) user is among the moderators of the given room.
     *
     * @param int                $room The room for which this method will check whether the given user is among its moderators
     * @param cs_user_item|null $user (optional) The user for whom this method will check whether (s)he is among the
     *                                 specified room's moderators (defaults to the current user if not given)
     *
     * @return bool Whether the given (or current) user is among the moderators of the specified room (true), or not (false)
     */
    public function userIsModeratorForRoom(cs_room_item $room, ?cs_user_item $user = null): bool
    {
        $user ??= $this->legacyEnvironment->getCurrentUserItem();
        if (!$user) {
            return false;
        }

        $roomModeratorIds = $this->getIdsForUsers($this->getModeratorsForContext($room->getItemId()));
        $userIds = $this->getIdsForUsers($user->getRelatedUserList()->to_array());

        // also check the given/current user's own item ID
        $userIds[] = $user->getItemID();

        $userIsModerator = (count(array_intersect($userIds, $roomModeratorIds)) > 0);

        return $userIsModerator;
    }

    /**
     * Checks whether the given (or otherwise the current) user is among the "parent" moderators of the given room.
     * Parent moderators considered by this method are:
     * - the root user
     * - the portal moderator
     * - any moderator of a community room that hosts the given (project) room
     * - any moderator of a project room whose group is linked to the given (group) room.
     * - any group creator whose group is linked to the given (group) room.
     *
     * @param cs_room_item      $room The room for which this method will check whether the given user is among its parent moderators
     * @param cs_user_item|null $user (optional) The user for whom this method will check whether (s)he is among the parent
     *                                 moderators of the specified room (defaults to the current user if not given)
     *
     * @return bool Whether the given (or current) user is among the parent moderators of the specified room (true), or not (false)
     */
    public function userIsParentModeratorForRoom(cs_room_item $room, ?cs_user_item $user = null): bool
    {
        $user ??= $this->legacyEnvironment->getCurrentUserItem();
        if (!$user) {
            return false;
        }

        // root user & portal moderator are considered as "parent" moderators
        if ($user->isRoot()) {
            return true;
        }

        $portalUser = $user->getRelatedPortalUserItem();
        if ($portalUser && $portalUser->isModerator()) {
            return true;
        }

        $roomType = $room->getType();
        if (CS_PROJECT_TYPE === $roomType) {
            // check if the given user corresponds to a moderator in a community room that hosts the given project room
            $communityRooms = $this->roomService->getCommunityRoomsForRoom($room);
            foreach ($communityRooms as $communityRoom) {
                if ($this->userIsModeratorForRoom($communityRoom, $user)) {
                    return true;
                }
            }
        } else {
            if (CS_GROUPROOM_TYPE === $roomType) {
                // check if the given user is a moderator of the project room that hosts the given group room
                $projectRoom = $room->getLinkedProjectItem();
                if ($projectRoom && $this->userIsModeratorForRoom($projectRoom, $user)) {
                    return true;
                }

                // check if the given user is the creator of the group that's linked to the given group room
                $group = $room->getLinkedGroupItem();
                if ($group) {
                    $groupCreator = $group->getCreatorItem();
                    if ($groupCreator && $groupCreator->getItemID() === $user->getItemID()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns the IDs of all given users.
     *
     * @param cs_user_item[] $users The array of users whose IDs shall be returned
     *
     * @return int[]
     */
    public function getIdsForUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $userIds = array_map(fn (cs_user_item $user) => $user->getItemID(), $users);

        return $userIds;
    }

    public function hideDeactivatedEntries()
    {
        $this->userManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    public function showUserStatus($status)
    {
        $this->userManager->setStatusLimit($status);
    }

    /**
     * @param cs_context_item $room
     * @param cs_user_item    $currentUser
     *
     * @return string
     */
    public function getMemberStatus($room, $currentUser)
    {
        /*
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
     * @param int   $roomId                            The ID of the containing context
     * @param mixed $groupIds                          The ID (or array of IDs) for the group(s) whose users shall be returned
     * @param bool  $excludeRejectedAndRegisteredUsers Whether to exclude any rejected and/or registered users
     *
     * @return cs_user_item[] An array of users belonging to the group(s) with the given group ID(s)
     */
    public function getUsersByGroupIds($roomId, mixed $groupIds, $excludeRejectedAndRegisteredUsers = false)
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
     * @param cs_user_item $user
     * @param int           $roomId
     */
    public function updateAllGroupStatus($user, $roomId)
    {
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
            if (1 == $userGroups->getCount()) {
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

    /**
     * Takes all group room users corresponding to the given project room user and sets their status according
     * to the project room user's current status.
     *
     * @param cs_user_item $user The project room user whose status shall be applied to corresponding group room
     *                            users within the user's project room
     */
    public function propagateStatusToGrouproomUsersForUser(cs_user_item $user): void
    {
        $roomItem = $user->getContextItem();
        if (!$roomItem->isProjectRoom()) {
            return;
        }

        $groupRooms = $roomItem->getGroupRoomList();
        if ($groupRooms->isEmpty()) {
            return;
        }

        $userStatus = $user->getStatus();

        foreach ($groupRooms as $groupRoom) {
            $groupRoomUser = $user->getRelatedUserItemInContext($groupRoom->getItemID());
            if ($groupRoomUser) {
                switch ($userStatus) {
                    case 0:
                        $groupRoomUser->reject();
                        $groupRoomUser->save();
                        break;

                    case 1:
                        $groupRoomUser->request();
                        $groupRoomUser->save();
                        break;

                    case 4:
                        $groupRoomUser->makeReadOnlyUser();
                        $groupRoomUser->save();
                        break;

                    case 2:
                        $groupRoomUser->makeUser();
                        $groupRoomUser->save();
                        break;

                    case 3:
                        $groupRoomUser->makeModerator();
                        $groupRoomUser->save();
                        break;
                }
            }
        }
    }

    /**
     * @param int $contextId
     * @return cs_user_item|null
     */
    public function getUserModeratorsInContext(int $contextId): ?cs_list
    {
        $this->userManager->resetLimits();
        $this->userManager->setContextLimit($contextId);
        $this->userManager->setStatusLimit(3);
        $this->userManager->select();

        return $this->userManager->get();
    }
}
