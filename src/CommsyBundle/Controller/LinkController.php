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
        /*// get the user manager service
        $userManager = $this->get('commsy.user_service');

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        // set filter conditions in user manager
        $userManager->setFilterConditions($form);

        // get material list from manager service 
        $materials = $userManager->getListUsers($roomId);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $materials,
            $request->query->getInt('page', 1),
            10
        );

        return array(
            'roomId' => $roomId,
            'pagination' => $pagination,
            'form' => $form->createView()
        );*/
        
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        $ids = $item->getAllLinkeditemIDArray();
        
        $labelService = $this->get('commsy.label_service');
        
        $groups = array();
        $linkedItems = array();
        foreach ($ids as $id) {
            $tempItem = $itemService->getItem($id);
            if ($tempItem->getItemType() == 'label') {
                $tempLabel = $labelService->getLabel($id);
                if ($tempLabel->getLabelType() == 'group') {
                    $groups[] = $tempLabel;
                }
            } else {
                $linkedItems[] = $tempItem;
            }
        }

        return array(
            'groups' => $groups
        );
    }
}
