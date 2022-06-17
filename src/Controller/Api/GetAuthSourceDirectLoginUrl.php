<?php

namespace App\Controller\Api;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GetAuthSourceDirectLoginUrl extends AbstractController
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke(AuthSource $data): array
    {
        $authSource = $data;

        // shibboleth is the only authentication source supporting a direct login for now
        if ($authSource instanceof AuthSourceShibboleth) {
            return [
                'url' => $this->urlGenerator->generate('app_shibboleth_authshibbolethinit', [
                    'portalId' => $authSource->getPortal()->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return ['url' => null];
    }
}