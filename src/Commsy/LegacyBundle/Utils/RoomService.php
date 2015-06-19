<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RoomService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getRubricInformation($roomId)
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();
        
        // get the rooms rubric configuration
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        $homeConfiguration = $roomItem->getHomeConf();

        $rubrics = array();
        if (!empty($homeConfiguration)) {
            $rubricConfigurations = explode(',', $homeConfiguration);
            
            foreach ($rubricConfigurations as $rubricConfiguration) {
                list($rubricName) = explode('_', $rubricConfiguration);
                $rubrics[] = $rubricName;
            }
        }
        return $rubrics;

    }

    public function getUserList($roomId)
    {
        // get person list
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $personList = $roomItem->getUserList();

        return $personList->to_array();
    }
}
