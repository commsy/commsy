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

use App\Entity\User;

/**
 * Doctrine-only port of `cs_user_item::mayEdit`.
 *
 * Distinct from {@see \App\Security\Permission\Checker\ItemEditChecker}
 * because user-items don't follow the cs_item::mayEdit body — no
 * lock check, no `public = 1` open-edit flag, no archive bypass. The
 * legacy rules collapse to: read-only never; root always; in the same
 * context + (moderator OR self) → yes; otherwise no.
 *
 * Note: the legacy `mayEditRegular` companion method is NOT ported.
 * Verified across origin/10.0 … origin/10.5 plus the current branch:
 * zero callers, six+ legacy major versions of dead code. Removed as
 * part of this phase rather than mirrored.
 */
final readonly class UserEditChecker
{
    public function canEdit(User $actor, User $target): bool
    {
        if ($actor->isReadOnlyUser()) {
            return false;
        }

        if ($actor->isRoot()) {
            return true;
        }

        if ($actor->getContextId() !== $target->getContextId()) {
            return false;
        }

        if ($actor->isModerator()) {
            return true;
        }

        if ($actor->isUser() && $this->isSameAccountIdentity($actor, $target)) {
            return true;
        }

        return false;
    }

    private function isSameAccountIdentity(User $actor, User $target): bool
    {
        $actorAccount = $actor->getAccount();
        $targetAccount = $target->getAccount();
        if ($actorAccount === null || $targetAccount === null) {
            return false;
        }

        return $actorAccount->getId() === $targetAccount->getId();
    }
}
