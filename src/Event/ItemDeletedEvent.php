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
 * The item.deleted event is dispatched each time an item is deleted
 * in the system.
 */
class ItemDeletedEvent extends Event
{
    final public const NAME = 'item.deleted';

    public function __construct(private readonly cs_item $item)
    {
    }

    public function getItem(): cs_item
    {
        return $this->item;
    }
}
