<?php


namespace App\Event;


use App\Utils\ReaderService;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when an item's read status will be changed for a user.
 * For available read status values, see the constants defined in `ReaderService`.
 *
 * Class ReadStatusPreChangeEvent
 * @package App\Event
 */
class ReadStatusPreChangeEvent extends Event
{
    /**
     * @var int $userId
     */
    private $userId;

    /**
     * @var int $itemId
     */
    private $itemId;

    /**
     * @var string $newReadStatus
     */
    private $newReadStatus;

    public function __construct(int $userId, int $itemId, string $newReadStatus)
    {
        if (
            $newReadStatus !== ReaderService::READ_STATUS_NEW &&
            $newReadStatus !== ReaderService::READ_STATUS_CHANGED &&
            $newReadStatus !== ReaderService::READ_STATUS_NEW_ANNOTATION &&
            $newReadStatus !== ReaderService::READ_STATUS_CHANGED_ANNOTATION &&
            $newReadStatus !== ReaderService::READ_STATUS_SEEN &&
            !empty($newReadStatus) // most CommSy code currently uses an empty string ('') instead of READ_STATUS_SEEN
        ) {
            throw new \InvalidArgumentException('unknown read status given');
        }

        $this->userId = $userId;
        $this->itemId = $itemId;
        $this->newReadStatus = $newReadStatus;
    }

    /**
     * The ID of the user for which the item's read status will be changed.
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * The ID of the item whose read status will be changed.
     * Note that the returned item may still have the old read status assigned.
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * The new read status that will be assigned to the item.
     * @return string
     */
    public function getNewReadStatus(): string
    {
        return $this->newReadStatus;
    }
}
