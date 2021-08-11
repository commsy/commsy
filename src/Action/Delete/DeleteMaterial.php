<?php

namespace App\Action\Delete;


use App\Services\CopyService;

class DeleteMaterial implements DeleteInterface
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
        /** \cs_material_item $material */
        $material = $item;

        $material->deleteAllVersions();

        $this->copyService->removeItemFromClipboard($material->getItemId());
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