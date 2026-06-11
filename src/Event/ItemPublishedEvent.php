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

use cs_item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a draft entry is published (undrafted) and thereby becomes
 * visible to the room. This is the real "new entry" moment: CommsyEditEvent::SAVE
 * fires while the item is still a draft (see ItemService::undraft, which defers
 * elastic indexing for the same reason), so notifications hook this event.
 */
class ItemPublishedEvent extends Event
{
    final public const NAME = 'item.published';

    public function __construct(private readonly cs_item $item)
    {
    }

    public function getItem(): cs_item
    {
        return $this->item;
    }
}
