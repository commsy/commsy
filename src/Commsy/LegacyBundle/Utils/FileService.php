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
        return $fileManager->getItem($fileId);
    }
    
    public function getNewFile()
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();
        return $fileManager->getNewItem();
    }
}