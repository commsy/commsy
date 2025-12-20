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

use Tests\Factory\RoomUserFactory;
use Zenstruck\Foundry\Story;

final class RoomWithMemberStory extends Story
{
    public function build(): void
    {
        RoomStory::load();

        $this->addState('room', RoomStory::get('room'));
        $this->addState('account', AccountStory::get('account'));

        $this->addState('roomUser', RoomUserFactory::createOne([
            'account' => $this->getState('account'),
            'room' => $this->getState('room'),
        ]));
    }
}
