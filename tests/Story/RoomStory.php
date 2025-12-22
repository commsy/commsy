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

namespace Tests\Story;

use App\Entity\Account;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Story;

final class RoomStory extends Story
{
    public function build(): void
    {
        AccountStory::load();

        /** @var Account $account */
        $account = AccountStory::get('account');

        $this->addState('room', RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
        ]));
    }
}
