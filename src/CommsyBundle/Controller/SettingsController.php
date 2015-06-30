<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

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
}