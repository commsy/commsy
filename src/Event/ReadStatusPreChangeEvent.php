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

use App\Utils\ReaderService;
use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when an item's read status will be changed for a user.
 * For available read status values, see the constants defined in `ReaderService`.
 *
 * Class ReadStatusPreChangeEvent
 */
class ReadStatusPreChangeEvent extends Event
{
    private string $newReadStatus;

    public function __construct(private int $userId, private int $itemId, string $newReadStatus)
    {
        if (
            ReaderService::READ_STATUS_NEW !== $newReadStatus &&
            ReaderService::READ_STATUS_CHANGED !== $newReadStatus &&
            ReaderService::READ_STATUS_NEW_ANNOTATION !== $newReadStatus &&
            ReaderService::READ_STATUS_CHANGED_ANNOTATION !== $newReadStatus &&
            ReaderService::READ_STATUS_SEEN !== $newReadStatus &&
            !empty($newReadStatus) // most CommSy code currently uses an empty string ('') instead of READ_STATUS_SEEN
        ) {
            throw new InvalidArgumentException('unknown read status given');
        }
        $this->newReadStatus = $newReadStatus;
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
    public function getNewReadStatus(): string
    {
        return $this->newReadStatus;
    }
}
