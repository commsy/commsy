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

namespace App\Facade;

use App\Entity\Account;
use App\Event\UserLeftRoomEvent;
use App\Utils\UserService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MembershipManager
{
    public function __construct(private UserService $userService, private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function joinGroup(\cs_group_item $group, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $group->getContextID());

        if ($userInWorkspace && !$group->getMemberItemList()->inList($userInWorkspace)) {
            $group->addMember($userInWorkspace);
        }
    }

    public function leaveGroup(\cs_group_item $group, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $group->getContextID());

        if ($userInWorkspace && $group->getMemberItemList()->inList($userInWorkspace)) {
            $group->removeMember($userInWorkspace);
        }
    }

    public function leaveWorkspace(\cs_room_item $room, Account $account): void
    {
        $userInWorkspace = $this->userService->getUserInContext($account, $room->getItemID());

        if (!$userInWorkspace || $userInWorkspace->isDeleted()) {
            return;
        }

        $userInWorkspace->delete();

        $event = new UserLeftRoomEvent($userInWorkspace, $room);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Return Boolean if the last moderator.
     *
     * @param Account $account
     */
    public function isLastModerator(\cs_room_item $room, $currentUser): bool
    {
        $usersInWorkspace = $this->userService->getUserModeratorsInContext($room->getItemID());
        if ($usersInWorkspace && ($usersInWorkspace->getCount() <= 1) && ('3' === $currentUser->getStatus())) {
            return true;
        }

        return false;
    }
}
