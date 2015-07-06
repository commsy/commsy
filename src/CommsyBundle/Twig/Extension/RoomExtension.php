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
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        return $roomItem->getTitle();
    }

    public function getName()
    {
        return 'RoomExtension';
    }
}