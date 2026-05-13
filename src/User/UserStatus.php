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
 * Typed wrapper around the `user.status` int column.
 *
 * Mirrors the values cs_user_item uses internally: 0 = guest/rejected,
 * 1 = membership requested, 2 = regular member, 3 = moderator,
 * 4 = read-only member. The legacy code does NOT distinguish between
 * the "portal guest singleton" (status=0 + user_id='guest') and a
 * "membership rejected" row (status=0 + real user_id) at the status
 * level — that distinction lives in {@see User::isReallyGuest()}.
 *
 * Note: "Root" is NOT an enum case. A root user is a moderator-status
 * user_item (status=3) with `user_id='root'` in the server context
 * (context_id=99); the classification is composite and lives in
 * {@see \App\Security\Permission\Role\RoleResolver::isRoot()}.
 */
enum UserStatus: int
{
    case Guest = 0;
    case Requested = 1;
    case User = 2;
    case Moderator = 3;
    case ReadOnly = 4;

    public static function fromUser(User $user): self
    {
        return self::from($user->getStatus());
    }

    /**
     * Whether the status counts as "is a member" — `cs_user_item::isUser()`
     * semantics (status 2, 3 or 4).
     */
    public function isMember(): bool
    {
        return match ($this) {
            self::User, self::Moderator, self::ReadOnly => true,
            self::Guest, self::Requested => false,
        };
    }
}
