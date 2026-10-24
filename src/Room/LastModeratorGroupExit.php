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

namespace App\Room;

use App\Entity\Account;
use App\Utils\GroupService;
use App\Utils\UserService;
use cs_grouproom_item;
use cs_user_item;

/**
 * Leaving a group as the last moderator of its group room: the group room is
 * closed together with its group instead.
 *
 * The exit applies only if the group belongs to the given project room, the
 * group and its group room point at each other (`GROUP_ROOM_ID` /
 * `GROUP_ITEM_ID`) and the account is the sole moderator of that group room.
 * {@see execute()} re-checks all of it and deletes nothing otherwise.
 */
readonly class LastModeratorGroupExit
{
    public function __construct(
        private GroupService $groupService,
        private UserService $userService,
        private RoomDeleterRegistry $roomDeleterRegistry,
    ) {
    }

    public function applies(int $projectRoomId, int $groupId, Account $account): bool
    {
        return null !== $this->resolve($projectRoomId, $groupId, $account);
    }

    /**
     * Soft-deletes the group room of the group; the room deleter takes the
     * group along. Returns false, without deleting anything, if the exit does
     * not apply.
     */
    public function execute(int $projectRoomId, int $groupId, Account $account): bool
    {
        $resolved = $this->resolve($projectRoomId, $groupId, $account);
        if (null === $resolved) {
            return false;
        }

        [$groupRoom, $user] = $resolved;

        $this->roomDeleterRegistry->softDeleteLegacyRoom(
            $groupRoom,
            (int) $user->getItemID(),
            RoomDeletionOptions::forUserAction()
        );

        return true;
    }

    /**
     * @return array{cs_grouproom_item, cs_user_item}|null
     */
    private function resolve(int $projectRoomId, int $groupId, Account $account): ?array
    {
        $group = $this->groupService->getGroup($groupId);
        if (null === $group || $group->isDeleted() || (int) $group->getContextID() !== $projectRoomId) {
            return null;
        }

        // Null for a deleted group room as well.
        $groupRoom = $group->getGroupRoomItem();
        if (null === $groupRoom || RoomType::GroupRoom->value !== $groupRoom->getType()) {
            return null;
        }

        if ((int) $groupRoom->getLinkedGroupItemID() !== $groupId) {
            return null;
        }

        $user = $this->userService->getUserInContext($account, (int) $groupRoom->getItemID());
        if (null === $user || !$user->isModerator()) {
            return null;
        }

        // Sole moderator: the one moderator of the group room is this user.
        $moderators = $this->userService->getUserModeratorsInContext((int) $groupRoom->getItemID());
        $soleModerator = $moderators->getFirst();
        if (1 !== $moderators->getCount() || !$soleModerator || (int) $soleModerator->getItemID() !== (int) $user->getItemID()) {
            return null;
        }

        return [$groupRoom, $user];
    }
}
