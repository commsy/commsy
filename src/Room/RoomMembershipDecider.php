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

use App\Event\UserStatusChangedEvent;
use App\Security\Authorization\Voter\UserVoter;
use App\Utils\UserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Decides a single room membership request — the same status change the moderator
 * list performs, reachable from one call so the notification bell can offer it
 * inline.
 *
 * The status change itself is the one in {@see RoomMembershipStatusChanger},
 * shared with the 'user-confirm' / 'user-block' arms of
 * {@see \App\Controller\UserController::changeStatus}; what remains here is the
 * decision around it: may this account decide, is there anything left to decide,
 * and announcing the outcome. Announcing is what feeds the notification
 * bookkeeping, so accepting or rejecting here clears the task for every
 * moderator and tells the requester.
 *
 * One thing the list flow does is deliberately left out: it marks the person's
 * entry read for the acting moderator. That resolves the reader against the
 * current legacy context, which in a live component is whatever room the
 * moderator is looking at — not the room being decided about.
 *
 * Authorisation is checked against the *request's own room*, not the room the
 * user happens to be in: MODERATOR is subject-blind (it asks about the current
 * context), so the bell — which spans every room of an account — has to use
 * ROOM_MODERATOR with that room as the subject.
 */
class RoomMembershipDecider
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoomMembershipStatusChanger $statusChanger,
        private readonly Security $security,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return bool true when this call decided the request, false when there was
     *              nothing left to decide (someone got there first)
     */
    public function decide(int $requesterItemId, bool $accept): bool
    {
        $user = $this->userService->getUser($requesterItemId);
        if (!$user) {
            return false;
        }

        $roomId = (int) $user->getContextID();
        if (!$this->security->isGranted(UserVoter::ROOM_MODERATOR, $roomId)) {
            throw new AccessDeniedException('Only a moderator of that room may decide its membership requests.');
        }

        if (!$user->isRequested()) {
            return false; // already decided elsewhere
        }

        $this->statusChanger->changeTo(
            $user,
            $accept ? RoomMembershipStatus::User : RoomMembershipStatus::Blocked
        );
        $this->userService->updateAllGroupStatus($user, $roomId);

        $this->eventDispatcher->dispatch(new UserStatusChangedEvent($user));

        return true;
    }
}
