<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 15:26
 */

namespace App\Action\MarkRead;


use App\Utils\MaterialService;

class MarkReadMaterial implements MarkReadInterface
{
    /**
     * @var MaterialService
     */
    private $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    /**
     * @param \cs_item $item
     */
    public function markRead(\cs_item $item): void
    {
        $this->materialService->markMaterialReadAndNoticed($item->getItemId());
    }
}