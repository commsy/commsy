<?php

namespace App\EventSubscriber;

use App\Utils\UserroomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use App\Event\UserJoinedRoomEvent;
use App\Event\UserLeftRoomEvent;

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
        ];
    }

    public function onUserJoinedRoom(UserJoinedRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        // TODO: only create user room if there isn't already a user room for this user

        // create a user room (cs_userroom_item) within $room, and add $user as well as all $room moderators to it
        $this->userroomService->createUserroom($room, $user);
    }

    public function onUserLeftRoom(UserLeftRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        // TODO: delete the user room associated with $user
    }
}
