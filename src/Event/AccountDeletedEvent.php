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

namespace App\Event;

use App\Entity\Account;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the user's account is about to be deleted.
 *
 * Class AccountDeletedEvent
 */
final class AccountDeletedEvent extends Event
{
    public function __construct(
        private readonly Account $account
    ) {
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
