<?php

namespace App\Search;

use App\Services\LegacyEnvironment;

class IndexableChecker
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function isIndexable($object)
    {
        // Call the objects isIndexable method
        if (!$object->isIndexable()) {
            return false;
        }

        // Check if this is a draft
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($object->getItemId());
        if ($item && $item->isDraft()) {
            return false;
        }

        return true;
    }
}