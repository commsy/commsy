<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class UserService
{
    private $legacyEnvironment;

    private $userManager;

    private $roomManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        
        $this->userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
        $this->userManager->reset();

        $this->roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $this->roomManager->reset();
    }

    public function getUser($userId)
    {
        
        $user = $this->userManager->getItem($userId);
        return $user;
    }
    
    public function getPortalUserFromSessionId()
    {
        if (isset($_COOKIE['SID'])) {
            $sid = $_COOKIE['SID'];
            
            $sessionManager = $this->legacyEnvironment->getEnvironment()->getSessionManager();
            $sessionItem = $sessionManager->get($sid);

            if ($sessionItem) {
                $userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
                $userList = $userManager->getAllUserItemArray($sessionItem->getValue('user_id'));
                $portalUser = NULL;
                if (!empty($userList)) {
                    //$contextID = $userList[0]->getContextId();
                    //$portalUser = $userList[0];
                    foreach ($userList as $user) {
                        //if ($user->getContextId() < $contextID) {
                        //    $contextID = $user->getContextId();
                        //    $portalUser = $user;
                        //}
                        if ($user->getAuthSource() == $sessionItem->getValue('auth_source')) {
                            $portalUser = $user;
                        }
                    }
                }
                return $portalUser;
            }
        }
    }

    public function getRoomList($userId)
    {
        $roomList = $this->roomManager->getRelatedRoomListForUser($userId);
        return $roomList->to_array();
    }
    
    public function getListUsers($roomId, $max, $start)
    {
        $this->userManager->reset();
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