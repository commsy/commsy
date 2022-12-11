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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the user's account has been updated.
 *
 * Class AccountChangedEvent
 */
class AccountChangedEvent extends Event
{
    /**
     * @var \cs_user_item The unchanged item
     */
    private $oldAccount;

    /**
     * @var \cs_user_item The updated item
     */
    private $newAccount;

    public function __construct(\cs_user_item $oldAccount, \cs_user_item $newAccount)
    {
        $this->oldAccount = $oldAccount;
        $this->newAccount = $newAccount;
    }

    public function getOldAccount(): \cs_user_item
    {
        return $this->oldAccount;
    }

    public function getNewAccount(): \cs_user_item
    {
        return $this->newAccount;
    }
}
