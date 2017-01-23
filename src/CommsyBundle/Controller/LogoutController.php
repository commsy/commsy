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

        $session = $legacyEnvironment->getSessionItem();
        $sessionId = $session->getSessionID();

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionManager->delete($sessionId, true);

        $session->reset();

        $portal = $legacyEnvironment->getCurrentPortalItem();

        $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

        return $this->redirect($url);
    }
}
