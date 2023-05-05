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

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\AuthSourceShibboleth;
use App\Facade\AccountCreatorFacade;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ShibbolethAuthenticator extends AbstractCommsyAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        UrlGeneratorInterface          $urlGenerator,
        private readonly AccountCreatorFacade   $accountCreator,
        RequestContext                 $requestContext,
        private readonly AccountManager         $accountManager
    )
    {
        parent::__construct($urlGenerator, $requestContext);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): bool
    {
        return 'app_shibboleth_authshibbolethcheck' === $request->attributes->get('_route')
            && $request->isMethod('GET')
            && $this->isSupportedByPortalConfiguration($request);
    }

    protected function getPostParameterName(): string
    {
        // unused
        return '';
    }

    public function isSupportedByPortalConfiguration(Request $request): bool
    {
        if ($request->attributes->has('context')) {
            $context = $request->attributes->get('context');

            // Try to find an enabled authentication source of type ldap for the given context
            $authSource = $this->entityManager->getRepository(AuthSourceShibboleth::class)
                ->findOneBy([
                    'portal' => $context,
                    'enabled' => 1,
                ]);

            if (!empty($authSource)) {
                return true;
            }
        }

        return false;
    }

    public function getCredentials(Request $request): array
    {
        $context = $request->attributes->get('context');

        /** @var AuthSourceShibboleth $authSource */
        $authSource = $this->entityManager->getRepository(AuthSourceShibboleth::class)
            ->findOneBy([
                'portal' => $context,
                'enabled' => 1,
            ]);

        $credentials = [
            'context' => $context,
            'username' => $request->server->get($authSource->getMappingUsername()),
            'firstname' => $request->server->get($authSource->getMappingFirstname()),
            'lastname' => $request->server->get($authSource->getMappingLastname()),
            'email' => $request->server->get($authSource->getMappingEmail()),
        ];

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }


    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);

        // Check we got all parameters we need
        if (null === $credentials['context'] ||
            null === $credentials['username'] ||
            null === $credentials['firstname'] ||
            null === $credentials['lastname'] ||
            null === $credentials['email']
        ) {
            throw new AuthenticationException();
        }

        $shibAuthSource = $this->entityManager->getRepository(AuthSourceShibboleth::class)
            ->findOneBy([
                'portal' => $credentials['context'],
                'enabled' => 1,
            ]);

        $context = $request->attributes->get('context');

        // Store the current context and the auth source id in the user session so we can
        // refer to it later in the user provider to get the correct user.
        $session = $request->getSession();
        $session->set('context', $context);
        $session->set('authSourceId', $shibAuthSource->getId());

        $account = $this->entityManager->getRepository(Account::class)
            ->findOneByCredentials($credentials['username'], $credentials['context'], $shibAuthSource);

        if (null === $account) {
            // if we did not found an existing account, create one
            $account = new Account();
            $account->setAuthSource($shibAuthSource);
            $account->setContextId($credentials['context']);
            $account->setLanguage('de');
            $account->setUsername($credentials['username']);
            $account->setFirstname($credentials['firstname']);
            $account->setLastname($credentials['lastname']);
            $account->setEmail($credentials['email']);
            $this->accountCreator->persistNewAccount($account);
        }

        // update user object with credentials extracted from request
        $account->setUsername($credentials['username']);
        $account->setFirstname($credentials['firstname']);
        $account->setLastname($credentials['lastname']);
        $account->setEmail($credentials['email']);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $this->accountManager->propagateAccountDataToProfiles($account);

        return new SelfValidatingPassport(new UserBadge($credentials['username']));
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
            $request->getSession()->set(AbstractCommsyAuthenticator::LAST_SOURCE, 'shib');
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }
}
