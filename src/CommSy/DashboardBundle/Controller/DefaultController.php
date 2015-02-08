<?php

namespace CommSy\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('CommSyDashboardBundle:Default:index.html.twig', array());
    }
}
