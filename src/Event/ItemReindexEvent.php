<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event gets fired when an item should be reindexed by the Elastic search index.
 *
 * Class ItemReindexEvent
 * @package App\Event
 */
class ItemReindexEvent extends Event
{
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
