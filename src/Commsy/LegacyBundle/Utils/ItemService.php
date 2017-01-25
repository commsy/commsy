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

        if ($item && is_object($item)) {
            $type = $item->getItemType();

            if ($type == 'label') {
                $labelManager = $this->legacyEnvironment->getLabelManager();
                $labelItem = $labelManager->getItem($item->getItemID());
                $type = $labelItem->getLabelType();
            }
            
            $manager = $this->legacyEnvironment->getManager($type);

            $item = $manager->getItem($item->getItemID());
            return $item;
        }

        return null;
    }
    
    public function getEditorsForItem ($item) {
        $user = $this->legacyEnvironment->getCurrentUserItem();
	    $link_modifier_item_manager = $this->legacyEnvironment->getLinkModifierItemManager();
	    $user_manager = $this->legacyEnvironment->getUserManager();
	    $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());
	    $modifier_array = array();
	    foreach($modifiers as $modifier_id) {
	        $modificator = $user_manager->getItem($modifier_id);
            $modifier_array[] = $modificator;
	    }
	    return $modifier_array;
    }
    
    public function getAdditionalEditorsForItem ($item) {
        $modifier_array = $this->getEditorsForItem($item);
        $additional_modifier_array = array();
        foreach ($modifier_array as $modifier) {
            if ($modifier->getItemId() != $item->getCreatorId()) {
                $additional_modifier_array[] = $modifier;
            }
        }
        return $additional_modifier_array;
    }
}