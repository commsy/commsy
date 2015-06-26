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
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemManager = $this->legacyEnvironment->getItemManager();
    }

    public function getItem($itemId)
    {
        $item = $this->itemManager->getItem($itemId);
        return $item;
    }

    public function getTypedItem($itemId)
    {
        $item = $this->getItem($itemId);

        if ($item) {
            $type = $item->getItemType();

            if ($type == 'label') {
                $manager = $this->legacyEnvironment->getLabelManager();
            } else {
                $manager = $this->legacyEnvironment->getManager($type);
            }

            $item = $manager->getItem($item->getItemID());
            return $item;
        }

        return null;
    }
}