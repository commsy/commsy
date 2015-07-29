<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Entity\User;
use CommsyBundle\Form\Type\RoomProfileType;

class ProfileController extends Controller
{
    /**
    * @Route("/room/{roomId}/user/{itemId}/settings")
    * @Template
    */
    public function roomAction($roomId, $itemId, Request $request)
    {
        // get room from RoomService
        $userService = $this->get('commsy.user_service');
        $userItem = $userService->getUser($itemId);

        // $user = $this->getDoctrine()
        //     ->getRepository('CommsyBundle:User')
        //     ->find($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }

        $transformer = $this->get('commsy_legacy.transformer.user');
        $userData = $transformer->transform($userItem);

        $form = $this->createForm('room_profile', $userData, array(
            'itemId' => $itemId
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $userItem = $transformer->applyTransformation($userItem, $form->getData());

            $userItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($user);
            // $em->flush();
        }

        return array(
            'form' => $form->createView()
        );
    }

    
}