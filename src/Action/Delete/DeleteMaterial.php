<?php

namespace App\Action\Delete;


use App\Services\MarkedService;

class DeleteMaterial implements DeleteInterface
{
    /** @var MarkedService $copyService */
    protected MarkedService $copyService;

    public function __construct(MarkedService $copyService)
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