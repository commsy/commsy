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

final class AccountCreatedEvent extends Event
{
    public function __construct(
        private readonly Account $account
    ) {}

    public function getAccount(): Account
    {
        return $this->account;
    }
}
