<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:17
 */

namespace App\Action\Delete;


use App\Services\CopyService;

class DeleteGeneric implements DeleteInterface
{
    /** @var CopyService $copyService */
    protected $copyService;

    public function __construct(CopyService $copyService)
    {
        $this->copyService = $copyService;
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        $item->delete();

        $this->copyService->removeItemFromClipboard($item->getItemId());
    }

    /**
     * @param \cs_item $item
     * @return string
     */
    public function getRedirectRoute(\cs_item $item)
    {
        return null;
    }
}