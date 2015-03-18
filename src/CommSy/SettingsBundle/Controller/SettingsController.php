<?php

namespace CommSy\SettingsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SettingsController extends Controller
{
    public function indexAction()
    {
        return $this->render('CommSySettingsBundle:Settings:index.html.twig', array());
    }
}
