<?php


namespace App\Security;


use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use App\Entity\Portal;
use App\Facade\AccountCreatorFacade;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ShibbolethAuthenticator extends AbstractCommsyGuardAuthenticator
{
    use TargetPathTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AccountCreatorFacade
     */
    private $accountCreator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        AccountCreatorFacade $accountCreator
    ) {
        parent::__construct($urlGenerator);

        $this->entityManager = $entityManager;
        $this->accountCreator = $accountCreator;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
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
     * @param Request $request
     *
     * @return mixed Any non-null value
     *
     * @throws \UnexpectedValueException If null is returned
     */
    public function getCredentials(Request $request)
    {
        $context = $request->attributes->get('context');
        /** @var AuthSourceShibboleth $authSource */
        $authSource = $this->entityManager->getRepository(AuthSourceShibboleth::class)
            ->findOneBy([
                'portal' => $context,
                'enabled' => 1,
            ]);

        // extract credentials from request
        $credentials = [
            'context' => $context,
            'username' => $request->server->get($authSource->getMappingUsername()),
            'firstname' => $request->server->get($authSource->getMappingFirstname()),
            'lastname' => $request->server->get($authSource->getMappingLastname()),
            'email' => $request->server->get($authSource->getMappingEmail()),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|null
     * @throws AuthenticationException
     *
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // Check we got all parameters we need
        if ($credentials['context'] === null ||
            $credentials['username'] === null ||
            $credentials['firstname'] === null ||
            $credentials['lastname'] === null ||
            $credentials['email'] === null
        ) {
            throw new AuthenticationException();
        }

        /** @var Collection $authSources */
        $authSources = $this->entityManager->getRepository(Portal::class)->find($credentials['context'])->getAuthSources();
        $shibAuthSource = $authSources->filter(function (AuthSource $authSource) {
            return $authSource instanceof AuthSourceShibboleth;
        })->first();

        $account = $this->entityManager->getRepository(Account::class)
            ->findOneByCredentials($credentials['username'], $credentials['context'], $shibAuthSource);

        if ($account === null) {
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

        return $account;
    }

    /**
     * Returns true if the credentials are valid.
     *
     * If any value other than true is returned, authentication will
     * fail. You may also throw an AuthenticationException if you wish
     * to cause authentication to fail.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     *
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // Credentials are checked remotely.
        return true;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyGuardAuthenticator::LAST_SOURCE, 'shib');
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /** @var Account $user */
        $user = $token->getUser();

        $context = $request->attributes->get('context');

        // Store the current context and the auth source id in the user session so we can
        // refer to it later in the user provider to get the correct user.
        $session = $request->getSession();
        $session->set('context', $context);
        $session->set('authSourceId', $user->getAuthSource()->getId());

        // This will redirect the user to the route they visited initially.
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_helper_portalenter', [
            'context' => $context,
        ]));
    }

    /**
     * Does this method support remember me cookies?
     *
     * Remember me cookie will be set if *all* of the following are met:
     *  A) This method returns true
     *  B) The remember_me key under your firewall is configured
     *  C) The "remember me" functionality is activated. This is usually
     *      done by having a _remember_me checkbox in your form, but
     *      can be configured by the "always_remember_me" and "remember_me_parameter"
     *      parameters under the "remember_me" firewall key
     *  D) The onAuthenticationSuccess method returns a Response object
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}