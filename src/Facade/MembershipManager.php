<?php

namespace App\Facade;

use App\Entity\Account;
use App\Event\UserLeftRoomEvent;
use App\Utils\UserService;
use cs_group_item;
use cs_room_item;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MembershipManager
{
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param cs_group_item $group
     * @param Account $account
     * @return void
     */
    public function joinGroup(cs_group_item $group, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $group->getContextID());

        if ($userInWorkspace && !$group->getMemberItemList()->inList($userInWorkspace)) {
            $group->addMember($userInWorkspace);
        }
    }

    /**
     * @param cs_group_item $group
     * @param Account $account
     * @return void
     */
    public function leaveGroup(cs_group_item $group, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $group->getContextID());

        if ($userInWorkspace && $group->getMemberItemList()->inList($userInWorkspace)) {
            $group->removeMember($userInWorkspace);
        }
    }

    /**
     * @param cs_room_item $room
     * @param Account $account
     * @return void
     */
    public function leaveWorkspace(cs_room_item $room, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $room->getItemID());

        if (!$userInWorkspace || $userInWorkspace->isDeleted()) {
            return;
        }

        $userInWorkspace->delete();

        $event = new UserLeftRoomEvent($userInWorkspace, $room);
        $this->eventDispatcher->dispatch($event);
    }
}