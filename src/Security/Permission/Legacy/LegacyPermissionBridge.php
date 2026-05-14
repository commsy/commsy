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

namespace App\Security\Permission\Legacy;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Room\RoomAccessChecker;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_room_item;
use cs_user_item;

/**
 * Single conversion seam between the legacy `cs_*_item` world and the
 * Doctrine-side permission stack.
 *
 * Before this class existed, the Phase 4 caller migration introduced
 * the same four code blocks across nine call sites in voters,
 * controllers and services:
 *
 *   1. "Convert a legacy room + legacy user item into a Doctrine
 *      ENTER verdict via the identity triple."
 *   2. "Convert a legacy room + a (room-scoped) user_item_id into a
 *      Doctrine ENTER verdict (hash-login flows)."
 *   3. "Convert a cs_user_item into its Doctrine `User` twin via the
 *      (userId, contextId, authSource) identity triple."
 *   4. "Resolve the legacy `currentContextItem` into a Doctrine `Room`,
 *      or null when it's a portal / server / guide context."
 *
 * Each call site held its own private helper for one of those four;
 * `RoomService`, `UserService`, `RoomController`, `ProjectController`,
 * `GroupController` carried near-identical *MayEnter() bodies, and
 * `ItemVoter` / `FileVoter` repeated the `currentRoom` resolution.
 *
 * Centralising this here:
 *  - eliminates ~100 LOC of boilerplate across nine classes,
 *  - lets each consumer hold a single dependency instead of three,
 *  - gives the eventual legacy-removal day a single file to delete
 *    (plus one constructor edit per consumer).
 *
 * **Boundary discipline**: the bridge wraps cs_*-shaped inputs, but
 * the underlying {@see RoomAccessChecker} / {@see UserRepository} stay
 * Doctrine-only — see Leitprinzip #5 from the permission refactor
 * plan. The bridge is the *only* place that talks to both sides.
 */
final readonly class LegacyPermissionBridge
{
    public function __construct(
        private RoomAccessChecker $roomAccessChecker,
        private UserRepository $userRepository,
        private RoomRepository $roomRepository,
        private LegacyEnvironment $legacyEnvironment,
    ) {
    }

    /**
     * Legacy parity for `cs_context_item::mayEnter($userItem)` — looks
     * up the room as a Doctrine entity, resolves the user's Account via
     * the `account_id` FK on the cs_user_item, and delegates to
     * {@see RoomAccessChecker::canEnter()}.
     *
     * Returns false when the legacy item has no Doctrine `Room` row
     * (portal / server / guide contexts) or when the cs_user_item is an
     * orphan row (no account_id — cannot enter anything).
     */
    public function userCanEnter(cs_context_item $room, cs_user_item $user): bool
    {
        if ($user->isRoot()) {
            return true;
        }
        $doctrineRoom = $this->roomFromLegacy($room);
        if ($doctrineRoom === null) {
            return false;
        }
        $account = $user->getAccount();
        if ($account === null) {
            return false;
        }
        return $this->roomAccessChecker->canEnter($account, $doctrineRoom);
    }

    /**
     * Legacy parity for `cs_context_item::mayEnterByUserItemID($id)` —
     * the hash-login flavour where the caller already holds the
     * (room-scoped) user_item_id but no `Account`/`cs_user_item`.
     * Returns false when the legacy item has no Doctrine `Room` row.
     */
    public function userItemIdCanEnter(cs_context_item $room, int $userItemId): bool
    {
        $doctrineRoom = $this->roomFromLegacy($room);
        if ($doctrineRoom === null) {
            return false;
        }
        return $this->roomAccessChecker->canEnterByUserItemId($userItemId, $doctrineRoom);
    }

    /**
     * Resolves a cs_user_item to its Doctrine `User` twin via the
     * `account_id` FK on the legacy row. Returns null for orphan rows
     * (no account_id) or when no User row exists for the (account,
     * context) tuple — caller decides what either means.
     */
    public function userFromLegacy(cs_user_item $user): ?User
    {
        $accountId = $user->getAccountID();
        if ($accountId === null) {
            return null;
        }

        return $this->userRepository->findByAccountIdAndContext(
            $accountId,
            (int) $user->getContextID(),
        );
    }

    /**
     * Returns the Doctrine `Room` twin of a legacy context item, or
     * null when the context has no Room row (portal / server / guide).
     */
    public function roomFromLegacy(cs_context_item $room): ?Room
    {
        return $this->roomRepository->find($room->getItemID());
    }

    /**
     * Resolves the legacy environment's `currentContextItem` to a
     * Doctrine `Room` when it is a room context. Returns null for
     * portal-level / non-room browsing — the new permission checkers
     * accept null and skip room-context-dependent branches.
     */
    public function currentRoom(): ?Room
    {
        $current = $this->legacyEnvironment->getEnvironment()->getCurrentContextItem();
        if (!$current instanceof cs_room_item) {
            return null;
        }
        return $this->roomRepository->find($current->getItemID());
    }
}
