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
        $roomTitle = $user->getFullName() . ' – ' . $room->getTitle();

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
            $this->userService->cloneUser($moderator, $userContext, 3);
            if (!$moderatorIsRoomOwner) {
                $moderatorIsRoomOwner = ($moderator->getItemID() === $user->getItemID());
            }
        }

        // add room owner (i.e. a regular user for the project room user who's associated with this user room)
        if (!$moderatorIsRoomOwner) {
            $this->userService->cloneUser($user, $userContext);
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
}
