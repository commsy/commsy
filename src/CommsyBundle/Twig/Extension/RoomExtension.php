<?php
namespace CommsyBundle\Twig\Extension;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RoomExtension extends \Twig_Extension
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('roomTitle', array($this, 'roomTitle')),
        );
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

    public function getName()
    {
        return 'RoomExtension';
    }
}