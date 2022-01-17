<?php

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use App\Entity\IdP;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShibbolethController extends AbstractController
{
    /**
     * @Route("/login/{portalId}/auth/shib/{idpsUri}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     */
    public function authShibbolethInit(
        Portal $portal,
        $idpsUri = null,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check that we have an enabled shibboleth authentication
        $authSources = $portal->getAuthSources();

        // fetch the chosen ipd by its ID
        $idpRepo = $entityManager->getRepository(Idp::Class);
        $idp = $idpRepo->find($idpsUri);

        /** @var AuthSourceShibboleth $shibSource */
        $shibSource = $authSources->filter(function (AuthSource $authSource) {
            return $authSource instanceof AuthSourceShibboleth && $authSource->isEnabled();
        })->first();

        if ($shibSource === false) {
            throw $this->createAccessDeniedException();
        }

        /*
         * The URL the Idp will send us back is provided as target parameter below. We generate this from the dummy
         * route below. The authentication process itself is handled by the ShibbolethAuthenticator.
         */
        $returnUrl = $this->generateUrl('app_shibboleth_authshibbolethcheck', [
            'context' => $portal->getId()
        ],UrlGeneratorInterface::ABSOLUTE_URL);

        // redirect to Idp
        $initiatorUrl = $shibSource->getLoginUrl() . '?target=' . urlencode($returnUrl) .'&entityID=' . urlencode($idp->getUrl());
        return $this->redirect($initiatorUrl);
    }

    /**
     * IMPORTANT: DO NOT REMOVE OR RENAME {context} or keep in sync with ShibbolethAuthenticator
     *
     * @Route("/login/{context}/auth/shib/check")
     * @throws Exception
     */
    public function authShibbolethCheck()
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Handled by guard authenticator');
    }
}
