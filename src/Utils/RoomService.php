<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;

class RoomService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * returns the rubrics for the room with the $roomId
     * @param Integer $roomId room id
     * @param Boolean $includeModifier include or remove "_show" and "_hide" modifier
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
     * @param Integer $roomId room id
     * @return Array         Array with legacy user items
     */
    public function getUserList($roomId)
    {
        // get person list
        $roomItem = $this->getRoomItem($roomId);
        $personList = $roomItem->getUserList();

        return $personList->to_array();
    }

    /**
     * For the room with the given room ID, returns all users who have the status of a contact person or moderator.
     * Note that if some contact person(s) have been defined for the room, only these will be returned. Otherwise,
     * the room moderators will be returned.
     *
     * @param int $roomId The ID of the containing context
     * @return \cs_user_item[] An array of users who are contact persons or moderators of the room with the given room ID
     */
    public function getContactModeratorItems($roomId)
    {
        $roomItem = $this->getRoomItem($roomId);
        $contactModeratorList = $roomItem->getContactModeratorList();

        return $contactModeratorList->to_array();
    }

    /**
     * For the room with the given room ID, returns all users who have the status of a contact person or moderator.
     * Note that if some contact person(s) have been defined for the room, only these will be returned. Otherwise,
     * the room moderators will be returned.
     *
     * @param int $roomId The ID of the containing context
     * @return \cs_user_item[] An array of users who are contact persons or moderators of the room with the given room ID
     */
    public function getModeratorList($roomId)
    {
        $roomItem = $this->getRoomItem($roomId);
        $contactModeratorList = $roomItem->getModeratorList();

        return $contactModeratorList->to_array();
    }

    public function getCurrentRoomItem()
    {
        $currentContextId = $this->legacyEnvironment->getCurrentContextId();

        return $this->getRoomItem($currentContextId);
    }

    /**
     * @param integer $roomId
     * @return \cs_room_item
     */
    public function getRoomItem($roomId)
    {
        /**
         * NOTICE: returning archived rooms here as a fallback if no room or private room item was found
         * currently impacts at least the "all rooms" feed due to the fact, that it relies on this function
         * returning false, if the room is archived.
         */

        // get room item
        $roomManager = $this->legacyEnvironment->getRoomManager();
        /** @var \cs_room_item $roomItem */
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $this->legacyEnvironment->getPrivateroomManager();
            $roomItem = $privateRoomManager->getItem($roomId);
        }

        return $roomItem;
    }

    public function getArchivedRoomItem($roomId)
    {
        $zzzRoomItem = $this->legacyEnvironment->getZzzRoomManager();
        $roomItem = $zzzRoomItem->getItem($roomId);

        return $roomItem;
    }

    public function getFilterableRubrics($roomId)
    {
        // get active rubrics
        $activeRubrics = $this->getRubricInformation($roomId);

        // filter rubrics, only group, topic and institution type is filterable
        $filterableRubrics = array_filter($activeRubrics, function ($rubric) {
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
        $roomDir = implode("/", array_filter(explode("\r\n", chunk_split(strval($roomId), "4")), 'strlen'));
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

    /**
     * Returns the array of time pulses specified for the current portal
     * @param bool $reverseOrder whether the list of time pulses shall be returned in reverse
     * order (true) or not (false); defaults to false
     * @return array list of time pulses
     */
    public function getTimePulses($reverseOrder = false)
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        if (!$portalItem->showTime()) {
            return [];
        }

        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();

        $timePulses = [];
        if ($reverseOrder) {
            $timePulses['continuous'] = 'cont';
        }

        $timeList = ($reverseOrder) ? $portalItem->getTimeListRev() : $portalItem->getTimeList();

        $timeItem = $timeList->getFirst();
        while ($timeItem) {
            $translatedTitle = $legacyTranslator->getTimeMessage($timeItem->getName());
            $timePulses[$translatedTitle] = $timeItem->getItemID();

            $timeItem = $timeList->getNext();
        }

        if (!$reverseOrder) {
            $timePulses['continuous'] = 'cont';
        }

        return $timePulses;
    }

    public function buildServiceLink()
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

        $remoteServiceLink = '';
        if ($portalItem) {
            $remoteServiceLink = $portalItem->getServiceLinkExternal();
        }

        if (empty($remoteServiceLink)) {
            $serverItem = $this->legacyEnvironment->getServerItem();
            $remoteServiceLink = $serverItem->getServiceLinkExternal();
        }

        if (!empty($remoteServiceLink)) {
            if (strstr($remoteServiceLink, '%')) {
                $textConverter = $this->legacyEnvironment->getTextConverter();
                $remoteServiceLink = $textConverter->convertPercent($remoteServiceLink, true, true);
            }

            return $remoteServiceLink;
        } else {
            $serviceEmail = $this->getServiceEmail();

            if (!empty($serviceEmail)) {
                return 'mailto:' . $serviceEmail;
            }
        }

        return '';
    }

    /**
     * Returns the service email address specified for the current portal or server
     * @return string service email address
     */
    public function getServiceEmail()
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $serviceEmail = '';

        if ($portalItem) {
            $serviceEmail = $portalItem->getServiceEmail();
        }

        if (empty($serviceEmail)) {
            $serverItem = $this->legacyEnvironment->getServerItem();
            $serviceEmail = $serverItem->getServiceEmail();
        }

        return $serviceEmail;
    }
}
