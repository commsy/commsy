<?php

namespace App\EventSubscriber;

use App\Event\RoomSettingsChangedEvent;
use App\Event\UserJoinedRoomEvent;
use App\Event\UserLeftRoomEvent;
use App\Utils\UserroomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRoomSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserroomService
     */
    private $userroomService;

    public function __construct(UserroomService $userroomService)
    {
        $this->userroomService = $userroomService;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserJoinedRoomEvent::class => 'onUserJoinedRoom',
            UserLeftRoomEvent::class => 'onUserLeftRoom',
            RoomSettingsChangedEvent::class => 'onRoomSettingsChanged',
        ];
    }

    public function onUserJoinedRoom(UserJoinedRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        // only create a user room if the feature has been enabled for this project room (in room settings > extensions)
        if (!($room->isProjectRoom() && $room->getShouldCreateUserRooms())) {
            return;
        }

        // only create a user room if there isn't already a user room for this user
        $existingUserroom = $user->getLinkedUserroomItem();
        if ($existingUserroom) {
            return;
        }

        // create a user room within $room, and create its initial users (for $user as well as all $room moderators)
        $this->userroomService->createUserroom($room, $user);
    }

    public function onUserLeftRoom(UserLeftRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        if (!$room->isProjectRoom()) {
            return;
        }

        // NOTE: a user's user room will be deleted again via cs_user_item->delete()
    }

    public function onRoomSettingsChanged(RoomSettingsChangedEvent $event)
    {
        $oldRoom = $event->getOldRoom();
        $newRoom = $event->getNewRoom();

        if (!$newRoom->isProjectRoom()) {
            return;
        }

        // if the 'CREATE_USER_ROOMS' setting was just enabled, create user rooms for all existing users
        if (!$oldRoom->getShouldCreateUserRooms() && $newRoom->getShouldCreateUserRooms()) {
            $this->userroomService->createUserroomsForRoomUsers($newRoom);
        }

        // if the room's title was just changed, rename all user rooms accordingly
        if ($oldRoom->getTitle() !== $newRoom->getTitle()) {
            $this->userroomService->renameUserroomsForRoom($newRoom);
        }
    }
}
