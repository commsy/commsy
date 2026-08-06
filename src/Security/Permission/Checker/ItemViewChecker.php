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

namespace App\Security\Permission\Checker;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Room\RoomType;
use App\Security\Permission\Subject\ItemViewSubject;

/**
 * Replaces `cs_item::maySee()` with a Doctrine-only implementation.
 *
 * Five evaluation branches, in order:
 *
 *   1. The item's context (Room) is soft-deleted → false.
 *   2. The actor is `root` (status=3 + userId='root') → true.
 *   3. The actor has a non-deleted membership in the item's context
 *      with status >= 2:
 *        - activated entry → true
 *        - deactivated entry → only moderators or the creator
 *   4. The actor's username is registered as external viewer for the
 *      item (per-item allow-list, used in private rooms) → true.
 *   5. Guest fallback: the *current browsing context* is a community
 *      room with `openForGuests = true`, the entry belongs to that very
 *      room, the actor holds guest, rejected or requested status, and
 *      the entry is activated → true. Read access only.
 *
 * Else: false.
 *
 * Notes:
 *   - The "openForGuests" flag is only honored for community rooms —
 *     legacy `cs_project_item` / `cs_grouproom_item` / `cs_userroom_item`
 *     hardcode it to false. The same quirk is enforced here via an
 *     explicit RoomType check.
 *   - The actor's User may live in any context. For step 3 we re-query
 *     the actor in the item's context; the actor's own (potentially
 *     unrelated) status is only consulted for the guest fallback.
 */
final readonly class ItemViewChecker
{
    public function __construct(
        private UserRepository $userRepository,
        private ExternalViewerChecker $externalViewerChecker,
    ) {
    }

    /**
     * @param User             $actor          the actor's user_item — any
     *                                         context (portal-level for
     *                                         non-room calls, room-level
     *                                         when the legacy currentUserItem
     *                                         is in a room)
     * @param ItemViewSubject  $subject        the target item's permission view
     * @param Room|null        $currentContext the room the actor is currently
     *                                         browsing (legacy "currentContextItem"
     *                                         when it resolves to a Room).
     *                                         Only the guest fallback uses it.
     */
    public function canSee(
        User $actor,
        ItemViewSubject $subject,
        ?Room $currentContext = null,
    ): bool {
        // 1. Item's context deleted? → deny.
        if ($subject->contextIsDeleted || $subject->contextId === null) {
            return false;
        }

        // 1b. Tombstone (body replaced with placeholder text, e.g. a
        //     discussion article kept alive only for its thread node) →
        //     never visible, even to root. Mirrors the legacy
        //     `cs_file_item::maySeeLinkedItem` skip filter.
        if ($subject->hasOverwrittenContent) {
            return false;
        }

        // 2. Root short-circuit.
        if ($actor->isRoot()) {
            return true;
        }

        // 3. Membership in the item's context.
        $membership = $this->resolveMembershipInContext($actor, $subject->contextId);
        if ($membership !== null && $membership->isUser()) {
            if (!$subject->isDeactivated) {
                return true;
            }
            if ($membership->isModerator()) {
                return true;
            }
            if ($subject->creatorId !== null && $subject->creatorId === $membership->getItemId()) {
                return true;
            }
        }

        // 4. External viewer allow-list.
        if ($this->externalViewerChecker->isViewerOf($subject->itemId, $actor->getUserId())) {
            return true;
        }

        // 5. Guest fallback: the guest of a community room that is open for
        //    guests sees the activated entries *of that room*.
        //
        //    Two conditions carry weight here beyond the legacy body.
        //
        //    The context comparison: without it the branch asks only where
        //    the actor is browsing and never which context the entry belongs
        //    to, so a guest-open room would hand out every activated item of
        //    every room and every portal. Sub-entries stay reachable —
        //    sections and discussion articles carry their room as context_id,
        //    not their parent item.
        //
        //    isGuest() is status 0, which also covers someone whose join
        //    request was rejected, and isRequested() covers a pending one.
        //    Both are deliberate: the room is open to any passer-by, so
        //    having asked to join — and having been turned down — cannot
        //    leave you with less than someone who never asked. Reading is
        //    all this grants; writing runs through attributes that require
        //    membership.
        if ($currentContext !== null
            && $subject->contextId === $currentContext->getItemId()
            && $this->reachableViaGuestAccess($currentContext)
            && ($actor->isGuest() || $actor->isRequested())
            && !$subject->isDeactivated
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns the actor's membership in `$contextId`, or null if none
     * exists. If the actor's own user_item already lives in that
     * context, we reuse it without an extra query.
     */
    private function resolveMembershipInContext(User $actor, int $contextId): ?User
    {
        if ($actor->getContextId() === $contextId) {
            return $actor;
        }
        if ($actor->getAccount() === null) {
            // Legacy guests / detached user_items have no Account — they
            // cannot resolve to a different context membership.
            return null;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $contextId);
    }

    /**
     * Mirrors the legacy quirk: only community rooms honor
     * `is_open_for_guests`. Identical to
     * {@see \App\Room\RoomAccessChecker::reachableViaGuestAccess()} —
     * if more checkers grow this need we will lift it into a shared
     * helper.
     */
    private function reachableViaGuestAccess(Room $room): bool
    {
        return $room->getOpenForGuests()
            && RoomType::tryFromLegacyString($room->getType()) === RoomType::Community;
    }
}
