<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Event\UserJoinedWorkspaceEvent;
use App\Event\UserLeftWorkspaceEvent;

class UserRoomSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            UserJoinedWorkspaceEvent::class => 'onUserJoinedWorkspace',
            UserLeftWorkspaceEvent::class => 'onUserLeftWorkspace',
        ];
    }

    public function onUserJoinedWorkspace(UserJoinedWorkspaceEvent $event)
    {
        $user = $event->getUser() ?? null;
        $room = $event->getRoom() ?? null;

        // TODO: create a user room (cs_userroom_item) within $room, and add $user as well as all $room moderators to it
    }

    public function onUserLeftWorkspace(UserLeftWorkspaceEvent $event)
    {
        $user = $event->getUser() ?? null;
        $room = $event->getRoom() ?? null;

        // TODO: delete the user room associated with $user
    }
}
