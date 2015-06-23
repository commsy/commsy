<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Filter\UserFilterType;

class UserController extends Controller
{
    /**
     * @Route("/room/{roomId}/user")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // get the user manager service
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
        );
    }
    
    /**
     * @Route("/room/{roomId}/user/{itemId}")
     * @Template()
     */
    public function indexAction($roomId, $itemId, Request $request)
    {
        // get room user list
        $userService = $this->get("commsy.user_service");
        $user = $userService->getUser($itemId);
        
        return array(
            'user' => $user
        );
    }
}
