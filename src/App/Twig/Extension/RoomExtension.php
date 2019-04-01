<?php
namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;

class RoomExtension extends \Twig_Extension
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('roomTitle', [$this, 'roomTitle']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('roomExpandedHashtags', [$this, 'roomExpandedHashtags']),
            new \Twig_SimpleFunction('roomExpandedCategories', [$this, 'roomExpandedCategories']),
        ];
    }

    public function roomTitle($roomId)
    {
        // get room title
        $item_manager = $this->legacyEnvironment->getEnvironment()->getItemManager();
        $item = $item_manager->getItem($roomId);

        if ($item->getItemType() != 'privateroom') {
            $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        } else {
            $roomManager = $this->legacyEnvironment->getEnvironment()->getPrivateRoomManager();
        }
        $roomItem = $roomManager->getItem($roomId);

        return $roomItem->getTitle();
    }

    public function roomExpandedHashtags($roomId)
    {
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem) {
            return $roomItem->isBuzzwordShowExpanded();
        }

        return false;
    }

    public function roomExpandedCategories($roomId)
    {
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem) {
            return $roomItem->isTagsShowExpanded();
        }

        return false;
    }

    public function getName()
    {
        return 'RoomExtension';
    }
}