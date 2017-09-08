<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;


class PortalController extends Controller
{
    /**
     * @Route("/portal/{roomId}/room/categories")
     * @Template()
     */
    public function roomcategoriesAction($roomId, Request $request)
    {

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();;

        return array(
            'roomId' => $roomId,
            'item' => $legacyEnvironment->getCurrentPortalItem(),
        );
    }

    /**
     * @Route("/portal/{roomId}/legacysettings")
     * @Template()
     */
    public function legacysettingsAction($roomId, Request $request)
    {
        return $this->redirect('/?cid='.$roomId.'&mod=configuration&fct=index');
    }
}
