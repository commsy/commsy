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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AuthSourceGuest.
 */
#[ORM\Entity]
class AuthSourceGuest extends AuthSource
{
    protected string $type = 'guest';

    /**
     * AuthSourceGuest constructor.
     */
    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
        $this->createRoom = false;
    }
}
