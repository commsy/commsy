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
 * Doctrine-only port of `cs_user_item::mayEdit` and `mayEditRegular`.
 *
 * Distinct from {@see \App\Security\Permission\Checker\ItemEditChecker}
 * because user-items don't follow the cs_item::mayEdit body — no
 * lock check, no `public = 1` open-edit flag, no archive bypass. The
 * legacy rules collapse to: read-only never; root always; in the same
 * context + (moderator OR self) → yes; otherwise no.
 *
 * `canEditRegular` is the stricter "self-only" variant: it permits edit
 * exclusively to the user themselves (used by legacy form views that
 * intentionally hide the moderator override).
 */
final readonly class UserEditChecker
{
    /**
     * Mirrors `cs_user_item::mayEdit`.
     */
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

    /**
     * Mirrors `cs_user_item::mayEditRegular` — self-only, no moderator
     * override.
     */
    public function canEditRegular(User $actor, User $target): bool
    {
        if ($actor->isReadOnlyUser()) {
            return false;
        }

        return $this->isSameAccountIdentity($actor, $target);
    }

    private function isSameAccountIdentity(User $actor, User $target): bool
    {
        return $actor->getUserId() === $target->getUserId()
            && $actor->getAuthSource() === $target->getAuthSource();
    }
}
