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

    public function getGroup($itemId)
    {
        $group = $this->groupManager->getItem($itemId);
        return $group;
    }
    
    public function getListGroups($roomId, $max, $start)
    {
        $this->groupManager->reset();
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->setIntervalLimit($start, $max);
        
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