<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RoomService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * returns the rubrics for the room with the $roomId
     * @param  Integer $roomId  room id
     * @return array            Array with rubric strings
     */
    public function getRubricInformation($roomId)
    {
        // get the rooms rubric configuration
        $roomManager = $this->legacyEnvironment->getRoomManager();
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

    /**
     * returns a user list for the room with the $roomId
     * @param  Integer $roomId room id
     * @return Array         Array with legacy user items
     */
    public function getUserList($roomId)
    {
        // get person list
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $personList = $roomItem->getUserList();

        return $personList->to_array();
    }

    public function getRoomItem($roomId)
    {
        // get room item
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        return $roomItem;
    }

    public function getFilterableRubrics($roomId)
    {
        // get active rubrics
        $activeRubrics = $this->getRubricInformation($roomId);

        // filter rubrics, only group, topic and institution type is filterable
        $filterableRubrics = array_filter($activeRubrics, function($rubric) {
            return in_array($rubric, array('group', 'topic', 'institution'));
        });

        return $filterableRubrics;
    }

    public function getRoomTitle($roomId)
    {
        // return room title
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        return $roomItem->getTitle();
    }
}
