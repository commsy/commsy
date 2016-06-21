<?php

namespace EtherpadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('EtherpadBundle:Default:index.html.twig');
    }
}
