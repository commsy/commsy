<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * The item.deleted event is dispatched each time an item is deleted
 * in the system.
 */
class ItemDeletedEvent extends Event
{
    public const NAME = 'item.deleted';

    private $item;

    public function __construct(\cs_item $item)
    {
        $this->item = $item;
    }

    public function getItem(): \cs_item
    {
        return $this->item;
    }
}