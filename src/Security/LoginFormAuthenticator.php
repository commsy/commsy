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

use App\Entity\AuthSourceLocal;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractCommsyAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        RequestContext $requestContext
    ) {
        parent::__construct($urlGenerator, $requestContext);
    }

    protected function getPostParameterName(): string
    {
        return 'login_local';
    }

    public function isSupportedByPortalConfiguration(Request $request): bool
    {
        if ($request->request->has('context')) {
            $context = $request->request->get('context');

            // If context is "server" this will be a root login as system administrator. The form authenticator
            // is the only one that handles these requests
            if ('server' === $context) {
                return true;
            }

            // Try to find an enabled authentication source of type local for the given context
            $authSource = $this->entityManager->getRepository(AuthSourceLocal::class)
                ->findBy([
                    'portal' => $context,
                    'enabled' => 1,
                ]);

            if (!empty($authSource)) {
                return true;
            }
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);

        $localAuthSource = $this->entityManager->getRepository(AuthSourceLocal::class)
            ->findOneBy([
                'portal' => $credentials['context'] === 'server' ? null : $credentials['context'],
                'enabled' => 1,
            ]);

        $context = ('server' === $request->request->get('context')) ? 99 : $request->request->get('context');

        // Store the current context and the auth source id in the user session so we can
        // refer to it later in the user provider to get the correct user.
        $session = $request->getSession();
        $session->set('context', $context);
        $session->set('authSourceId', $localAuthSource->getId());

        return new Passport(
            new UserBadge($credentials['email']),
            new PasswordCredentials($credentials['password']),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),
                (new RememberMeBadge())->enable(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        // This will redirect the user to the route they visited initially.
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_helper_portalenter', [
            'context' => $request->request->get('context'),
        ]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyAuthenticator::LAST_SOURCE, 'local');
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }
}
