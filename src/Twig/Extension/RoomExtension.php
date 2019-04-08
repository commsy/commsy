<?php
namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RoomExtension extends AbstractExtension
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('roomTitle', [$this, 'roomTitle']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('roomExpandedHashtags', [$this, 'roomExpandedHashtags']),
            new TwigFunction('roomExpandedCategories', [$this, 'roomExpandedCategories']),
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
}