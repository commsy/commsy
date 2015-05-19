<?php

namespace Commsy\LegacyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        // // check for cid in GET and POST
        // $currentContextId = $request->get('cid');

        // if (!$currentContextId) {
        //     return $this->redirect('?cid=99');
        // }
        
        return array();
    }
}
