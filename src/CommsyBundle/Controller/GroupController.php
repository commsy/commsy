<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Filter\GroupFilterType;

class GroupController extends Controller
{
    /**
     * @Route("/room/{roomId}/group")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // get the user manager service
        $groupService = $this->get('commsy.group_service');

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new GroupFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_group_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        // set filter conditions in user manager
        $groupService->setFilterConditions($form);

        // get material list from manager service 
        $groups = $groupService->getListGroups($roomId);

        return array(
            'roomId' => $roomId,
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/group/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new GroupFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_group_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        // get the material manager service
        $groupService = $this->get('commsy.group_service');

        // set filter conditions in material manager
        $groupService->setFilterConditions($form);

        // get material list from manager service 
        $groups = $groupService->getListGroups($roomId, $max, $start);

        return array(
            'roomId' => $roomId,
            'groups' => $groups,
        );
    }
    
    /**
     * @Route("/room/{roomId}/group/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $groupService = $this->get('commsy.group_service');
        $group = $groupService->getGroup($itemId);
        
        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();
        
        return array(
            'group' => $group,
            'members' => $members
        );
    }
}
