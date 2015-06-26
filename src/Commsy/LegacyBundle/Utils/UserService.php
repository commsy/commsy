<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class UserService
{
    private $legacyEnvironment;

    private $userManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
    }

    public function getUser($userId)
    {
        
        $user = $this->userManager->getItem($userId);
        return $user;
    }
    
    public function getListUsers($roomId, $max, $start)
    {
        $this->userManager->setContextLimit($roomId);
        $this->userManager->setUserLimit();
        $this->userManager->setIntervalLimit($start, $max);
        
        $this->userManager->select();
        $userList = $this->userManager->get();

        return $userList->to_array();
    }
    
    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->userManager->showNoNotActivatedEntries();
        }
    }
}