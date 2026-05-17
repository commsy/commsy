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

namespace App\Legacy;

use App\Entity\User;
use cs_environment;
use cs_user_item;

/**
 * STRICTLY ONE-WAY, STRICTLY ONE CALLER.
 *
 * Only {@see \App\EventSubscriber\LegacySubscriber} may use this. Do not
 * add callers. It exists solely so the legacy environment can keep its
 * implicit `currentUserItem` while the *decision* of who that user is
 * has already moved to the Doctrine-native {@see \App\Services\CurrentUserResolver}.
 *
 * It deliberately does NOT hand-build a cs_user_item from the entity:
 * the legacy object carries ~1700 lines of behaviour (mayEdit, isModerator,
 * getFullName, …) that the 36 remaining legacy-internal callers still
 * rely on. Instead it re-hydrates the faithful legacy object by id —
 * the legacy user manager is reduced to a dumb id→object loader, while
 * the resolver owns the (account, context) → which-row identity decision.
 *
 * Dies with cs_user_item: once no legacy code reads currentUserItem,
 * delete this and the setCurrentUser() call in LegacySubscriber.
 */
final class UserItemAdapter
{
    public static function userToLegacy(User $user, cs_environment $environment): ?cs_user_item
    {
        return $environment->getUserManager()->getItem($user->getItemId());
    }
}
