<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ItemService
{
    private $legacyEnvironment;

    private $itemManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->itemManager = $this->legacyEnvironment->getEnvironment()->getitemManager();
    }

    public function getItem($itemId)
    {
        $item = $this->itemManager->getItem($itemId);
        return $item;
    }
}