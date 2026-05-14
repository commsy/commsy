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
use App\Room\RoomType;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubjectFactory;

/**
 * Doctrine-only port of `cs_user_item::maySee`.
 *
 * Users are special among items: the legacy implementation branches on
 * the *current browsing context* — community rooms have their own
 * visibility rules (driven by `user.visible` + actor guest/member
 * status), every other room type plus the portal context defer to the
 * generic item-visibility checker and then layer an extra "is the user
 * rubric switched on in this room?" gate on top.
 *
 * Mirrored 1:1 here; the only behavioural normalisation vs. the legacy
 * body is the dead-code branch removed (`if (!$room->withRubric(...))`
 * inside an else-of-the-same-condition was unreachable).
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
        if ($currentRoom !== null && $this->isCommunityRoom($currentRoom)) {
            return $this->canSeeInCommunityRoom($actor, $target, $currentRoom);
        }

        return $this->canSeeOutsideCommunityRoom($actor, $target, $currentRoom);
    }

    /**
     * Community-room branch — guest visibility is the discriminator.
     */
    private function canSeeInCommunityRoom(User $actor, User $target, Room $currentRoom): bool
    {
        if ($actor->isRoot()) {
            return true;
        }

        // Guests only see users explicitly flagged `visible = 2`.
        if ($actor->isGuest() && $target->isVisibleForAll()) {
            return true;
        }

        // Context match: viewer is in the target's home context, or in
        // the current community room context.
        $actorContext = $actor->getContextId();
        if ($actorContext !== $target->getContextId()
            && $actorContext !== $currentRoom->getItemId()
        ) {
            return false;
        }

        // Logged-in viewer — legacy `isVisibleForLoggedIn()` is hard-true.
        if ($actor->isUser() && $target->isVisibleForLoggedIn()) {
            return true;
        }

        if ($this->isSameAccountIdentity($actor, $target)) {
            return true;
        }

        return $actor->isModerator();
    }

    /**
     * Default branch: defer to {@see ItemViewChecker}, then apply the
     * room-rubric overlay (only if a Room is the browsing context).
     */
    private function canSeeOutsideCommunityRoom(User $actor, User $target, ?Room $currentRoom): bool
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

    private function isCommunityRoom(Room $room): bool
    {
        return RoomType::tryFromLegacyString($room->getType()) === RoomType::Community;
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
