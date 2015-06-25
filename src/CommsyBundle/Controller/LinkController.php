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
        
        $linkedItems = array();
        
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy.group_service');
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }
        
        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
        
        $returnArray = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $returnArray[] = $item;
        }

        return array(
            'linkedItems' => $returnArray
        );
    }
    
    
}
