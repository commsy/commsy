<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $redirectContextId = 99;

        $httpHost = $request->getHttpHost();
        if ($httpHost) {
            $searchUrl = $httpHost;
            $requestUri = $request->getRequestUri();

            if ($requestUri) {
                $searchUrl .= dirname($requestUri);
            }

            if (mb_substr($searchUrl, mb_strlen($searchUrl)-1) == '/') {
                $searchUrl = mb_substr($searchUrl, 0, mb_strlen($searchUrl)-1);
            }

            $portalManager = $legacyEnvironment->getPortalManager();
            $portalManager->setUrlLimit($searchUrl);
            $portalManager->select();

            $portalList = $portalManager->get();
            if ($portalList->isNotEmpty()) {
                $numPortals = $portalList->getCount();

                if ($numPortals == 1) {
                    $portalItem = $portalList->getFirst();
                    if (isset($portalItem)) {
                        $redirectContextId = $portalItem->getItemID();
                    }
                }
            }

            // check server item url
            $serverItem = $legacyEnvironment->getServerItem();
            $serverUrl = $serverItem->getURL();
            if ($serverUrl == $searchUrl) {
                $redirectContextId = $serverItem->getItemID();
            }
        }

        // try default portal id
        $serverItem = $legacyEnvironment->getServerItem();
        $defaultPortalId = $serverItem->getDefaultPortalItemID();
        if (is_numeric($defaultPortalId)) {
            $redirectContextId = $defaultPortalId;
        }

        $url = $request->getBaseUrl() . '?cid=' . $redirectContextId;
        return $this->redirect($url);
    }
}
