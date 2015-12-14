<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

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
        $this->groupManager->select();
        $countGroupArray['countAll'] = $this->groupManager->getCountAll();

        return $countGroupArray;
    }


    public function getGroup($itemId)
    {
        $group = $this->groupManager->getItem($itemId);
        return $group;
    }
    
    public function getListGroups($roomId, $max = NULL, $start = NULL)
    {
        $this->groupManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->groupManager->setIntervalLimit($start, $max);
        }

        $this->groupManager->select();
        $groupList = $this->groupManager->get();

        return $groupList->to_array();
    }
    
    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->groupManager->showNoNotActivatedEntries();
        }
    }
}