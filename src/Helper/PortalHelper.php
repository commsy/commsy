<?php

namespace App\Helper;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_list;
use cs_time_item;

class PortalHelper
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getTitleOfCurrentTime(Portal $portal)
    {
        $retour = '';

        $current_year = date('Y');
        $year = $current_year - 1;
        $current_date = getCurrentDate();
        $clock_pulse_array = $portal->getTimeTextArray();
        $found = false;
        while (!$found and $year < $current_year + 1) {
            foreach ($clock_pulse_array as $key => $clock_pulse) {
                if (isset($clock_pulse['BEGIN'][3])
                    and isset($clock_pulse['BEGIN'][4])
                ) {
                    $begin_month = $clock_pulse['BEGIN'][3] . $clock_pulse['BEGIN'][4];
                } else {
                    $begin_month = '';
                }
                if (isset($clock_pulse['BEGIN'][0])
                    and isset($clock_pulse['BEGIN'][1])
                ) {
                    $begin_day = $clock_pulse['BEGIN'][0] . $clock_pulse['BEGIN'][1];
                } else {
                    $begin_day = '';
                }
                if (isset($clock_pulse['END'][3])
                    and isset($clock_pulse['END'][4])
                ) {
                    $end_month = $clock_pulse['END'][3] . $clock_pulse['END'][4];
                } else {
                    $end_month = '';
                }
                if (isset($clock_pulse['END'][0])
                    and isset($clock_pulse['END'][1])
                ) {
                    $end_day = $clock_pulse['END'][0] . $clock_pulse['END'][1];
                } else {
                    $end_day = '';
                }
                $begin = $begin_month . $begin_day;
                $end = $end_month . $end_day;
                if ($begin > $end) {
                    $begin = $year . $begin;
                    $end = ($year + 1) . $end;
                } else {
                    $begin = $year . $begin;
                    $end = $year . $end;
                }
                if ($begin <= $current_date
                    and $current_date <= $end
                ) {
                    $found = true;
                    $retour = $year . '_' . $key;
                }
            }
            $year++;
        }

        return $retour;
    }

    public function getContinuousRoomListNotLinkedToTime(Portal $portal, cs_time_item $time): cs_list
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($portal->getId());
        $roomManager->setContinuousLimit();
        $roomManager->setOpenedLimit();
        $roomManager->select();
        $id_array1 = $roomManager->getIdArray();
        $roomManager->setTimeLimit($time->getItemID());
        $roomManager->select();
        $id_array2 = $roomManager->getIdArray();
        if (is_array($id_array1) and is_array($id_array2)) {
            $id_array3 = array_diff($id_array1, $id_array2);
            if (!empty($id_array3)) {
                $roomManager->resetLimits();
                $roomManager->setIDArrayLimit($id_array3);
                $roomManager->select();
                return $roomManager->get();
            }
        }

        return new cs_list();
    }

    /**
     * @param Portal $portal
     * @return cs_list
     */
    public function getRoomList(Portal $portal): cs_list
    {
        $roomList = new cs_list();

        $communityManager = $this->legacyEnvironment->getCommunityManager();
        $communityManager->setContextLimit($portal->getId());
        $communityManager->select();
        $roomList->addList($communityManager->get());

        $projectManager = $this->legacyEnvironment->getProjectManager();
        $projectManager->setContextLimit($portal->getId());
        $projectManager->select();
        $roomList->addList($projectManager->get());

        return $roomList;
    }

    public function getActiveRoomsInPortal(Portal $portal): cs_list
    {
        $rooms = new cs_list();

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
        $privateRoomManager->reset();
        $privateRoomManager->setContextLimit($portal->getId());
        $privateRoomManager->setActiveLimit();
        $privateRoomManager->select();
        $rooms->addList($privateRoomManager->get());

        $communityManager = $this->legacyEnvironment->getCommunityManager();
        $communityManager->reset();
        $communityManager->setContextLimit($portal->getId());
        $communityManager->setActiveLimit();
        $communityManager->select();
        $rooms->addList($communityManager->get());

        $projectManager = $this->legacyEnvironment->getProjectManager();
        $projectManager->reset();
        $projectManager->setContextLimit($portal->getId());
        $projectManager->setActiveLimit();
        $projectManager->select();
        $rooms->addList($projectManager->get());

        $groupRoomManager = $this->legacyEnvironment->getGroupRoomManager();
        $groupRoomManager->reset();
        $groupRoomManager->setContextLimit($portal->getId());
        $groupRoomManager->setActiveLimit();
        $groupRoomManager->select();
        $rooms->addList($groupRoomManager->get());

        $userRoomManager = $this->legacyEnvironment->getUserRoomManager();
        $userRoomManager->reset();
        $userRoomManager->setContextLimit($portal->getId());
        $userRoomManager->setActiveLimit();
        $userRoomManager->select();
        $rooms->addList($userRoomManager->get());

        return $rooms;
    }
}