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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;

abstract class AbstractCommsyAuthenticator extends AbstractAuthenticator
{
    public const LAST_SOURCE = '_security.last_source';

    public function __construct(
        protected UrlGeneratorInterface $urlGenerator,
        protected RequestContext $requestContext
    ) {
    }

    /**
     * When app_login is submitted, this post parameter will be checked in order to decide
     * which authenticator should be used.
     */
    abstract protected function getPostParameterName(): string;

    /**
     * This check must be implemented to ensure the current authentication method is supported by the
     * actual portal configuration.
     */
    abstract protected function isSupportedByPortalConfiguration(Request $request): bool;

    public function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login', [
            'context' => $request->attributes->get('context'),
        ]);
    }

    public function getCredentials(Request $request): array
    {
        $credentials = [
            'email' => $request->request->get('email'),
            'password' => $request->request->get('password'),
            'context' => $request->request->get('context'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST')
            && $request->request->has($this->getPostParameterName())
            && $this->isSupportedByPortalConfiguration($request);
    }
}
