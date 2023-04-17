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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractCommsyAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
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

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);

        $ldapAuthSource = $this->entityManager->getRepository(AuthSourceLdap::class)
            ->findOneBy([
                'portal' => $credentials['context'],
                'enabled' => 1,
            ]);

        $context = $request->attributes->get('context');

        // Store the current context and the auth source id in the user session so we can
        // refer to it later in the user provider to get the correct user.
        $session = $request->getSession();
        $session->set('context', $context);
        $session->set('authSourceId', $ldapAuthSource->getId());

        /**
         * TODO: Instead of creating a new user provider here we should utilize security.yaml or the service container
         * to already get a useful UserProvider from the parameter (check chained user provider or a compiler
         * pass or ...).
         */
        $ldap = Ldap::create('ext_ldap', [
            'connection_string' => $ldapAuthSource->getServerUrl(),
        ]);

        $ldapProvider = new LdapUserProvider(
            $ldap,
            $ldapAuthSource->getBaseDn(),
            $ldapAuthSource->getSearchDn(),
            $ldapAuthSource->getSearchPassword(),
            [],
            $ldapAuthSource->getUidKey(),
            null,
            null,
            ['mail', 'givenName', 'sn']
        );

        $ldapUser = $ldapProvider->loadUserByIdentifier($credentials['email']);

        $account = $this->entityManager->getRepository(Account::class)
            ->findOneByCredentials($credentials['email'], $credentials['context'], $ldapAuthSource);
        $extraFields = $ldapUser->getExtraFields();

        if (null === $account) {
            // if we did not found an existing account, create one
            $account = new Account();
            $account->setAuthSource($ldapAuthSource);
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

        return new Passport(
            new UserBadge($credentials['email']),
            new CustomCredentials(function ($credentials, UserInterface $user) use ($ldapAuthSource) {
                /** @var Account $account */
                $account = $user;

                try {
                    $ldap = Ldap::create('ext_ldap', [
                        'connection_string' => $ldapAuthSource->getServerUrl(),
                    ]);

                    $authQuery = $ldapAuthSource->getAuthQuery();
                    if (!$authQuery) {
                        $dn = str_replace('{username}', $account->getUsername(), $ldapAuthSource->getAuthDn());
                    } else {
                        // bind with searchDn
                        $ldap->bind($ldapAuthSource->getSearchDn(), $ldapAuthSource->getSearchPassword());

                        $username = $ldap->escape($account->getUsername(), '', LdapInterface::ESCAPE_FILTER);
                        $query = str_replace('{username}', $username, $authQuery);
                        $result = $ldap->query($ldapAuthSource->getAuthDn(), $query)->execute();
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
            }, $credentials['password']),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->request->get('_csrf_token')
                ),
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
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyAuthenticator::LAST_SOURCE, 'ldap');
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }
}
