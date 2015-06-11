<?php

namespace CommsyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RoomManager
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getUserList($roomId) {
        // get person list
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $personList = $roomItem->getUserList();

        $person = $personList->getFirst();
        $personArray = array();

        while($person) {
            $personArray[] = $person;
            $person = $personList->getNext();
        }

        return $personArray;
    }



}
