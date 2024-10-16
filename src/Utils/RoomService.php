<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Room\Copy\LegacyCopy;
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Symfony\Component\HttpFoundation\Request;

class RoomService
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly LegacyCopy $legacyCopy
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
     * returns the rubrics for the room with the $roomId.
     *
     * @param int  $roomId          room id
     * @param bool $includeModifier include or remove "_show" and "_hide" modifier
     *
     * @return array Array with rubric strings
     */
    public function getRubricInformation($roomId, $includeModifier = false): array
    {
        $rubricConfigurations = [];

        // get the rooms rubric configuration
        $roomItem = $this->getRoomItem($roomId);
        if ($roomItem) {
            $homeConfiguration = $roomItem->getHomeConf();

            $rubrics = [];
            if (!empty($homeConfiguration)) {
                $rubricConfigurations = explode(',', (string) $homeConfiguration);

                foreach ($rubricConfigurations as $rubricConfiguration) {
                    [$rubricName] = explode('_', $rubricConfiguration);
                    $rubrics[] = $rubricName;
                }
            }
            if ($includeModifier) {
                return $rubricConfigurations;
            } else {
                return $rubrics;
            }
        }

        return [];
    }

    /**
     * @param int $roomId The id of the room
     * @return string[] List of rubrics needed for querying
     */
    public function getVisibleRoomRubrics(int $roomId, array $exclude = ['hide', 'off']): array
    {
        $rubricConfiguration = $this->getRubricInformation($roomId, true);

        $visibleRubrics = array_filter($rubricConfiguration, function ($rubric) use ($exclude) {
            [, $modifier] = explode('_', $rubric);

            return !in_array($modifier, $exclude);
        });

        return array_map(function ($rubric) {
            [$name] = explode('_', $rubric);
            return $name;
        }, $visibleRubrics);
    }

    public function copySettings($masterRoom, $targetRoom)
    {
        // TODO: check if the commented code is still necessary
        // (when creating a project room with user rooms, the commented code would hit the exception since the user room creator is not a room member)

        $user_manager = $this->legacyEnvironment->getUserManager();
        $creator_item = $user_manager->getItem($targetRoom->getCreatorID());
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
//        $creator_item->save();

        // copy room settings
        $this->legacyCopy->copySettings($masterRoom, $targetRoom);

        // save new room
        $targetRoom->save();

        // copy data
        $this->legacyCopy->copyData($masterRoom, $targetRoom, $creator_item);

        return $targetRoom;
    }

    /**
     * Returns the list of users for the given room item.
     *
     * @param cs_room_item $roomItem room item whose users should be returned
     *
     * @return cs_user_item[] Array of all users that are members of the given room
     */
    public function getUsers($roomItem): array
    {
        $userList = $roomItem->getUserList();

        return !$userList ? [] : $userList->to_array();
    }

    /**
     * For the room with the given room ID, returns all users who have the status of a contact person or moderator.
     * Note that if some contact person(s) have been defined for the room, only these will be returned. Otherwise,
     * the room moderators will be returned.
     *
     * @param int $roomId The ID of the containing context
     *
     * @return cs_user_item[] An array of users who are contact persons or moderators of the room with the given room ID
     */
    public function getContactModeratorItems($roomId): array
    {
        $roomItem = $this->getRoomItem($roomId);
        $contactModeratorList = $roomItem->getContactModeratorList();

        return $contactModeratorList->to_array();
    }

    public function getCurrentRoomItem(): ?cs_room_item
    {
        $currentContextId = $this->legacyEnvironment->getCurrentContextId();

        return $this->getRoomItem($currentContextId);
    }

    /**
     * @param int $roomId
     */
    public function getRoomItem($roomId): ?cs_room_item
    {
        /**
         * NOTE: returning archived rooms here as a fallback if no room or private room item was found
         * currently impacts at least the "all rooms" feed due to the fact, that it relies on this function
         * returning null, if the room is archived.
         *
         * NOTE: for a guest user, $roomItem may be also null
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

    /**
     * Returns all community rooms that host the given (project) room.
     *
     * @param cs_room_item $room The room whose related community rooms shall be returned
     *
     * @return cs_community_item[] Array of community rooms that host the given (project) room
     */
    public function getCommunityRoomsForRoom(cs_room_item $room): array
    {
        // NOTE: we don't use $room->getCommunityList() here since that method may incorrectly set the room limit
        //       to the current context (instead of the room's context); this e.g. happens if this method gets
        //       called for a project room's detail page within a community room

        $linkItemManager = $this->legacyEnvironment->getLinkItemManager();
        $linkItemManager->resetLimits();
        $linkItemManager->setLinkedItemLimit($room);
        $linkItemManager->setTypeLimit(CS_COMMUNITY_TYPE);
        $linkItemManager->setRoomLimit($room->getContextID());
        $linkItemManager->select();
        $linkList = $linkItemManager->get();

        $communityRooms = [];
        foreach ($linkList as $linkItem) {
            $communityRoom = $linkItem->getLinkedItem($room);
            if ($communityRoom) {
                $communityRooms[] = $communityRoom;
            }
        }

        return $communityRooms;
    }

    /**
     * Returns the IDs of all given rooms.
     *
     * @param cs_room_item[] $rooms The array of rooms whose IDs shall be returned
     *
     * @return int[]
     */
    public function getIdsForRooms(array $rooms): array
    {
        if (empty($rooms)) {
            return [];
        }

        $roomIds = array_map(fn (cs_room_item $room) => $room->getItemID(), $rooms);

        return $roomIds;
    }

    public function getFilterableRubrics(int $roomId, Request $request): array
    {
        // get active rubrics
        $rubrics = $this->getRubricInformation($roomId);

        // do not add the group filter on app_group_list
        if ($request->attributes->get('_route') === 'app_group_list') {
            $rubrics = array_filter($rubrics, fn ($rubric) => $rubric !== 'group');
        }

        // filter rubrics, only group, topic and institution type is filterable
        return array_filter($rubrics, fn ($rubric) => in_array($rubric, ['group', 'topic', 'institution']));
    }

    public function getRoomTitle($roomId)
    {
        // return room title
        $roomItem = $this->getRoomItem($roomId);

        return $roomItem->getTitle();
    }

    public function getRoomFileDirectory($roomId)
    {
        $roomDir = implode('/', array_filter(explode("\r\n", chunk_split(strval($roomId), '4')), 'strlen'));

        return $this->legacyEnvironment->getCurrentPortalID().'/'.$roomDir.'_';
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
     * Returns the array of time pulses specified for the current portal.
     *
     * @param bool $reverseOrder whether the list of time pulses shall be returned in reverse
     *                           order (true) or not (false); defaults to false
     */
    public function getTimePulses($reverseOrder = false): array
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
            if (strstr((string) $remoteServiceLink, '%')) {
                $textConverter = $this->legacyEnvironment->getTextConverter();
                $remoteServiceLink = $textConverter->convertPercent($remoteServiceLink, true, true);
            }

            return $remoteServiceLink;
        } else {
            $serviceEmail = $this->getServiceEmail();

            if (!empty($serviceEmail)) {
                return 'mailto:'.$serviceEmail;
            }
        }

        return '';
    }

    /**
     * Returns the service email address specified for the current portal or server.
     */
    public function getServiceEmail(): string
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        $serviceEmail = null;

        if ($portalItem) {
            $serviceEmail = $portalItem->getServiceEmail();
        }

        if (empty($serviceEmail)) {
            $serverItem = $this->legacyEnvironment->getServerItem();
            $serviceEmail = $serverItem->getServiceEmail();
        }

        return $serviceEmail ?? '';
    }

    /**
     * Returns all room templates available for the given room type.
     *
     * @param string $type the type of the room
     *
     * @return array array of room template IDs keyed by room title & ID
     */
    public function getAvailableTemplates(string $roomType): array
    {
        $templates = [];

        $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($this->legacyEnvironment->getCurrentPortalItem()->getItemID());
        $roomManager->setTemplateLimit();
        $roomManager->select();

        $templateList = $roomManager->get();
        foreach ($templateList as $template) {
            $availability = $template->getTemplateAvailability(); // $roomType === 'project'
            if ('community' === $roomType) {
                $availability = $template->getCommunityTemplateAvailability();
            }

            $add = false;

            // free for all?
            if (!$add && '0' == $availability) {
                $add = true;
            }

            // only in community rooms
            if (!$add && $this->legacyEnvironment->inCommunityRoom() && '3' == $availability) {
                $add = true;
            }

            // same as above, but from portal context
            if (!$add && $this->legacyEnvironment->inPortal() && '3' == $availability) {
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
            if (!$add && '1' == $availability && $template->mayEnter($currentUserItem)) {
                $add = true;
            }

            // only mods
            if (!$add && '2' == $availability && $template->mayEnter($currentUserItem)) {
                if ($template->isModeratorByUserID($currentUserItem->getUserID(), $currentUserItem->getAuthSource())) {
                    $add = true;
                }
            }

            if ($roomType != $template->getItemType()) {
                $add = false;
            }

            if ($add) {
                $label = $template->getTitle().' (ID: '.$template->getItemID().')';
                $templates[$label] = $template->getItemID();
            }
        }

        return $templates;
    }
}
