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

use App\Enum\ReaderStatus;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when an item's read status will be changed for a user.
 * For available read status values, see the constants defined in `ReaderService`.
 *
 * Class ReadStatusPreChangeEvent
 */
class ReadStatusPreChangeEvent extends Event
{
    public function __construct(
        private readonly int $userId,
        private readonly int $itemId,
        private readonly ReaderStatus $newReadStatus
    ) {
    }

    /**
     * The ID of the user for which the item's read status will be changed.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * The ID of the item whose read status will be changed.
     * Note that the returned item may still have the old read status assigned.
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * The new read status that will be assigned to the item.
     */
    public function getNewReadStatus(): ReaderStatus
    {
        return $this->newReadStatus;
    }
}
