<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:17
 */

namespace App\Action\Delete;


use App\Services\MarkedService;
use App\Services\LegacyEnvironment;
use cs_environment;

class DeleteGeneric implements DeleteInterface
{
    /**
     * @var cs_environment
     */
    protected cs_environment $legacyEnvironment;

    /** @var MarkedService $markedService */
    protected MarkedService $markedService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        MarkedService $markedService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->markedService = $markedService;
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        $item->delete();

        $this->markedService->removeItemFromClipboard($item->getItemId());
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