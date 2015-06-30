<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Entity\Room;
use CommsyBundle\Form\Type\GeneralSettingsType;

class SettingsController extends Controller
{
    /**
    * @Route("/room/{roomId}/settings/")
    * @Template
    * @Security("is_granted('MODERATOR')")
    */
    public function dashboardAction($roomId, Request $request)
    {
        return array();
    }

    /**
    * @Route("/room/{roomId}/settings/general")
    * @Template
    * @Security("is_granted('MODERATOR')")
    */
    public function generalAction($roomId, Request $request)
    {
        $room = $this->getDoctrine()
            ->getRepository('CommsyBundle:Room')
            ->find($roomId);

        if (!$room) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $form = $this->createForm(new GeneralSettingsType(), $room);
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            // persist
            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $em->flush();
        }

        return array(
            'form' => $form->createView()
        );
    }
}