<?php

namespace App\Utils;

use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_room_item;
use cs_user_item;

class RoomService
{
    private $legacyEnvironment;

    /**
     * @var CalendarsService
     */
    private $calendarsService;

    public function __construct(LegacyEnvironment $legacyEnvironment, CalendarsService $calendarsService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->calendarsService = $calendarsService;
    }

    /**
     * Returns a new room with the given properties, created by the given room manager
     * @param \cs_room2_manager $roomManager the room manager to be used to create the room (which also defines its type)
     * @param int $contextID the ID of the room which hosts the created room
     * @param string $title the title of the created room
     * @param string $description (optional) the description of the created room
     * @param cs_room_item|null (optional) $roomTemplate the room to be used as a template when creating the new room
     * @param cs_user_item|null (optional) $creator the user who will be specified as the room's creator; if left out,
     * the current user will be used
     * @param cs_user_item|null (optional) $modifier the user who will be specified as the room's modifier; if left out,
     * the current user will be used
     * @return cs_room_item|null the newly created room, or null if an error occurred
     */
    public function createRoom(
        \cs_room2_manager $roomManager,
        int $contextID,
        string $title,
        string $description = "",
        cs_room_item $roomTemplate = null,
        cs_user_item $creator = null,
        cs_user_item $modifier = null
    ): ?cs_room_item
    {
        // TODO: use a facade/factory to create a new room

        if (!isset($roomManager) || empty($contextID) || empty($title)) {
            return null;
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $creator = $creator ?? $currentUser;
        $modifier = $modifier ?? $currentUser;

        $newRoom = $roomManager->getNewItem();
        if (!$newRoom) {
            return null;
        }

        $newRoom->setCreatorItem($creator);
        $newRoom->setModificatorItem($modifier);
        $newRoom->setCreationDate(date('Y-m-d H:i:s'));

        $newRoom->setContextID($contextID);
        $newRoom->open();

        $newRoom->setTitle($title);
        $newRoom->setDescription($description);

        // TODO: in case of a project room, assign the community rooms to which this room belongs (from a method parameter)
        // TODO: set the room's time intervals (from a method parameter)

        // persist room (which will also call $roomManager->saveItem())
        $newRoom->save();

        $this->calendarsService->createCalendar($newRoom, null, null, true);

        // TODO: setRoomContext?

        if ($roomTemplate) {
            $newRoom = $this->copySettings($roomTemplate, $newRoom);
        }

        // TODO: set the room's system language (from a method parameter)

        // mark the room as edited
        $linkModifierItemManager = $this->legacyEnvironment->getLinkModifierItemManager();
        $linkModifierItemManager->markEdited($newRoom->getItemID(), $modifier->getItemID());

        // TODO: set any room categories (from a method parameter)

        return $newRoom;
    }

    public function updateRoomTemplate($roomId, $roomTemplateID)
    {
        $roomItem = $this->getRoomItem($roomId);
        $roomTemplate = $this->getRoomItem($roomTemplateID);
        if ($roomTemplate) {
            $roomItem = $this->copySettings($roomTemplate, $roomItem);
        }
        $roomItem->save();
        return $roomItem;
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

    private function copySettings($masterRoom, $targetRoom)
    {
        // NOTE: the variable names in this method are required by the below included files
        $old_room = $masterRoom;
        $new_room = $targetRoom;
        $old_room_id = $old_room->getItemID();
        $environment = $this->legacyEnvironment;

// TODO: check if the commented code is still necessary
// (when creating a project room with user rooms, the commented code would hit the exception since the user room creator is not a room member)

        /**/
        $user_manager = $environment->getUserManager();
        $creator_item = $user_manager->getItem($new_room->getCreatorID());
//        if ($creator_item->getContextID() == $new_room->getItemID()) {
            $creator_id = $creator_item->getItemID();
//        } else {
//            $user_manager->resetLimits();
//            $user_manager->setContextLimit($new_room->getItemID());
//            $user_manager->setUserIDLimit($creator_item->getUserID());
//            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
//            $user_manager->setModeratorLimit();
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
//                $creator_item = $user_list->getFirst();
//                $creator_id = $creator_item->getItemID();
//            } else {
//                throw new \Exception('can not get creator of new room');
//            }
//        }
//        $creator_item->setAccountWantMail('yes');
//        $creator_item->setOpenRoomWantMail('yes');
//        $creator_item->setPublishMaterialWantMail('yes');
//        $creator_item->save();

        // copy room settings
        require('include/inc_room_copy_config.php');

        // save new room
        $new_room->save();

        // copy data
        require('include/inc_room_copy_data.php');
        /**/

        $targetRoom = $new_room;

        return $targetRoom;
    }

    /**
     * returns a user list for the room with the $roomId
     * @param Integer $roomId room id
     * @return array Array with legacy user items
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
     * @return cs_user_item[] An array of users who are contact persons or moderators of the room with the given room ID
     */
    public function getContactModeratorItems($roomId)
    {
        $roomItem = $this->getRoomItem($roomId);
        $contactModeratorList = $roomItem->getContactModeratorList();

        return $contactModeratorList->to_array();
    }

    public function getCurrentRoomItem()
    {
        $currentContextId = $this->legacyEnvironment->getCurrentContextId();

        return $this->getRoomItem($currentContextId);
    }

    /**
     * @param integer $roomId
     * @return cs_room_item
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
        /** @var cs_room_item $roomItem */
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

    /**
     * Returns all community rooms that host the given (project) room.
     * @param cs_room_item $room The room whose related community rooms shall be returned
     * @return cs_community_item[] Array of community rooms that host the given (project) room
     */
    public function getCommunityRoomsForRoom(cs_room_item $room): array
    {
        // NOTE: we don't use $room->getCommunityList() here since that method may incorrectly set the room limit
        //       to the current context (instead of the room's context); this e.g. happens if this method gets
        //       called for a project room's detail page within a community room

        $link_item_manager = $this->legacyEnvironment->getLinkItemManager();
        $link_item_manager->resetLimits();
        $link_item_manager->setLinkedItemLimit($room);
        $link_item_manager->setTypeLimit(CS_COMMUNITY_TYPE);
        $link_item_manager->setRoomLimit($room->getContextID());
        $link_item_manager->select();
        $link_list = $link_item_manager->get();
        $result_list = new \cs_list();
        $link_item = $link_list->getFirst();
        while ($link_item) {
            $result_list->add($link_item->getLinkedItem($room));
            $link_item = $link_list->getNext();
        }
        $communityRooms = $result_list;

        return $communityRooms->to_array();
    }

    /**
     * Returns the IDs of all given rooms.
     *
     * @param cs_room_item[] $rooms The array of rooms whose IDs shall be returned
     * @return int[]
     */
    public function getIdsForRooms(array $rooms): array
    {
        if (empty($rooms)) {
            return [];
        }

        $roomIds = array_map(function (cs_room_item $room) {
            return $room->getItemID();
        }, $rooms);

        return $roomIds;
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

    /**
     * Returns all room templates available for the given room type
     * @param string $type the type of the room
     * @return array array of room template IDs keyed by room title & ID
     */
    public function getAvailableTemplates(string $type): array
    {
        $templates = [];

        $legacyEnvironment = $this->legacyEnvironment;

        $currentUserItem = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($legacyEnvironment->getCurrentPortalItem()->getItemID());
        $roomManager->setTemplateLimit();
        $roomManager->select();

        $templateList = $roomManager->get();
        if ($templateList->isNotEmpty()) {
            $template = $templateList->getFirst();
            while ($template) {
                $availability = $template->getTemplateAvailability(); // $type === 'project'
                if ($type === 'community') {
                    $availability = $template->getCommunityTemplateAvailability();
                }

                $add = false;

                // free for all?
                if (!$add && $availability == '0') {
                    $add = true;
                }

                // only in community rooms
                if (!$add && $legacyEnvironment->inCommunityRoom() && $availability == '3') {
                    $add = true;
                }

                // same as above, but from portal context
                if (!$add && $legacyEnvironment->inPortal() && $availability == '3') {
                    // check if user is member in one of the templates community rooms
                    $communityList = $template->getCommunityList();
                    if ($communityList->isNotEmpty()) {
                        $userCommunityList = $currentUserItem->getRelatedCommunityList();
                        if ($userCommunityList->isNotEmpty()) {
                            $communityItem = $communityList->getFirst();
                            while ($communityItem) {
                                $userCommunityItem = $userCommunityList->getFirst();
                                while ($userCommunityItem) {
                                    if ($userCommunityItem->getItemID() == $communityItem->getItemID()) {
                                        $add = true;
                                        break;
                                    }

                                    $userCommunityItem = $userCommunityList->getNext();
                                }

                                $communityItem = $communityList->getNext();
                            }
                        }
                    }
                }

                // only for members
                if (!$add && $availability == '1' && $template->mayEnter($currentUserItem)) {
                    $add = true;
                }

                // only mods
                if (!$add && $availability == '2' && $template->mayEnter($currentUserItem)) {
                    if ($template->isModeratorByUserID($currentUserItem->getUserID(), $currentUserItem->getAuthSource())) {
                        $add = true;
                    }
                }

                if ($type != $template->getItemType()) {
                    $add = false;
                }

                if ($add) {
                    $label = $template->getTitle() . ' (ID: ' . $template->getItemID() . ')';
                    $templates[$label] = $template->getItemID();
                }

                $template = $templateList->getNext();
            }
        }

        return $templates;
    }
}
