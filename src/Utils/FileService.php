<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_file_item;

class FileService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    /**
     * @param $fileId
     * @return cs_file_item|null
     */
    public function getFile($fileId):? cs_file_item
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();
        return $fileManager->getItem($fileId);
    }

    public function getNewFile()
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();
        return $fileManager->getNewItem();
    }
}
