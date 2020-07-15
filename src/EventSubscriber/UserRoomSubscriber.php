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
        // TODO: figure out why, for a single room membership request, this callback method gets called twice

        $user = $event->getUser();
        $room = $event->getRoom();

        // only create a user room if the feature has been enabled for this room (in room settings > extensions)
        $shouldCreateUserroom = !empty($room->getUserRoom()) ? true : false;
        if (!$shouldCreateUserroom) {
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

        // TODO: delete the user room associated with $user
    }

    public function onRoomSettingsChanged(RoomSettingsChangedEvent $event)
    {
        // TODO: disable creation of user rooms if the `userRoom` setting was disabled
    }
}
