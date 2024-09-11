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
use App\Entity\AuthSourceOIDC;
use App\Entity\Portal;
use App\Security\Oidc\Discovery\MetadataReader;
use App\Security\Oidc\Flow\AuthorizationCodeFlow;
use App\Security\Oidc\Request\CodeResponseType;
use App\Security\Oidc\Request\ResponseType;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OidcController extends AbstractController
{
    #[Route(path: '/login/{portalId}/auth/oidc')]
    public function authOidcInit(
        #[MapEntity(id: 'portalId')]
        Portal $portal,
        MetadataReader $metadataReader,
        AuthorizationCodeFlow $codeFlow,
        Request $request
    ): RedirectResponse {
        // Check that we have an enabled shibboleth authentication
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceOIDC $oidcSource */
        $oidcSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceOIDC && $authSource->isEnabled())->first();

        if (false === $oidcSource) {
            throw $this->createAccessDeniedException();
        }

        $metadata = $metadataReader->fetchRemoteConfiguration($oidcSource->getIssuer());
        return $codeFlow->prepareAuthorizationRequest(
            $oidcSource->getClientIdentifier(),
            $portal->getId(),
            $metadata,
            ResponseType::CODE
        );
    }

    /**
     * IMPORTANT: DO NOT REMOVE OR RENAME {context} or keep in sync with ShibbolethAuthenticator.
     *
     * @throws Exception
     */
    #[Route(path: '/login/{context}/auth/oidc/check')]
    public function authOidcCheck(): never
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Handled by guard authenticator');
    }
}
