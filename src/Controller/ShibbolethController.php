<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use App\Entity\Portal;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShibbolethController extends AbstractController
{
    #[Route(path: '/login/{portalId}/auth/shib')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function authShibbolethInit(
        Portal $portal,
        Request $request
    ): Response {
        // Check that we have an enabled shibboleth authentication
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceShibboleth $shibSource */
        $shibSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceShibboleth && $authSource->isEnabled())->first();

        if (false === $shibSource) {
            throw $this->createAccessDeniedException();
        }

        /*
         * The URL the Idp will send us back is provided as target parameter below. We generate this from the dummy
         * route below. The authentication process itself is handled by the ShibbolethAuthenticator.
         */
        $returnUrl = $this->generateUrl('app_shibboleth_authshibbolethcheck', [
            'context' => $portal->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // redirect to Idp
        $initiatorUrl = $shibSource->getLoginUrl().'?target='.urlencode($returnUrl);

        // pass entityId if present
        if ($request->query->has('entityId')) {
            $entityId = urldecode($request->query->get('entityId', ''));
            $initiatorUrl .= '&entityID='.urlencode($entityId);
        }

        return $this->redirect($initiatorUrl);
    }

    /**
     * IMPORTANT: DO NOT REMOVE OR RENAME {context} or keep in sync with ShibbolethAuthenticator.
     *
     * @throws Exception
     */
    #[Route(path: '/login/{context}/auth/shib/check')]
    public function authShibbolethCheck()
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Handled by guard authenticator');
    }
}
