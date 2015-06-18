<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class AnnouncementController extends Controller
{
    /**
     * @Route("/room/{roomId}/announcement/{announcementId}")
     * @Template()
     */
    public function indexAction($roomId, $announcementId, Request $request)
    {   
        return array();
    }

    /**
     * @Route("/room/{roomId}/announcement")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
    	return array(
            'roomId' => $roomId
        );
    }
}
