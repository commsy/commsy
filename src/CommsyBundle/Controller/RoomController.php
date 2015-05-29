<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class RoomController extends Controller
{
    /**
     * @Route("/room/{roomId}")
     * @Template()
     */
    public function indexAction($roomId, Request $request)
    {   
        return array();
    }
}
