<?php


namespace App\Search\FilterConditions;


use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\UserService;
use Elastica\Query\Ids;

class ReadStatusFilterCondition implements FilterConditionInterface
{
    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * @var ItemService $itemService
     */
    private $itemService;

    /**
     * @var ReaderService $readerService
     */
    private $readerService;

    /**
     * @var string $readStatus
     */
    private $readStatus;

    public function __construct(UserService $userService, ItemService $itemService, ReaderService $readerService)
    {
        $this->userService = $userService;
        $this->itemService = $itemService;
        $this->readerService = $readerService;
    }

    /**
     * Sets the read status to be used as a filter condition.
     * Read status must be: ReaderService::READ_STATUS_NEW, ReaderService::READ_STATUS_CHANGED or ReaderService::READ_STATUS_SEEN
     * @param string $readStatus
     * @return self
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

        $contextIds = array_map(function (\cs_room_item $room) {
            return $room->getItemID();
        }, $searchableRooms);

        // get all searchable items from the user's rooms
        $items = $this->itemService->getSearchableItemsForContextIds($contextIds);

        // extract the IDs of all items with a read status matching the one in `$this->readStatus`
        $itemIds = $this->readerService->itemIdsForReadStatus($items, $this->readStatus, $currentUser);

        $contextFilter = new Ids();
        $contextFilter->setIds($itemIds);

        return [$contextFilter];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}