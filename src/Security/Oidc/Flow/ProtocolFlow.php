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

namespace App\Security\Oidc\Flow;

use App\Entity\AuthSourceOIDC;
use App\Security\Oidc\Discovery\ProviderMetadata;
use App\Security\Oidc\Request\ResponseType;
use Lcobucci\JWT\Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

interface ProtocolFlow
{
    public function prepareAuthorizationRequest(
        string $clientIdentifier,
        int $portalId,
        ProviderMetadata $metadata,
        ResponseType $responseType
    ): RedirectResponse;

    public function authenticate(Request $request, AuthSourceOIDC $authSource): ?Token;
}
