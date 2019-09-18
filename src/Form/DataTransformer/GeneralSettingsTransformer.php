<?php
namespace App\Form\DataTransformer;

use App\Utils\RoomService;
use App\Utils\UserService;
use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;
use cs_room_item;

class GeneralSettingsTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->userService = $userService;
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = array();

        $defaultRubrics = $roomItem->getAvailableDefaultRubricArray();

        if ($roomItem) {
            $roomData['title'] = html_entity_decode($roomItem->getTitle());
            $roomData['language'] = $roomItem->getLanguage();

            if ($roomItem->checkNewMembersAlways()) {
                $roomData['access_check'] = 'always';
            } else if ($roomItem->checkNewMembersNever()) {
                $roomData['access_check'] = 'never';
            } else if ($roomItem->checkNewMembersWithCode()) {
                $roomData['access_check'] = 'withcode';
                $roomData['access_code'] = $roomItem->getCheckNewMemberCode();
            }

            $roomData['room_description'] = $roomItem->getDescription();
            // $rubrics = array_combine($defaultRubrics, array_fill(0, count($defaultRubrics), 'off'));
            $rubrics = array();
            foreach ($this->roomService->getRubricInformation($roomItem->getItemID(), true) as $rubric) {
                list($rubricName, $modifier) = explode('_', $rubric);
                $rubrics[$rubricName] = $modifier;
            }
            foreach(array_diff($defaultRubrics, array_keys($rubrics)) as $deactivated_rubric){
                $rubrics[$deactivated_rubric] = 'off';
            }
            $roomData['rubrics'] = $rubrics;

            $roomData['assignment_restricted'] = $roomItem->isAssignmentOnlyOpenForRoomMembers();

            $roomData['open_for_guest'] = $roomItem->isOpenForGuests();

            $roomData['material_open_for_guest'] = $roomItem->isMaterialOpenForGuests();

            $linkedCommunityRooms = array();

            if (!$roomItem->isGroupRoom()) {
                foreach ($roomItem->getCommunityList()->to_array() as $key => $communityRoom) {
                    if ($communityRoom) {
                        $linkedCommunityRooms[] = $communityRoom->getItemID();
                    }
                }
                $roomData['community_rooms'] = $linkedCommunityRooms;
            }

            // time contexts
            if ($roomItem->isProjectRoom() || $roomItem->isGroupRoom()) {
                if ($roomItem->isContinuous()) {
                    $roomData['time_pulses'][] = 'cont';
                }

                $roomTimeList = $roomItem->getTimeList();

                if (!$roomTimeList->isEmpty()) {
                    $roomTimeItem = $roomTimeList->getFirst();
                    while ($roomTimeItem) {
                        $roomData['time_pulses'][] = $roomTimeItem->getItemID();

                        $roomTimeItem = $roomTimeList->getNext();
                    }
                }
            }
        }
        return $roomData;
    }

  /**
     * Save general settings
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        foreach(explode(",", $roomData['rubricOrder']) as $rubricName){
            $rubricValue = $roomData['rubrics'][$rubricName];
            if (strcmp($rubricValue, 'off') == 0) {
                continue;
            }
            $rubricArray[] = $rubricName . "_" . $rubricValue;
        }

        $roomObject->setHomeConf(implode($rubricArray, ','));

        $roomObject->setTitle($roomData['title']);

        if (isset($roomData['language'])) {
            $roomObject->setLanguage($roomData['language']);
        }

        if(isset($roomData['room_description'])) 
            $roomObject->setDescription(strip_tags($roomData['room_description']));
        else 
            $roomObject->setDescription('');

        // assignment
        if($roomObject->isProjectRoom() && isset($roomData['community_rooms'])) {
            /*
             * if assignment is mandatory, the array must not be empty
             */
            if ($this->legacyEnvironment->getCurrentPortalItem()->getProjectRoomLinkStatus() !== "mandatory" || sizeof($roomData['community_rooms']) > 0 )
            {
                $roomObject->setCommunityListByID(array_values($roomData['community_rooms']));
            }

        } elseif($roomObject->isCommunityRoom()) {
            if(isset($roomData['assignment_restricted'])) {
                if($roomData['assignment_restricted']) $roomObject->setAssignmentOnlyOpenForRoomMembers();
                else $roomObject->setAssignmentOpenForAnybody();
            }
            if(isset($roomData['open_for_guest'])){
                if($roomData['open_for_guest']) $roomObject->setOpenForGuests();
                else $roomObject->setClosedForGuests();
            }
            if(isset($roomData['material_open_for_guest'])){
                if($roomData['material_open_for_guest']) $roomObject->setMaterialOpenForGuests();
                else $roomObject->setMaterialClosedForGuests();
            }
        }

        // check member
        if (isset($roomData['access_check'])) {
            switch($roomData['access_check']) {
                case "never":
                    $this->userService->grantAccessToAllPendingApplications();
                    $roomObject->setCheckNewMemberNever();
                    break;
                case "always":
                    $roomObject->setCheckNewMemberAlways();
                    break;
                case "withcode":
                    $roomObject->setCheckNewMemberWithCode();
                    $roomObject->setCheckNewMemberCode($roomData['access_code']);
                    break;
            }
        }

        // time context
        if (isset($roomData['time_pulses'])) {
            if (in_array('cont', $roomData['time_pulses'])) {
                $roomObject->setContinuous();
                $roomObject->setTimeListByID([]);
            } else {
                $roomObject->setNotContinuous();
                $roomObject->setTimeListByID($roomData['time_pulses']);
            }
        }

        return $roomObject;
    }
}
