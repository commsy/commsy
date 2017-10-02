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
     * @param  Boolean $includeModifier include or remove "_show" and "_hide" modifier
     * @return array            Array with rubric strings
     */
    public function getRubricInformation($roomId, $includeModifier = false)
    {
        // get the rooms rubric configuration
        $roomItem = $this->getRoomItem($roomId);
        if ($roomItem) {
            $homeConfiguration = $roomItem->getHomeConf();

            $rubrics = array();
            if (!empty($homeConfiguration)) {
                $rubricConfigurations = explode(',', $homeConfiguration);

                foreach ($rubricConfigurations as $rubricConfiguration) {
                    list($rubricName) = explode('_', $rubricConfiguration);
                    $rubrics[] = $rubricName;
                }
            }
            if ($includeModifier) {
                return $rubricConfigurations;
            } else {
                return $rubrics;
            }
        }
    }

    /**
     * returns a user list for the room with the $roomId
     * @param  Integer $roomId room id
     * @return Array         Array with legacy user items
     */
    public function getUserList($roomId)
    {
        // get person list
        $roomItem = $this->getRoomItem($roomId);
        $personList = $roomItem->getUserList();

        return $personList->to_array();
    }

    public function getCurrentRoomItem()
    {
        $currentContextId = $this->legacyEnvironment->getCurrentContextId();
        
        return $this->getRoomItem($currentContextId);
    }

    public function getRoomItem($roomId)
    {
        // get room item
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $this->legacyEnvironment->getPrivateroomManager();
            $roomItem = $privateRoomManager->getItem($roomId);
        }

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
        $roomItem = $this->getRoomItem($roomId);

        return $roomItem->getTitle();
    }

    public function getRoomFileDirectory($roomId)
    {
        $roomDir = implode( "/", array_filter(explode("\r\n", chunk_split(strval($roomId), "4")), 'strlen') );
        return $this->legacyEnvironment->getCurrentPortalID() . "/" . $roomDir . "_";
    }

    public function getRoomsInTimePulse($timeId)
    {
        $projectManager = $this->legacyEnvironment->getProjectManager();
        $projectManager->resetLimits();
        $projectManager->setTimeLimit($timeId);
        $projectManager->unsetContextLimit();
        $projectManager->select();

        return $projectManager->getIDArray();

    }

    public function getTimePulses()
    {
        $timePulses = [];

        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $translator = $this->legacyEnvironment->getTranslationObject();

        if ($portalItem->showTime()) {
            $timeList = $portalItem->getTimeList();

            $timeItem = $timeList->getFirst();
            while ($timeItem) {
                $translatedTitle = $translator->getTimeMessage($timeItem->getTitle());
                $timePulses[$translatedTitle] = $timeItem->getItemID();

                $timeItem = $timeList->getNext();
            }

            $timePulses['continuous'] = 'cont';
        }

        return $timePulses;
    }
}
