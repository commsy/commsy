<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LogoutController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/logout")
     */
    public function logoutAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $session = $legacyEnvironment->getSessionItem();
        $sessionId = $session->getSessionID();
        $cookie = $session->getValue('cookie');

        // restore root session
        if ($session->issetValue('root_session_id')) {
            $rootSessionId = $session->getValue('root_session_id');
        }

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionManager->delete($sessionId, true);

        $session->reset();

        $portal = $legacyEnvironment->getCurrentPortalItem();

        $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

        // restore root session
        if (isset($rootSessionId)) {
            $session = $sessionManager->get($rootSessionId);
            $session->setValue('cookie',2);
            $legacyEnvironment->setSessionItem($session);
            if ($cookie != 1) {
                $url .= '&SID='.$rootSessionId;
            }
        }

        return $this->redirect($url);
    }
}
