<?php

namespace App\Export;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class WikiExporter implements ExporterInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function isEnabled()
    {
        return false;

        // global $c_pmwiki;
        //     if($c_pmwiki and $context_item->isWikiActive()) {
        //         if($this->_item->isExportToWiki()) {
        //             $temp_array = array();
        //             $temp_array[] = $translator->getMessage('MATERIAL_EXPORT_TO_WIKI_LINK');
        //             $temp_array[] = $this->_item->getExportToWikiLink();
        //             $return[] = $temp_array;
        //         }
        //     }
    }

    public function isExportAllowed($item)
    {
        return $item->isExportToWiki();
    }

    public function exportItem($item)
    {
        $wikiManager = $this->legacyEnvironment->getWikiManager();
        $wikiManager->exportItemToWiki_soap($item->getItemID(), $item->getType());
    }
}