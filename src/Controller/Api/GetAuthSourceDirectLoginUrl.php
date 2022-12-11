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

namespace App\Controller\Api;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GetAuthSourceDirectLoginUrl extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
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
