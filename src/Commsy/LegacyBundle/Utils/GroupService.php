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
    }

    public function getGroup($itemId)
    {
        $user = $this->groupManager->getItem($userId);
        return $user;
    }
    
    public function getListGroups($roomId)
    {
        $this->groupManager->setContextLimit($roomId);
        //$this->groupManager->setUserLimit();

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