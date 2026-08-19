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
 * Mirrors the 'user-confirm' / 'user-block' arms of
 * {@see \App\Controller\UserController::changeStatus}: set the status, propagate
 * it to group rooms and groups, then announce it. Announcing is what feeds the
 * notification bookkeeping, so accepting or rejecting here clears the task for
 * every moderator and tells the requester.
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

        if ($accept) {
            $user->makeUser();
        } else {
            $user->reject();
        }
        $user->save();

        $this->userService->propagateStatusToGrouproomUsersForUser($user);
        $this->userService->updateAllGroupStatus($user, $roomId);

        $this->eventDispatcher->dispatch(new UserStatusChangedEvent($user));

        return true;
    }
}
