<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;

class DashboardFeedGenerator
{
    private $legacyEnvironment;
    private $roomService;
    private $userService;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
        $this->userService = $userService;
    }

    public function getFeedList($userId, $max, $start)
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $user = $this->userService->getPortalUserFromSessionId();
        $authSourceManager = $legacyEnvironment->getAuthSourceManager();
        $authSource = $authSourceManager->getItem($user->getAuthSource());
        $legacyEnvironment->setCurrentPortalID($authSource->getContextId());

        $currentUser=$legacyEnvironment->getCurrentUser();

        $room_id_array = array();
        $grouproom_list = $currentUser->getUserRelatedGroupList();
        if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
            $grouproom_list->reverse();
            $grouproom_item = $grouproom_list->getFirst();
            while ($grouproom_item) {
                $project_room_id = $grouproom_item->getLinkedProjectItemID();
                if ( in_array($project_room_id,$room_id_array) ) {
                    $room_id_array_temp = array();
                    foreach ($room_id_array as $value) {
                        $room_id_array_temp[] = $value;
                        if ( $value == $project_room_id) {
                            $room_id_array_temp[] = $grouproom_item->getItemID();
                        }
                    }
                    $room_id_array = $room_id_array_temp;
                }
                $grouproom_item = $grouproom_list->getNext();
            }
        }

        $project_list = $currentUser->getUserRelatedProjectList();
        if ( isset($project_list) and $project_list->isNotEmpty()) {
            $project_item = $project_list->getFirst();
            while ($project_item) {
                $room_id_array[] = $project_item->getItemID();
                $project_item = $project_list->getNext();
            }
        }
        $community_list = $currentUser->getUserRelatedCommunityList();
        if ( isset($community_list) and $community_list->isNotEmpty()) {
            $community_item = $community_list->getFirst();
            while ($community_item) {
                $room_id_array[] = $community_item->getItemID();
                $community_item = $community_list->getNext();
            }
        }
        $room_id_array_without_privateroom = $room_id_array;

        $userManager = $legacyEnvironment->getUserManager();
        $room_id_array_without_privateroom_with_member_status = [];
        foreach ($room_id_array_without_privateroom as $temp_room_id) {
            $tempUserArray = $userManager->getUserArrayByUserAndRoomIDLimit($currentUser->getUserId(), [$temp_room_id], $authSource->getItemId());
            if (!empty($tempUserArray)) {
                $room_id_array_without_privateroom_with_member_status[] = $temp_room_id;
            }
        }
        
        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextArrayLimit($room_id_array_without_privateroom_with_member_status);

        /**
         * TODO: Showing users without check the room configuration leads to data privacy issues.
         * TODO: Users must not be displayed if the user rubric in a room is disabled.
         */
        $itemManager->setTypeArrayLimit([/*'user', */'material', 'date', 'discussion', 'announcement', 'todo']);
        $itemManager->setIntervalLimit($max + $start);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        if ($itemList->getCount() < $start + $max) {
            $max = $itemList->getCount() - $start;
        }
        
        $itemList = $itemList->getSubList($start, $max);

        $feedList = array();
        $item = $itemList->getFirst();
        while ($item) {
            if (!$item->isNotActivated()) {
                $type = $item->getItemType();
                switch ($type) {
                    case 'date':
                        $datesManager = $this->legacyEnvironment->getEnvironment()->getDatesManager();
                        $dateItem = $datesManager->getItem($item->getItemId());
                        if ($dateItem) {
                            if ($dateItem->getRecurrencePattern() == '') {
                                $feedList[] = $dateItem;
                            } else {
                                $foundRecurrenceId = false;
                                foreach ($feedList as $feedListEntry) {
                                    if ($feedListEntry->getItemType() == CS_DATE_TYPE) {
                                        if ($feedListEntry->getRecurrenceId() == $dateItem->getRecurrenceId()) {
                                            $foundRecurrenceId = true;
                                        }
                                    }
                                }
                                if (!$foundRecurrenceId) {
                                    $feedList[] = $dateItem;
                                }
                            }
                        }
                        break;

                    case 'label':
                        $labelManager = $this->legacyEnvironment->getEnvironment()->getLabelManager();
                        $labelItem = $labelManager->getItem($item->getItemId());

                        if ($labelItem) {
                            if ($labelItem->getItemType() == 'group') {
                                $groupManager = $this->legacyEnvironment->getEnvironment()->getLabelManager();
                                $groupItem = $groupManager->getItem($item->getItemId());
                                if ($groupItem) {
                                    $feedList[] = $groupItem;
                                }
                            } else if ($labelItem->getItemType() == 'topic') {
                                $topicManager = $this->legacyEnvironment->getEnvironment()->getTopicManager();
                                $topicItem = $topicManager->getItem($item->getItemId());
                                if ($topicItem) {
                                    $feedList[] = $topicItem;
                                }
                            }
                        }

                        break;

                    case 'announcement':
                        $announcementManager = $this->legacyEnvironment->getEnvironment()->getAnnouncementManager();
                        $announcementItem = $announcementManager->getItem($item->getItemId());

                        if ($announcementItem) {
                            $currentDate = new \DateTime();
                            $announcementEndDate = new \DateTime($announcementItem->getSecondDateTime());
                            if ($currentDate <= $announcementEndDate) {
                                $feedList[] = $announcementItem;
                            }
                        }

                        break;

                    default:
                        $tempManager = $legacyEnvironment->getManager($item->getItemType());
                        $tempItem = $tempManager->getItem($item->getItemId());

                        if ($tempItem) {
                            $feedList[] = $tempItem;
                        }

                        break;
                }
            }
            $item = $itemList->getNext();
        }

        /* usort($feedList, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        }); */
        
        return $feedList;
    }


}
