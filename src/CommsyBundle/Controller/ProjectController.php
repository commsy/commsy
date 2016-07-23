<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\ProjectType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Filter\ProjectFilterType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;

class ProjectController extends Controller
{    
    /**
     * @Route("/room/{roomId}/project/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $projects = $projectService->getListProjects($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        foreach ($projects as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUser();

        return array(
            'roomId' => $roomId,
            'projects' => $projects,
            'readerList' => $readerList,
            'currentUser' => $currentUser
        );
    }
    
    /**
     * @Route("/room/{roomId}/project")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'project',
            'itemsCountArray' => $itemsCountArray
        );
    }
    
    
    /**
     * @Route("/room/{roomId}/project/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        return array(
        );
    }
    
    
    /**
     * @Route("/room/{roomId}/project/{itemId}/request", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function requestAction($roomId, $itemId, Request $request)
    {
        $formData = array();
        $formOptions = array();
        $form = $this->createForm(ProjectType::class, $formData, $formOptions);
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();
                
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                
                $roomManager = $legacyEnvironment->getRoomManager();
		        $roomItem = $roomManager->getItem($itemId);
		      
		        $currentItemId = $itemId;
                if(empty($roomItem)){
                    $grouproomFlag = true;
                    $roomItem = $roomManager->getItem($additional['context_id']);
                    $currentItemId = $additional['context_id'];
                    // label item holen und addmember ausführen wenn kein member
                    $labelManager = $legacyEnvironment->getLabelManager();
                    $labelItem = $labelManager->getItem($itemId);
		        }
		        
                $portalItem = $legacyEnvironment->getCurrentPortalItem();
                $agbFlag = false;
		      
                if($portalItem->withAGBDatasecurity()){
				    if($roomItem->getAGBStatus() == 1){
					    if($formData['agb']){
						    $agbFlag = true;
					    } else {
						    $agbFlag = false;
					    }
				    } else {
					    $agbFlag = true;
				    }
			    } else {
                    $agbFlag = true;
			    }
			  
                // build new user_item
                if ((!$roomItem->checkNewMembersWithCode() or ($roomItem->getCheckNewMemberCode() == $formData['code']) or ($roomItem->getCheckNewMemberCode() and !empty($formData['description']))) and $agbFlag) {
                    $currentUser = $legacyEnvironment->getCurrentUserItem();
                    $privateRoomUserItem = $currentUser->getRelatedPrivateRoomUserItem();
                    if (isset($privateRoomUserItem)) {
		                $userItem = $privateRoomUserItem->cloneData();
                        $picture = $privateRoomUserItem->getPicture();
		            } else {
		                $userItem = $currentUser->cloneData();
                        $picture = $currentUser->getPicture();
		            }
                    $userItem->setContextID($currentItemId);
                    if (!empty($picture)) {
		                $valueArray = explode('_',$picture);
                        $valueArray[0] = 'cid'.$userItem->getContextID();
                        $new_picture_name = implode('_',$valueArray);
                        $discManager = $legacyEnvironment->getDiscManager();
                        $discManager->copyImageFromRoomToRoom($picture,$userItem->getContextID());
                        $userItem->setPicture($new_picture_name);
		            }
                    if (isset($formData['description'])) {
		                $userItem->setUserComment($formData['description']);
		            }

                    //check room_settings
                    if ((!$roomItem->checkNewMembersNever() and !$roomItem->checkNewMembersWithCode()) or ($roomItem->checkNewMembersWithCode() and $roomItem->getCheckNewMemberCode() != $formData['code'])) {
		                $userItem->request();
                        $checkMessage = 'YES'; // for mail body
                        $accountMode = 'info';
		            } else {
		                $userItem->makeUser(); // for mail body
                        $checkMessage = 'NO';
                        $accountMode = 'to_room';
                        // save link to the group ALL
                        $groupManager = $legacyEnvironment->getLabelManager();
                        $groupManager->setExactNameLimit('ALL');
                        $groupManager->setContextLimit($currentItemId);
                        $groupManager->select();
                        $groupList = $groupManager->get();
                        if ($groupList->getCount() == 1) {
                            $group = $groupList->getFirst();
                            $group->setTitle('ALL');
                            $userItem->setGroupByID($group->getItemID());
		                }
		            
                        if(isset($labelItem) and !empty($labelItem)){
		            	    if(!$labelItem->isMember($currentUser)){
		            		    $labelItem->addMember($currentUser);
		            	    }
		                }
    		        }
		         
                    if($portalItem->withAGBDatasecurity()){
                        if($roomItem->getAGBStatus()){
                            if($formData['agb']){
                                $userItem->setAGBAcceptance();
		                    }
		                }
		            }
		      
                    // test if user id already exists (reload page)
                    $userId = $userItem->getUserID();
                    $userTestItem = $roomItem->getUserByUserID($userId,$userItem->getAuthSource());
                    if (!isset($userTestItem) and mb_strtoupper($userId, 'UTF-8') != 'GUEST' and mb_strtoupper($userId, 'UTF-8') != 'ROOT') {
		                $userItem->save();
                        $userItem->setCreatorID2ItemID();
		      
                        // save task
                        if (!$roomItem->checkNewMembersNever() and !$roomItem->checkNewMembersWithCode()) {
                            $taskManager = $legacyEnvironment->getTaskManager();
                            $taskItem = $taskManager->getNewItem();
                            $currentUser = $legacyEnvironment->getCurrentUserItem();
                            $taskItem->setCreatorItem($currentUser);
                            $taskItem->setContextID($roomItem->getItemID());
                            $taskItem->setTitle('TASK_USER_REQUEST');
                            $taskItem->setStatus('REQUEST');
                            $taskItem->setItem($userItem);
                            $taskItem->save();
    		            }
    		      
    		            // send email to moderators if necessary
    		            $userManager = $legacyEnvironment->getUserManager();
    		            $userManager->resetLimits();
    		            $userManager->setModeratorLimit();
    		            $userManager->setContextLimit($currentItemId);
    		            $userManager->select();
    		            $userList = $userManager->get();
    		            $emailAddresses = array();
    		            $moderatorItem = $userList->getFirst();
    		            $recipients = '';
    		            while ($moderatorItem) {
    		                $wantsMail = $moderatorItem->getAccountWantMail();
                            if (!empty($wantsMail) and $wantsMail == 'yes') {
    		                    $emailAddresses[] = $moderatorItem->getEmail();
                                $recipients .= $moderatorItem->getFullname()."\n";
    		                }
                            $moderatorItem = $userList->getNext();
    		            }
    		      
    		            // language
    		            $language = $roomItem->getLanguage();
    		            if ($language == 'user') {
    		                $language = $userItem->getLanguage();
                            if ($language == 'browser') {
    		                    $language = $legacyEnvironment->getSelectedLanguage();
    		                }
    		            }
    		      
    		            /*
        		         *  ToDo: Send emails to new user and moderators. See cs_popup_userContextJoin_controller -> save() -> case 'context_join' for details.
        		         */
    		        }
		        } elseif ($roomItem->checkNewMembersWithCode() and $roomItem->getCheckNewMemberCode() != $formData['code']) {
		            $accountMode = 'member';
                    $error = 'code';
                    //$this->_popup_controller->setErrorReturn(111, 'wrong_code', array());
		        } elseif (!$agbFlag and $portalItem->withAGBDatasecurity() and $roomItem->getAGBStatus() == 1){
                    //$this->_popup_controller->setErrorReturn(115, 'agb_not_accepted', array());
		        }
		      
                if ($accountMode =='to_room'){
                    $data['cid'] = $legacyEnvironment->getCurrentContextID();
                    if($labelItem){
                        $data['item_id'] = $labelItem->getItemID();
                        $data['mod'] = 'group';
		            } else {
                        $data['item_id'] = $roomItem->getItemID();
                        $data['mod'] = 'project';
		            }
                    //$this->_popup_controller->setSuccessfullDataReturn($data);
		        } else {
                    $data['cid'] = $legacyEnvironment->getCurrentContextID();
                    if($labelItem){
                        $data['item_id'] = $labelItem->getItemID();
                        $data['mod'] = 'group';
		            } else {
                        $data['item_id'] = $roomItem->getItemID();
                        $data['mod'] = 'project';
		            }
                    //$this->_popup_controller->setSuccessfullDataReturn($data);
		        }
		        
            } else {
                // ToDo ...
            }
            
            return $this->redirectToRoute('commsy_room_home', array('roomId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }
        
        return array(
            'form' => $form->createView()
        );
    }
    
}
