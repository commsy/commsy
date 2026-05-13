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

use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Doctrine-only port of `cs_context_item::mayEdit` restricted to room
 * contexts. Portal / server contexts (also `cs_context_item` subclasses
 * but not modelled as `Room` in Doctrine) are out of scope — the legacy
 * wrapper returns `false` for them as the safe default.
 *
 * Behaviour mirrors the legacy body 1:1 with one annotated cleanup:
 * the `isPublic()` branch is dropped because rooms have no `public`
 * column in the schema, so the check is dead in practice — it lives
 * on the `cs_*_item` types where `public` is a real column.
 *
 * Note: the legacy `mayEditRegular` companion method is NOT ported.
 * Verified across origin/10.0 … origin/10.5 plus the current branch:
 * zero callers, six+ legacy major versions of dead code. Removed as
 * part of this phase rather than mirrored.
 */
final readonly class RoomEditChecker
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Mirrors `cs_context_item::mayEdit` for a Room target.
     *
     * @param User      $actor          the viewer (legacy `$user`)
     * @param Room      $target         the room being edited
     * @param Room|null $currentContext the browsing context — only the
     *                                  community-room-over-project-room
     *                                  branch consults it.
     */
    public function canEdit(User $actor, Room $target, ?Room $currentContext = null): bool
    {
        if ($actor->isReadOnlyUser()) {
            return false;
        }
        if ($actor->isRoot()) {
            return true;
        }
        if (!$actor->isUser()) {
            return false;
        }

        $creatorId = $target->getCreator()?->getItemId();
        if ($creatorId !== null && $creatorId === $actor->getItemId()) {
            return true;
        }

        if ($this->isRoomModerator($actor, $target)) {
            return true;
        }

        // Cross-context override: a moderator who is browsing inside
        // a community room may edit any project room from there.
        // Mirrors the legacy `inCommunityRoom AND isProjectRoom AND isModerator`
        // branch.
        if ($actor->isModerator()
            && $target->getType() === 'project'
            && $currentContext !== null
            && $currentContext->getType() === 'community'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Whether the actor has a non-deleted membership with status=3
     * (moderator) in the target room. Mirrors
     * `cs_context_item::isModeratorByUserID()` semantics, but goes
     * through the Doctrine identity triple instead of iterating the
     * legacy moderator-list.
     */
    private function isRoomModerator(User $actor, Room $target): bool
    {
        $membership = $actor->getContextId() === $target->getItemId()
            ? $actor
            : $this->lookupMembership($actor, $target->getItemId());

        return $membership !== null && $membership->isModerator();
    }

    private function lookupMembership(User $actor, int $roomId): ?User
    {
        if ($actor->getAccount() === null) {
            return null;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $roomId);
    }
}
