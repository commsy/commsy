<?php

namespace App\Action\Delete;


use App\Services\MarkedService;

class DeleteMaterial implements DeleteInterface
{
    /** @var MarkedService $markedService */
    protected MarkedService $markedService;

    public function __construct(MarkedService $markedService)
    {
        $this->markedService = $markedService;
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        /** \cs_material_item $material */
        $material = $item;

        $material->deleteAllVersions();

        $this->markedService->removeItemFromClipboard($material->getItemId());
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