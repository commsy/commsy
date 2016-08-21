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
        $projectsMemberStatus = array();
        foreach ($projects as $project) {
            $projectsMemberStatus[$project->getItemId()] = $this->memberStatus($project);
        }

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
            'projectsMemberStatus' => $projectsMemberStatus,
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

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('project') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('project');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('project');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'project',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo
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
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);
        
        $currentUser = $legacyEnvironment->getCurrentUser();
        
        return array(
            'roomId' => $roomId,
            'item' => $roomItem,
            'currentUser' => $currentUser
        );
    }
    
    function memberStatus ($item) {
        $status = 'closed';
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_user = $legacyEnvironment->getCurrentUserItem();
        if ($current_user->isRoot()) {
            $may_enter = true;
        } elseif ( !empty($room_user) ) {
            $may_enter = $item->mayEnter($room_user);
        } else {
            $may_enter = false;
        }
        
        if ($may_enter) {
            if ($item->isOpen()) {
                $status = 'enter';
            } else {
                $status = 'join';
            }
        } elseif ( $item->isLocked() ) {
            $status = 'locked';
        } elseif(!empty($room_user) and $room_user->isRequested()) {
            $status = 'requested';
        } elseif(!empty($room_user) and $room_user->isRejected()) {
            $status = 'rejected';
        }
        return $status;
    }
}
