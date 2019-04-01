<?php

namespace App\Export;

use App\Services\LegacyEnvironment;

class WordpressExporter implements ExporterInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function isEnabled()
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $contextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($portalItem->getWordpressPortalActive()) {
            if ($contextItem->isWordpressActive()) {
                return true;
            }
        }

        return false;
    }

    public function isExportAllowed($item)
    {
        return $item->isExporttoWordpress();
    }

    public function exportItem($item)
    {
        $wordpressManager = $this->legacyEnvironment->getWordpressManager();
        $wordpressManager->exportItemToWordpress($item->getItemID(), $item->getType());
    }
}