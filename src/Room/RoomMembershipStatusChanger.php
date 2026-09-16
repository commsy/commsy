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

use App\Utils\UserService;
use cs_user_item;

/**
 * Sets a room membership status and cascades it into the room's group rooms.
 *
 * The cascade rule is the reason this is one class rather than a line in each
 * caller: blocking always propagates, every other change propagates only when it
 * lifts a block. That way a status deliberately given inside a group room
 * survives unrelated changes in the project room, while a blocked person cannot
 * keep access through a group room — and coming back from blocked restores what
 * the block had forced to zero.
 *
 * Callers stay responsible for what follows a status change in their own
 * context: the group *label* status, announcing the change, and any mail.
 */
final readonly class RoomMembershipStatusChanger
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function changeTo(cs_user_item $user, RoomMembershipStatus $status): void
    {
        $previous = (int) $user->getStatus();

        match ($status) {
            RoomMembershipStatus::Blocked => $user->reject(),
            RoomMembershipStatus::Requested => $user->request(),
            RoomMembershipStatus::User => $user->makeUser(),
            RoomMembershipStatus::Moderator => $user->makeModerator(),
            RoomMembershipStatus::ReadOnly => $user->makeReadOnlyUser(),
        };
        $user->save();

        if (RoomMembershipStatus::Blocked === $status || RoomMembershipStatus::Blocked->value === $previous) {
            $this->userService->propagateStatusToGrouproomUsersForUser($user);
        }
    }
}
