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

declare(strict_types=1);

namespace App\Room;

use App\Entity\Account;
use App\Entity\Room;
use App\Repository\UserRepository;

/**
 * Decides whether a given Account (or a known user_item id) is allowed
 * to enter a Room. Consumes only Doctrine entities and repositories —
 * no `cs_*_item`, no `LegacyEnvironment`. Replaces the
 * `cs_context_item::mayEnter*` chain in the new permission stack.
 *
 * Rules pinned by ItemVoter ENTER characterization tests:
 *
 *   1. Account.username === 'root' → always true (short-circuits even
 *      for locked or deleted rooms; the Voter top-level grants in this
 *      case anyway, but the checker also honors it for direct callers).
 *   2. Locked rooms (status 3 / LOCKED_PORTAL_MOD) → false (except root).
 *   3. Community rooms with an enabled AuthSourceGuest accept non-members.
 *      *Only* community rooms — `cs_project_item::isOpenForGuests()` and
 *      its grouproom/userroom siblings hardcode false in the legacy code,
 *      so the `is_open_for_guests` column on those room types is dead.
 *      Replicated here as an explicit type check.
 *   4. Otherwise: must have a non-deleted membership with status >= 2
 *      (`User::isUser()`).
 *
 * The checker does NOT inspect Room.deletionDate / archived / portal-id
 * collisions — those guards live in the Voter (canEnter helper). The
 * checker is the post-guard membership engine.
 */
final readonly class RoomAccessChecker
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function canEnter(Account $account, Room $room): bool
    {
        if ('root' === $account->getUsername()) {
            return true;
        }
        if ($room->isLocked()) {
            return false;
        }
        if ($this->reachableViaGuestAccess($room)) {
            return true;
        }

        $membership = $this->userRepository->findInContext($account, $room->getItemId());
        return $membership !== null && $membership->isUser();
    }

    /**
     * Identifier-only check used by callers that have a user_item id but
     * not the originating Account (e.g. RSS / iCal hash logins). Mirrors
     * `cs_context_item::mayEnterByUserItemID()` — note the legacy method
     * does NOT short-circuit for the root user_item (root lives in the
     * server context, never matches a Room id, so the membership branch
     * would always say no).
     */
    public function canEnterByUserItemId(int $userItemId, Room $room): bool
    {
        if ($room->isLocked()) {
            return false;
        }
        if ($this->reachableViaGuestAccess($room)) {
            return true;
        }

        $user = $this->userRepository->find($userItemId);
        if ($user === null) {
            return false;
        }
        if ($user->getRoom()?->getItemId() !== $room->getItemId()) {
            return false;
        }
        return $user->isUser();
    }

    /**
     * Replicates the legacy "openForGuests" reachability:
     * `cs_community_item` honors the `is_open_for_guests` column;
     * `cs_project_item` / `cs_grouproom_item` / `cs_userroom_item`
     * override the method to hardcoded false.
     */
    private function reachableViaGuestAccess(Room $room): bool
    {
        return $room->getOpenForGuests()
            && RoomType::tryFromLegacyString($room->getType()) === RoomType::Community;
    }
}
