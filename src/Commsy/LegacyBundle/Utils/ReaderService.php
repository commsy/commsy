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
        return $this->readerManager->getLatestReader($itemId);
    }
}