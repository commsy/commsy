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

use App\Entity\User;
use App\Lock\LockManager;
use App\Repository\UserRepository;

/**
 * Default edit-permission base for items that don't carry a rubric-
 * specific override. Mirrors the body of `cs_item::mayEdit` 1:1 — root
 * shortcut, context-membership lookup, moderator/creator/public branch,
 * lock check.
 *
 * Rubric-specific edit semantics live in their own checkers and are
 * dispatched via {@see \App\Security\Permission\Dispatcher\ItemEditDispatcher}
 * — discussion-articles override "no edit after closed", section/step
 * delegate to the linked item, etc.
 *
 * `canEditLock` is the legacy-Voter-cycle-breaking entry from Phase 2.3
 * and stays a public method because the file-edit and dispatcher paths
 * both consult it after their own moderator/creator decision.
 */
final readonly class ItemEditChecker
{
    public function __construct(
        private LockManager $lockManager,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Full base check. Mirrors `cs_item::mayEdit` for items that have
     * no subtype override:
     *
     *   - read-only users (status 4) can never edit          → false
     *   - root short-circuits                                → true
     *   - actor must have a non-deleted membership in the
     *     item's context with status >= 2 (isUser)           → otherwise false
     *   - moderator in context                               → lock check
     *   - creator of the item                                → lock check
     *   - item is `public=1` (i.e. !isPrivateEditing)        → lock check
     *   - else                                               → false
     *
     * The lock check is `canEditLock($item->getItemId())` — already
     * Phase 2.3 Doctrine-only via {@see LockManager}.
     */
    public function canEdit(User $actor, object $item): bool
    {
        if ($actor->isReadOnlyUser()) {
            return false;
        }
        if ($actor->isRoot()) {
            return true;
        }

        $contextId = $item->getContextId();
        if ($contextId === null) {
            return false;
        }

        $membership = $this->resolveMembershipInContext($actor, $contextId);
        if ($membership === null || !$membership->isUser()) {
            return false;
        }

        if ($membership->isModerator()) {
            return $this->canEditLock($item->getItemId());
        }

        $creatorId = $this->resolveCreatorId($item);
        if ($creatorId !== null && $creatorId === $membership->getItemId()) {
            return $this->canEditLock($item->getItemId());
        }

        // `public == 1` on the subtype table = !isPrivateEditing on the
        // legacy cs_item — items flagged as world-editable are open to
        // any context member.
        if ($this->isOpenToAnyMember($item)) {
            return $this->canEditLock($item->getItemId());
        }

        return false;
    }

    /**
     * Whether the lock state of the item permits editing for the
     * currently authenticated user (resolved by {@see LockManager} from
     * the security token).
     *
     * Returns true when:
     *   - the item type does not support locking (Material, Date, etc.
     *     are lockable; user_items, rooms etc. are not), OR
     *   - no active lock exists, OR
     *   - the current user is the lock holder.
     *
     * Returns false only when another user holds an active lock on a
     * lockable item type.
     */
    public function canEditLock(int $itemId): bool
    {
        if (!$this->lockManager->supportsLocking($itemId)) {
            return true;
        }
        return $this->lockManager->userCanLock($itemId);
    }

    private function resolveMembershipInContext(User $actor, int $contextId): ?User
    {
        if ($actor->getContextId() === $contextId) {
            return $actor;
        }
        if ($actor->getAccount() === null) {
            return null;
        }
        return $this->userRepository->findInContext($actor->getAccount(), $contextId);
    }

    /**
     * Reads `getCreator()?->getItemId()` polymorphically — all
     * Item-domain subclasses migrated to {@see \App\Utils\EntityUsersTrait}
     * expose it. Partial-column entities (Files, Calendars, LinkItems,
     * Assessments) degrade to null and lose the "is-creator" branch.
     */
    private function resolveCreatorId(object $item): ?int
    {
        if (!method_exists($item, 'getCreator')) {
            return null;
        }
        return $item->getCreator()?->getItemId();
    }

    /**
     * Reads the subtype's `public` flag — only set on Item-domain
     * subclasses (Materials, Discussions, Dates, Announcement, Todos,
     * Annotations, Labels, Step, Discussionarticles). The Items parent
     * itself has no public column. Subclasses without that accessor
     * fall back to the safe default (private editing on).
     */
    private function isOpenToAnyMember(object $item): bool
    {
        if (!method_exists($item, 'getPublic')) {
            return false;
        }
        $public = $item->getPublic();
        // Most subtypes return bool; Discussionarticles returns string
        // (legacy `-1`, `-2`, `0`, `1` semantics). We accept anything
        // that converts to the integer 1.
        return (int) $public === 1;
    }
}
