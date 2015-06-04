<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends Controller
{
    /**
     * @Template("CommsyBundle:Category:show.html.twig")
     */
    public function showAction($roomId, Request $request)
    {   
        return array();
    }
}
