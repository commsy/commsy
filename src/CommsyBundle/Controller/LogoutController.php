<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class LogoutController extends Controller
{
    /**
     * @Route("/room/{roomId}/logout")
     */
    public function logoutAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $contextId = $legacyEnvironment->getCurrentContextId();
        $userId = $legacyEnvironment->getCurrentUserID();

        $baseUrl = $request->getBaseUrl();

        $url = $baseUrl . '?cid=' . $contextId . '&mod=context&fct=logout&iid=' . $userId;

        return $this->redirect($url);
    }
}
