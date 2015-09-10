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
        $groups = $groupService->getListGroups($roomId, 10, 0);

        return array(
            'roomId' => $roomId,
            'form' => $form->createView(),
            'module' => 'group'
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

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($groups as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList
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

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $group;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        
        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();
        
        return array(
            'roomId' => $roomId,
            'group' => $group,
            'members' => $members
        );
    }
}
