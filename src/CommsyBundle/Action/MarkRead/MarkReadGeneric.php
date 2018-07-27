<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 15:21
 */

namespace CommsyBundle\Action\MarkRead;


use Commsy\LegacyBundle\Utils\ItemService;

class MarkReadGeneric implements MarkReadInterface
{
    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    /**
     * @param \cs_item $item
     */
    public function markRead(\cs_item $item): void
    {
        $this->itemService->markRead([$item]);
    }
}