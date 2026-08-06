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

namespace App\User;

use App\Entity\Room;
use App\Entity\User;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubjectFactory;

/**
 * Doctrine-only replacement for `cs_user_item::maySee`.
 *
 * A user entry belongs to a context — a room, or the portal. Seeing one
 * requires sharing that context, which is what the generic item checker
 * establishes; on top of it sits the room's own "is the user rubric
 * switched on?" gate.
 *
 * The legacy body carried a second branch for community rooms that
 * skipped the target entirely: it asked where the *actor* was and then
 * granted on `isVisibleForLoggedIn()`, which is hard-coded true. A
 * community room is a room like any other, not a portal directory, so
 * that branch is gone and community rooms take the same path as every
 * other room type. Guests keep their access through the generic
 * checker's `openForGuests` branch, which reads a setting that is
 * actually maintained — unlike the `user.visible` column the legacy
 * branch consulted, which no code path ever sets to "visible for all".
 */
final readonly class UserViewChecker
{
    public function __construct(
        private ItemViewSubjectFactory $subjectFactory,
        private ItemViewChecker $itemViewChecker,
    ) {
    }

    /**
     * @param User      $actor       the viewer (legacy `$userItem`)
     * @param User      $target      the user being inspected (legacy `$this`)
     * @param Room|null $currentRoom the browsing context — null when the
     *                               legacy currentContextItem is a Portal
     *                               (portals aren't Rooms in Doctrine).
     */
    public function canSee(User $actor, User $target, ?Room $currentRoom = null): bool
    {
        $subject = $this->subjectFactory->fromItem($target, $currentRoom);
        if (!$this->itemViewChecker->canSee($actor, $subject, $currentRoom)) {
            return false;
        }

        // No specific Room → portal-level browse → keep the access.
        if ($currentRoom === null) {
            return true;
        }

        // Private rooms and portal-like rooms always let the viewer see
        // users they could already see via the generic check. Same goes
        // when the user rubric is activated in the room's home config.
        if ($currentRoom->getType() === 'privateroom' || $this->roomHasUserRubric($currentRoom)) {
            return true;
        }

        // Otherwise the user listing is hidden — only the user themselves
        // or a moderator may see other users.
        if ($this->isSameAccountIdentity($actor, $target)) {
            return true;
        }
        return $actor->isModerator();
    }

    /**
     * Same `(userId, authSource)` pair — the legacy "is the viewer the
     * same person as the target?" check. Compared on the account_id FK
     * because the two User rows live in different contexts and therefore
     * have different item ids.
     */
    private function isSameAccountIdentity(User $actor, User $target): bool
    {
        $actorAccount = $actor->getAccount();
        $targetAccount = $target->getAccount();
        if ($actorAccount === null || $targetAccount === null) {
            return false;
        }

        return $actorAccount->getId() === $targetAccount->getId();
    }

    /**
     * Whether the Room's home configuration lists the user rubric. The
     * legacy `cs_context_item::withRubric()` does a substring match on
     * the comma-separated `HOMECONF` extras string against `'user'`.
     */
    private function roomHasUserRubric(Room $room): bool
    {
        $extras = $room->getExtras();
        if (!is_array($extras) || !isset($extras['HOMECONF'])) {
            // Without an explicit HOMECONF the legacy returns a default
            // module list — which DOES include 'user' in production.
            // Conservative match here: treat as activated.
            return true;
        }
        return stripos((string) $extras['HOMECONF'], 'user') !== false;
    }
}
