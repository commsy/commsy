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
     * @param \cs_user_item $user the user who will be associated with the created user room
     * @return \cs_userroom_item|null the newly created user room, or null if an error occurred
     */
    public function createUserroom(\cs_room_item $room, \cs_user_item $user): ?\cs_userroom_item
    {
        // TODO: use a facade/factory to create a new user room

        $roomManager = $this->legacyEnvironment->getUserroomManager();
        $roomContext = $room->getItemID();
        $roomTitle = $user->getFullName() . ' – ' . $room->getTitle();

        /**
         * @var $newRoom \cs_userroom_item
         */
        $newRoom = $this->roomService->createRoom($roomManager, $roomContext, $roomTitle);

        $newRoom->setLinkedProjectItemID($roomContext);

        // TODO: add new users to the newly created user room
        // TODO: set `$userroom->setLinkedUserItemID` to ID of newly created regular user

        // persist room (which will also call $roomManager->saveItem())
        $newRoom->save();

        return $newRoom;
    }
}
