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

    public function getCountArray($roomId)
    {
        $this->userManager->setContextLimit($roomId);
        $this->userManager->setUserLimit();
        $this->userManager->select();
        $countUser = array();
        $countUserArray['count'] = sizeof($this->userManager->get()->to_array());
        $this->userManager->resetLimits();
        $this->userManager->setUserLimit();
        $this->userManager->select();
        $countUserArray['countAll'] = $this->userManager->getCountAll();

        return $countUserArray;
    }


    public function resetLimits(){
        $this->userManager->resetLimits();
    }

    public function getListUsers($roomId, $max = NULL, $start = NULL)
    {
       $this->userManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->userManager->setIntervalLimit($start, $max);
        }
        $this->userManager->setUserLimit();
        $this->userManager->setOrder('name');
        $this->userManager->select();
        $userList = $this->userManager->get();

        $user_array = $userList->to_array();

        return $user_array;
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();


        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->userManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->userManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->userManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }
        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->userManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->userManager->setTagArrayLimit($categories);
                }
            }
        }
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

    public function grantAccessToAllPendingApplications(){
       //$requested_user_manager = $this->_environment->getUserManager();
       //$requested_user_manager->setContextLimit($this->_environment->getCurrentContextID());
       //$requested_user_manager->setRegisteredLimit();
       //$requested_user_manager->select();
       //$requested_user_list = $requested_user_manager->get();

       $this->user_manager->setContextLimit($this->_environment->getCurrentContextID());
       $this->user_manager->setRegisteredLimit();
       $this->user_manager->select();
       $requested_user_list = $this->user_manager->get();

       if (!empty($requested_user_list)){
          $requested_user = $requested_user_list->getFirst();
          while($requested_user){
             $requested_user->makeUser();
             $requested_user->save();
             $task_manager = $this->_environment->getTaskManager();
             $task_list = $task_manager->getTaskListForItem($requested_user);
             if (!empty($task_list)){
                $task = $task_list->getFirst();
                while($task){
                   if ($task->getStatus() == 'REQUEST' and ($task->getTitle() == 'TASK_USER_REQUEST' or $task->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                      $task->setStatus('CLOSED');
                      $task->save();
                   }
                   $task = $task_list->getNext();
                }
             }
             $requested_user = $requested_user_list->getNext();
          }
       }
    }
}
