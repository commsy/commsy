<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ReaderService
{
    private $legacyEnvironment;

    private $readerManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->readerManager = $this->legacyEnvironment->getEnvironment()->getReaderManager();
    }

    public function getLatestReader($itemId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReader($itemId);
    }
    
    public function getLatestReaderForUserByID($itemId, $userId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReaderForUserByID($itemId, $userId);
    }
}