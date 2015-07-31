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

        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm('room_profile', $userData, array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('commsy_profile_room', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
    
                $userItem->save();
    
                $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
                
                $privateRoomItem->save();
                
                // persist
                // $em = $this->getDoctrine()->getManager();
                // $em->persist($user);
                // $em->flush();
            } else if ($form->get('saveCombine')->isClicked()) {
                
            }
            
            return $this->redirectToRoute('commsy_profile_room', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'roomId' => $roomId,
            'user' => $userItem,
            'form' => $form->createView()
        );
    }

    
}