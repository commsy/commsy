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

use App\Lock\LockManager;

/**
 * Edit-time lock state check.
 *
 * Mirrors {@see \App\Security\Authorization\Voter\ItemVoter::canEditLock}
 * but with a direct {@see LockManager} dependency — there is no Symfony
 * `isGranted()` round-trip through the Voter chain. This is what breaks
 * the long-standing cycle in `cs_item::mayEdit`, where the legacy item
 * was reaching back into the security layer via
 * `global $symfonyContainer` to ask `isGranted(ItemVoter::EDIT_LOCK)`.
 *
 * The root short-circuit (which the legacy `Voter::canEditLock` carries)
 * is intentionally NOT in this checker — root semantics depend on the
 * caller's notion of "user". The cs_item.mayEdit wrapper handles it
 * inline via `cs_user_item::isRoot()`. The future Doctrine-only callers
 * will pass a Doctrine User and route through `User::isRoot()` instead.
 */
final readonly class ItemEditChecker
{
    public function __construct(private LockManager $lockManager)
    {
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
}
