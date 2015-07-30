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

        $privateRoomItem = $userItem->getOwnRoom();
        $userData['newsletterStatus'] = $privateRoomItem->getPrivateRoomNewsletterActivity();
        if ($privateRoomItem->getCSBarShowWidgets() == '1') {
            $userData['widgetStatus'] = false;
        } else {
            $userData['widgetStatus'] = true;
        }
        if ($privateRoomItem->getCSBarShowCalendar() == '1') {
            $userData['calendarStatus'] = false;
        } else {
            $userData['calendarStatus'] = true;
        }
        if ($privateRoomItem->getCSBarShowStack() == '1') {
            $userData['stackStatus'] = false;
        } else {
            $userData['stackStatus'] = true;
        }
        if ($privateRoomItem->getCSBarShowPortfolio() == '1') {
            $userData['portfolioStatus'] = false;
        } else {
            $userData['portfolioStatus'] = true;
        }
        if ($privateRoomItem->getCSBarShowOldRoomSwitcher() == '1') {
            $userData['switchRoomStatus'] = false;
        } else {
            $userData['switchRoomStatus'] = true;
        }

        $form = $this->createForm('room_profile', $userData, array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('commsy_profile_room', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $userItem = $transformer->applyTransformation($userItem, $form->getData());

            $userItem->save();

            $privateRoomItem = $userItem->getOwnRoom();
            $privateRoomItem->setPrivateRoomNewsletterActivity($userData['newsletterStatus']);
            $privateRoomItem->setCSBarShowWidgets($userData['widgetStatus']);
            $privateRoomItem->setCSBarShowCalendar($userData['calendarStatus']);
            $privateRoomItem->setCSBarShowStack($userData['stackStatus']);
            $privateRoomItem->setCSBarShowPortfolio($userData['portfolioStatus']);
            $privateRoomItem->setCSBarShowOldRoomSwitcher($userData['switchRoomStatus']);
            
            $privateRoomItem->save();
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($user);
            // $em->flush();
        }

        return array(
            'roomId' => $roomId,
            'user' => $userItem,
            'form' => $form->createView()
        );
    }

    
}