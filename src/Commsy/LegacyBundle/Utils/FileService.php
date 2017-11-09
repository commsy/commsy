<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class FileService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getFile($fileId)
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