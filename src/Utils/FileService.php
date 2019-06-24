<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;

class FileService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    /**
     * @param $fileId
     * @return \cs_file_item|null
     */
    public function getFile($fileId):? \cs_file_item
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();
        $file = $fileManager->getItem($fileId);

        if (!$file) {
            $fileManager = $this->legacyEnvironment->getEnvironment()->getZzzFileManager();
            $file = $fileManager->getItem($fileId);
        }

        return $file;
    }
    
    public function getNewFile()
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();
        return $fileManager->getNewItem();
    }
}