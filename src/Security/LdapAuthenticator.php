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
use App\Entity\AuthSourceLdap;
use App\Facade\AccountCreatorFacade;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Ldap\Security\LdapUserProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractCommsyGuardAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private AccountCreatorFacade $accountCreator,
        RequestContext $requestContext,
        private AccountManager $accountManager
    ) {
        parent::__construct($urlGenerator, $requestContext);
    }

    protected function getPostParameterName(): string
    {
        return 'login_ldap';
    }

    public function isSupportedByPortalConfiguration(Request $request): bool
    {
        if ($request->request->has('context')) {
            $context = $request->request->get('context');

            // If context is "server" this will be a root login as system administrator. The form authenticator
            // is the only one that handles these requests
            if ('server' === $context) {
                return false;
            }

            // Try to find an enabled authentication source of type ldap for the given context
            $authSource = $this->entityManager->getRepository(AuthSourceLdap::class)
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

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $context = 'server' === $credentials['context'] ? 99 : $credentials['context'];

        /** @var AuthSourceLdap $ldapSource */
        $ldapSource = $this->entityManager->getRepository(AuthSourceLdap::class)
            ->findOneBy([
                'portal' => $context,
                'enabled' => 1,
            ]);

        /**
         * TODO: Instead of creating a new user provider here we should utilize security.yaml or the service container
         * to already get a useful UserProvider from the parameter (check chained user provider or a compiler
         * pass or ...).
         */
        $ldap = Ldap::create('ext_ldap', [
            'connection_string' => $ldapSource->getServerUrl(),
        ]);

        $ldapProvider = new LdapUserProvider(
            $ldap,
            $ldapSource->getBaseDn(),
            $ldapSource->getSearchDn(),
            $ldapSource->getSearchPassword(),
            [],
            $ldapSource->getUidKey(),
            null,
            null,
            ['mail', 'givenName', 'sn']
        );

        $ldapUser = $ldapProvider->loadUserByUsername($credentials['email']);

        $account = $this->entityManager->getRepository(Account::class)
            ->findOneByCredentials($credentials['email'], $credentials['context'], $ldapSource);
        $extraFields = $ldapUser->getExtraFields();

        if (null === $account) {
            // if we did not found an existing account, create one
            $account = new Account();
            $account->setAuthSource($ldapSource);
            $account->setContextId($credentials['context']);
            $account->setLanguage('de');
            $account->setUsername($ldapUser->getUsername());
            $account->setFirstname($extraFields['givenName']);
            $account->setLastname($extraFields['sn']);
            $account->setEmail($extraFields['mail']);
            $this->accountCreator->persistNewAccount($account);
        }

        // update user object with credentials extracted from request
        $account->setFirstname($extraFields['givenName']);
        $account->setLastname($extraFields['sn']);
        $account->setEmail($extraFields['mail']);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $this->accountManager->propagateAccountDataToProfiles($account);

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
     *
     * @return bool
     *
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        /** @var Account $account */
        $account = $user;

        /** @var AuthSourceLdap $ldapSource */
        $ldapSource = $this->entityManager->getRepository(AuthSourceLdap::class)
            ->findOneBy([
                'portal' => $account->getContextId(),
                'enabled' => 1,
            ]);

        try {
            $ldap = Ldap::create('ext_ldap', [
                'connection_string' => $ldapSource->getServerUrl(),
            ]);

            $authQuery = $ldapSource->getAuthQuery();
            if (!$authQuery) {
                $dn = str_replace('{username}', $account->getUsername(), $ldapSource->getAuthDn());
            } else {
                // bind with searchDn
                $ldap->bind($ldapSource->getSearchDn(), $ldapSource->getSearchPassword());

                $username = $ldap->escape($account->getUsername(), '', LdapInterface::ESCAPE_FILTER);
                $query = str_replace('{username}', $username, $authQuery);
                $result = $ldap->query($ldapSource->getAuthDn(), $query)->execute();
                if (1 !== $result->count()) {
                    return false;
                }

                $dn = $result[0]->getDn();
            }

            $ldap->bind($dn, $credentials['password']);

            return true;
        } catch (Exception) {
        }

        return false;
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
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyGuardAuthenticator::LAST_SOURCE, 'ldap');
        }

        $url = $this->getLoginUrl($request->attributes->get('context'));

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
