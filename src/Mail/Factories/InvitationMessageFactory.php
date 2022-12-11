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

namespace App\Mail\Factories;

use App\Entity\Portal;
use App\Mail\MessageInterface;
use App\Mail\Messages\InvitationMessage;
use App\Services\LegacyEnvironment;

class InvitationMessageFactory
{
    public function __construct(private LegacyEnvironment $legacyEnvironment)
    {
    }

    public function createInvitationMessage(Portal $portal, \cs_room_item $room, string $token): MessageInterface
    {
        return new InvitationMessage($this->legacyEnvironment, $portal, $room, $token);
    }
}
