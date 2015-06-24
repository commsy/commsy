<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use CommsyBundle\Filter\UserFilterType;

class LinkController extends Controller
{
    /**
     * @Route("/room/{roomId}/link/{itemId}")
     * @Template()
     */
    public function linkAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy.label_service');
        
        $groups = array();
        $users = array();
        
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy.group_service');
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $users = $membersList->to_array();
            }
        } else {
            $ids = $item->getAllLinkeditemIDArray();
            foreach ($ids as $id) {
                $tempItem = $itemService->getItem($id);
                if ($tempItem->getItemType() == 'label') {
                    $tempLabel = $labelService->getLabel($id);
                    if ($tempLabel->getLabelType() == 'group') {
                        $groups[] = $tempLabel;
                    }
                }
            }
        }
        
        dump($users);
        
        return array(
            'groups' => $groups,
            'users' => $users
        );
    }
}
