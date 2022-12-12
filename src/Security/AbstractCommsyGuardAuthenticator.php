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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use UnexpectedValueException;

abstract class AbstractCommsyGuardAuthenticator extends AbstractGuardAuthenticator
{
    public const LAST_SOURCE = '_security.last_source';

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

    public function __construct(protected UrlGeneratorInterface $urlGenerator, protected RequestContext $requestContext)
    {
    }

    public function getLoginUrl($context): string
    {
        return $this->urlGenerator->generate('app_login', [
            'context' => $context,
        ]);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST')
            && $request->request->has($this->getPostParameterName())
            && $this->isSupportedByPortalConfiguration($request);
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array).
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * For example, for a form login, you might:
     *
     *      return [
     *          'username' => $request->request->get('_username'),
     *          'password' => $request->request->get('_password'),
     *      ];
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return ['api_key' => $request->headers->get('X-API-TOKEN')];
     *
     * @return mixed Any non-null value
     *
     * @throws UnexpectedValueException If null is returned
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'email' => $request->request->get('email'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
            'context' => $request->request->get('context'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *
     * - For a form login, you might redirect to the login page
     *
     *     return new RedirectResponse('/login');
     *
     * - For an API token authentication system, you return a 401 response
     *
     *     return new Response('Auth header required', 401);
     *
     * @param Request                 $request       The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $portal = $this->requestContext->fetchPortal($request);
        $contextId = null !== $portal ? $portal->getId() : $this->requestContext->fetchContextId($request);

        $url = $this->getLoginUrl($contextId);

        return new RedirectResponse($url);
    }
}
