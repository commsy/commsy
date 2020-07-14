<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Event\UserJoinedRoomEvent;
use App\Event\UserLeftRoomEvent;

class UserRoomSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            UserJoinedRoomEvent::class => 'onUserJoinedRoom',
            UserLeftRoomEvent::class => 'onUserLeftRoom',
        ];
    }

    public function onUserJoinedRoom(UserJoinedRoomEvent $event) {
        $user = $event->getUser() ?? null;
        $room = $event->getRoom() ?? null;

        // TODO: create a user room (cs_userroom_item) within $room, and add $user as well as all $room moderators to it
    }

    public function onUserLeftRoom(UserLeftRoomEvent $event) {
        $user = $event->getUser() ?? null;
        $room = $event->getRoom() ?? null;

        // TODO: delete the user room associated with $user
    }
}
