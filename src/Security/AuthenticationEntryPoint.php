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

namespace App\Security;

use App\Utils\RequestContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

readonly class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private RequestContext $requestContext,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $portal = $this->requestContext->fetchPortal($request);

        // An id straight out of the URL may resolve to nothing, and its login
        // page would answer 404 — telling a visitor which ids exist.
        $context = null !== $portal ? $portal->getId() : 'server';

        $url = $this->urlGenerator->generate('app_login', [
            'context' => $context,
        ]);

        return new RedirectResponse($url);
    }
}
