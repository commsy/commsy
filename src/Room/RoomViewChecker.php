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
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubjectFactory;

/**
 * Doctrine-only port of the per-room-type `maySee` overrides.
 *
 * The legacy hierarchy carries custom `maySee` only on three room
 * subclasses; community and private rooms inherit the generic
 * `cs_item::maySee` body. We dispatch on {@see RoomType} so each
 * branch's semantics stay isolated:
 *
 *   - Project room                    — `cs_project_item::maySee`
 *   - Group room (`grouproom`)        — `cs_grouproom_item::maySee`
 *   - User room (`userroom`)          — `cs_userroom_item::maySee`
 *   - Community / Private / unknown   — fall through to
 *                                       {@see ItemViewChecker}
 */
final readonly class RoomViewChecker
{
    public function __construct(
        private ItemViewChecker $itemViewChecker,
        private ItemViewSubjectFactory $subjectFactory,
        private RoomRepository $roomRepository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @param User      $actor          the viewer
     * @param Room      $target         the room being inspected
     * @param Room|null $currentContext the browsing context (legacy
     *                                  `currentContextItem`); null when
     *                                  the viewer is at portal level.
     */
    public function canSee(User $actor, Room $target, ?Room $currentContext = null): bool
    {
        $type = RoomType::tryFromLegacyString($target->getType());

        return match ($type) {
            RoomType::Project   => $this->canSeeProjectRoom($actor, $currentContext),
            RoomType::GroupRoom => $this->canSeeGroupRoom($actor, $target, $currentContext),
            RoomType::UserRoom  => $this->canSeeUserRoom($actor, $target),
            // Community + PrivateRoom: no legacy override → defer to
            // the generic item checker.
            default => $this->canSeeViaItemChecker($actor, $target, $currentContext),
        };
    }

    /**
     * Project rooms — mirrors `cs_project_item::maySee`:
     *
     *   if ($user->isRoot()
     *       || ($user->getContextID() == $env->getCurrentContextID()
     *           && ($user->isGuest() || $user->isUser()))
     *       || $contextItem->isOpenForGuests())
     *
     * Two practical consequences worth pinning:
     *
     *   - The `contextID == currentContextID` branch is effectively
     *     "the actor is a guest/user in the current browsing context".
     *     The legacy `LegacySubscriber` resolves `currentUserItem` IN
     *     the current context, so the actor's `getContextId()` is the
     *     current context by construction — for both room-level and
     *     portal-level browses. This is what grants any logged-in
     *     portal user the right to SEE project rooms in their portal
     *     dashboard.
     *
     *   - The `openForGuests` flag is read off the *current browsing
     *     context*, not the target room. Replicated here.
     */
    private function canSeeProjectRoom(User $actor, ?Room $currentContext): bool
    {
        if ($actor->isRoot()) {
            return true;
        }
        if ($actor->isGuest() || $actor->isUser()) {
            // Legacy "user is in the current context" branch. The actor
            // is whatever `LegacySubscriber` resolved as `currentUserItem`,
            // which always lives in the current browsing context — so we
            // just need a non-zero status. Trusting this short-circuit
            // keeps portal-level room listings visible to portal users
            // (`/portal/X/room/Y` dashboard), matching legacy.
            return true;
        }
        return $currentContext !== null && $currentContext->getOpenForGuests();
    }

    /**
     * Group rooms — mirrors `cs_grouproom_item::maySee`. Two grant
     * paths: viewer is in the linked project room (with the open-for-
     * guests caveat for guest viewers), or viewer is browsing in a
     * private-room context and is a member of the group room.
     */
    private function canSeeGroupRoom(User $actor, Room $target, ?Room $currentContext): bool
    {
        if ($actor->isRoot()) {
            return true;
        }

        $linkedProjectId = $this->extractIntExtra($target, 'PROJECT_ROOM_ITEM_ID');
        if ($linkedProjectId !== null && $actor->getContextId() === $linkedProjectId) {
            if ($actor->isUser()) {
                return true;
            }
            if ($actor->isGuest()) {
                $linkedProject = $this->roomRepository->find($linkedProjectId);
                if ($linkedProject !== null && $linkedProject->getOpenForGuests()) {
                    return true;
                }
            }
        }

        if ($currentContext !== null && $currentContext->getType() === 'privateroom') {
            if ($this->actorIsMemberOf($actor, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * User rooms — mirrors `cs_userroom_item::maySee`. Three grants:
     * root, any moderator (legacy is intentionally context-free here),
     * and the user whose room this is (linked via the `USER_ITEM_ID`
     * extra).
     */
    private function canSeeUserRoom(User $actor, Room $target): bool
    {
        if ($actor->isRoot() || $actor->isModerator()) {
            return true;
        }

        $linkedUserId = $this->extractIntExtra($target, 'USER_ITEM_ID');
        return $linkedUserId !== null && $linkedUserId === $actor->getItemId();
    }

    private function canSeeViaItemChecker(User $actor, Room $target, ?Room $currentContext): bool
    {
        $subject = $this->subjectFactory->fromItem($target, $currentContext);
        return $this->itemViewChecker->canSee($actor, $subject, $currentContext);
    }

    private function extractIntExtra(Room $room, string $key): ?int
    {
        $extras = $room->getExtras();
        if (!is_array($extras) || !isset($extras[$key])) {
            return null;
        }
        return (int) $extras[$key];
    }

    /**
     * Whether the actor has a (non-deleted) membership in the given
     * room. Replicates `cs_context_item::isUser($user)` — userId +
     * authSource match in the room's context.
     */
    private function actorIsMemberOf(User $actor, Room $room): bool
    {
        if ($actor->getContextId() === $room->getItemId()) {
            return true;
        }
        if ($actor->getAccount() === null) {
            return false;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $room->getItemId()) !== null;
    }
}
