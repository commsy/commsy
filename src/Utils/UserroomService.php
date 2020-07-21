<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;

/**
 * Implements services for user rooms
 *
 * A user room gets used inside project rooms for bilateral exchange between a single user and the room's moderators
 */
class UserroomService
{
    private $legacyEnvironment;

    /**
     * @var RoomService
     */
    private $roomService;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->userService = $userService;
    }

    /**
     * Creates a new user room within the given project room for the given user
     * @param \cs_room_item $room the project room that will host the created user room
     * @param \cs_user_item $user the project room user who will be associated with the created user room
     * @return \cs_userroom_item|null the newly created user room, or null if an error occurred
     */
    public function createUserroom(\cs_room_item $room, \cs_user_item $user): ?\cs_userroom_item
    {
        // TODO: use a facade/factory to create a new user room

        $roomManager = $this->legacyEnvironment->getUserroomManager();
        $roomTitle = $this->defaultUserroomTitle($room, $user);

        // NOTE: for user rooms, the context item is the project room that hosts the user room (not the portal item)
        $roomContext = $room->getItemID();

        /**
         * @var $newRoom \cs_userroom_item
         */
        $newRoom = $this->roomService->createRoom($roomManager, $roomContext, $roomTitle);
        if (!$newRoom) {
            return null;
        }

        $newRoom->setLinkedProjectItemID($roomContext);

        $linkedUserID = $user->getItemID();
        $newRoom->setLinkedUserItemID($linkedUserID);

        // persist room (which will also call $roomManager->saveItem())
        $newRoom->save();

        // update project room user
        $user->setLinkedUserroomItemID($newRoom->getItemID());
        $user->save();

        // add room moderators
        $userContext = $newRoom->getItemID();
        $moderatorIsRoomOwner = false;
        $roomModerators = $this->userService->getModeratorsForContext($roomContext);
        foreach ($roomModerators as $moderator) {
            $userroomModerator = $this->userService->cloneUser($moderator, $userContext, 3);
            $userroomModerator->setLinkedProjectUserItemID($moderator->getItemID());
            $userroomModerator->save();

            if (!$moderatorIsRoomOwner) {
                $moderatorIsRoomOwner = ($moderator->getItemID() === $user->getItemID());
            }
        }

        // add room owner (i.e. a regular user for the project room user who's associated with this user room)
        if (!$moderatorIsRoomOwner) {
            $userroomOwner = $this->userService->cloneUser($user, $userContext);
            $userroomOwner->setLinkedProjectUserItemID($user->getItemID());
            $userroomOwner->save();
        }

        return $newRoom;
    }

    /**
     * Creates new user rooms within the given project room for all users who don't have user rooms yet
     * @param \cs_room_item $room the project room for which user rooms shall be created for all its existing users
     */
    public function createUserroomsForRoomUsers(\cs_room_item $room)
    {
        $roomUsers = $this->userService->getListUsers($room->getItemID(), null, null, true);
        foreach ($roomUsers as $user) {
            // only create a user room if there isn't already a user room for this user
            $existingUserroom = $user->getLinkedUserroomItem();
            if ($existingUserroom) {
                continue;
            }

            // create a user room within $room, and create its initial users (for $user as well as all $room moderators)
            $this->createUserroom($room, $user);
        }
    }

    /**
     * Updates the room titles of all user rooms of the given project room so that they include the project room's current title
     * @param \cs_room_item $room the project room whose user rooms shall be updated
     */
    public function renameUserroomsForRoom(\cs_room_item $room)
    {
        $roomUsers = $this->userService->getListUsers($room->getItemID(), null, null, true);
        foreach ($roomUsers as $user) {
            $existingUserroom = $user->getLinkedUserroomItem();
            if (!$existingUserroom) {
                continue;
            }

            $userroomTitle = $this->defaultUserroomTitle($room, $user);
            if ($existingUserroom->getTitle() !== $userroomTitle) {
                $existingUserroom->setTitle($userroomTitle);
                $existingUserroom->save();
            }
        }
    }

    /**
     * Updates the user status of the given user for its related users in all user rooms of the given project room
     * @param \cs_room_item $room the project room whose user rooms shall be updated
     * @param \cs_user_item $changedUser the project room user whose related user room users shall be updated
     */
    public function changeUserStatusInUserroomsForRoom($room, $changedUser)
    {
        $changedUserStatus = $changedUser->getStatus();

        // for all project room users with user rooms, update their user room users
        $projectUsers = $this->userService->getListUsers($room->getItemID(), null, null, true);
        foreach ($projectUsers as $projectUser) {
            // NOTE: a user room contains a single regular user (who "owns" this user room), plus one or more moderators
            $userroom = $projectUser->getLinkedUserroomItem();
            if (!$userroom) {
                continue;
            }

            // get the project room user who's associated with this user room
            $projectUserRelatedToUserroom = $userroom->getLinkedUserItem();
            // is this user room the $changedUser's own roon?
            $userroomBelongsToChangedUser = $projectUserRelatedToUserroom !== null && $projectUserRelatedToUserroom->getItemID() === $changedUser->getItemID();

            // does this user room contain a user who corresponds to (i.e., represents) the $changedUser?
            $changedUserHasRelatedUserroomUser = false;

            $userroomUsers = $this->userService->getListUsers($userroom->getItemID(), null, null, true);
            foreach ($userroomUsers as $userroomUser) {

                // get the project room user who corresponds to (i.e., represents) this user room user
                $projectUserRelatedToUserroomUser = $userroomUser->getLinkedProjectUserItem();

                // act on the user room owner
                $ownsUserroom = false;
                if ($projectUserRelatedToUserroomUser && $projectUserRelatedToUserroom) {
                    $ownsUserroom = $projectUserRelatedToUserroomUser->getItemID() === $projectUserRelatedToUserroom->getItemID();
                }
                if ($ownsUserroom) {
                    // for the user room of the $changedUser, update the user room owner's status
                    if ($userroomBelongsToChangedUser) {
                        $userroomUser->setStatus($changedUserStatus);
                        $userroomUser->save();
                    }
                }

                $userroomUserIsRelatedToChangedUser = $projectUserRelatedToUserroomUser !== null && $projectUserRelatedToUserroomUser->getItemID() === $changedUser->getItemID();
                if ($userroomUserIsRelatedToChangedUser) {
                    $changedUserHasRelatedUserroomUser = true;

                    // remove this user room moderator if the project room user who's related to this user room moderator
                    // isn't a project room moderator anymore
                    if (!$ownsUserroom && $userroomUser->isModerator() && !$changedUser->isModerator()) {
                        $userroomUser->delete();
                    }
                }
            }

            // in case the $changedUser is a moderator, add a corresponding user room moderator if there isn't already one
            if ($changedUser->isModerator() && !$changedUserHasRelatedUserroomUser) {
                $userroomModerator = $this->userService->cloneUser($changedUser, $userroom->getItemID(), 3);
                $userroomModerator->setLinkedProjectUserItemID($changedUser->getItemID());
                $userroomModerator->save();
            }
        }
    }

    /**
     * Removes all users related to the given user from all user rooms of the given project room
     * @param \cs_room_item $room the project room whose associated user rooms shall be purged
     * @param \cs_user_item $deletedUser the project room user whose related user room users shall be deleted
     */
    public function removeUserFromUserroomsForRoom(\cs_room_item $room, \cs_user_item $deletedUser)
    {
        $projectUsers = $this->userService->getListUsers($room->getItemID(), null, null, true);
        foreach ($projectUsers as $projectUser) {
            $userroom = $projectUser->getLinkedUserroomItem();
            if (!$userroom) {
                continue;
            }

            $userroomUsers = $this->userService->getListUsers($userroom->getItemID(), null, null, true);
            foreach ($userroomUsers as $userroomUser) {

                // get the project room user who corresponds to (i.e., represents) this user room user
                $projectUserRelatedToUserroomUser = $userroomUser->getLinkedProjectUserItem();

                // remove this user room user if the project room user who's related to this user room user was deleted
                $userroomUserIsRelatedToDeletedUser = $projectUserRelatedToUserroomUser->getItemID() === $deletedUser->getItemID();
                if ($userroomUserIsRelatedToDeletedUser) {
                    $userroomUser->delete();
                }
            }
        }
    }

    /**
     * Returns the default room title for a user room to be created for the given room and user
     * @param \cs_room_item $room the project room that would host the created user room
     * @param \cs_user_item $user the project room user who would be associated with the created user room
     * @return string the user room's suggested default title
     */
    private function defaultUserroomTitle(\cs_room_item $room, \cs_user_item $user): string
    {
        $roomTitle = $user->getFullName() . ' – ' . $room->getTitle();

        return $roomTitle;
    }
}
