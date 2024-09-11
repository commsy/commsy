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

namespace App\Search\FilterConditions;

use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\UserService;
use cs_room_item;
use Elastica\Query\Ids;

class ReadStatusFilterCondition implements FilterConditionInterface
{
    private ?string $readStatus = null;

    public function __construct(private readonly UserService $userService, private readonly ItemService $itemService, private readonly ReaderService $readerService)
    {
    }

    /**
     * Sets the read status to be used as a filter condition.
     * Read status must be: ReaderStatus::STATUS_NEW, ReaderStatus::STATUS_CHANGED or ReaderStatus::STATUS_SEEN.
     */
    public function setReadStatus(string $readStatus): self
    {
        $this->readStatus = $readStatus;

        return $this;
    }

    /**
     * @return Ids[]
     */
    public function getConditions(): array
    {
        // WARNING: this method potentially iterates over a very large number of items, i.e. this may be very slow!

        if (empty($this->readStatus)) {
            return [];
        }

        // get IDs of the user's rooms
        $currentUser = $this->userService->getCurrentUserItem();
        $searchableRooms = $this->userService->getSearchableRooms($currentUser);

        $contextIds = array_map(fn (cs_room_item $room) => $room->getItemID(), $searchableRooms);

        // get all searchable items from the user's rooms
        $items = $this->itemService->getSearchableItemsForContextIds($contextIds);

        // extract the IDs of all items with a read status matching the one in `$this->readStatus`
        $itemIds = $this->readerService->itemIdsForReadStatus($items, $this->readStatus, $currentUser);

        $contextFilter = new Ids();
        $contextFilter->setIds($itemIds);

        return [$contextFilter];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
