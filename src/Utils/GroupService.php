<?php

namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;

class GroupService
{
    private $legacyEnvironment;

    private $groupManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;

        $this->groupManager = $this->legacyEnvironment->getEnvironment()->getGroupManager();
        $this->groupManager->reset();
    }


    public function getCountArray($roomId)
    {
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->select();
        $countGroup = array();
        $countGroupArray['count'] = sizeof($this->groupManager->get()->to_array());
        $this->groupManager->resetLimits();
        $this->groupManager->select();
        $countGroupArray['countAll'] = $this->groupManager->getCountAll();

        return $countGroupArray;
    }


    public function getGroup($itemId)
    {
        $group = $this->groupManager->getItem($itemId);
        return $group;
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return \cs_group_item[]
     */
    public function getListGroups($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->groupManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->groupManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->groupManager->setSortOrder($sort);
        }

        $this->groupManager->select();
        $groupList = $this->groupManager->get();

        return $groupList->to_array();
    }

    /**
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_group_item[]
     */
    public function getGroupsById($roomId, $ids) {
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->setIDArrayLimit($ids);

        $this->groupManager->select();
        $userList = $this->groupManager->get();

        return $userList->to_array();
    }
    
    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ($formData['hide-deactivated-entries'] === 'only_activated') {
                $this->groupManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'only_deactivated') {
                $this->groupManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'all') {
                $this->groupManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }
    }
    
    public function getNewGroup()
    {
        return $this->groupManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->groupManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }
}
