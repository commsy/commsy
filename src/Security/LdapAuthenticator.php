<?php


namespace App\Security;


use App\Entity\Account;
use App\Entity\AuthSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractCommsyGuardAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
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
            if ($context === 'server') {
                return false;
            }

            // Try to find an enabled authentication source of type ldap for the given context
            $authSource = $this->entityManager->getRepository(AuthSource::class)
                ->findBy([
                    'type' => 'ldap',
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
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|null
     * @throws AuthenticationException
     *
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $context = $credentials['context'] === 'server' ? 99 : $credentials['context'];

        $authSource = $this->entityManager->getRepository(AuthSource::class)
            ->findBy([
                'type' => 'ldap',
                'portal' => $context,
                'enabled' => 1,
            ]);

        $lookup = $this->performLdapLookup($credentials['email'], $credentials['password'], $authSource);

        // TODO: Perform LDAP requests here.
        $dummyUser = new Account();
        $dummyUser->setUsername('test');

        return $dummyUser;
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
        // TODO: Implement onAuthenticationFailure() method.
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
        // Check if the user exists locally, update his account information

        /** @var Account $user */
        $user = $token->getUser();
        $i = 5;
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
        // TODO: Implement supportsRememberMe() method.
    }

    protected function getLoginUrl(Request $request): string
    {
        // TODO
        return $this->urlGenerator->generate('app_login', [
            'context' => $request->attributes->get('context'),
        ]);
    }

    private function performLdapLookup(string $username, string $password, AuthSource $authSource)
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        $extras = $authSource->getExtras();
        $ldapConnectionString = $extras['DATA']['HOST'] ?? '';
        $ldapConnectionUser = $extras['DATA']['USER'] ?? '';
        $ldapConnectionPassword = $extras['DATA']['PASSWORD'] ?? '';
        $ldapFieldUserId = $extras['DATA']['DBSEARCHUSERID'] ?? '';
        $ldapBaseDn = $extras['DATA']['BASE'] ?? '';
        $ldapEncryption = $extras['DATA']['ENCRYPTION'] ?? 'none';

        $ldap = Ldap::create('ext_ldap', [
            'connection_string' => $ldapConnectionString,
        ]);

        try {
            $ldap->bind($ldapConnectionUser, $this->encryptPassword($ldapConnectionPassword, $ldapEncryption));

            // search for user
            $userEntry = false;
            $searchFilter = "($ldapFieldUserId=$username)";
            foreach (explode(';', $ldapBaseDn) as $searchBase) {
                $query = $ldap->query($searchBase, $searchFilter);
                $results = $query->execute()->toArray();

                if (count($results) === 1) {
                    $userEntry = $results[0];
                    $this->userData[$username] = $userEntry;
                    $access = $userEntry->getDn();
                }
            }

            if (!$userEntry) {
//                $this->_error_array[] = $this->translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD', $username);
                return false;
            }

            try {
                $ldap->bind($userEntry->getDn(), $this->encryptPassword($password, $ldapEncryption));
                return true;
            } catch (ConnectionException $exception) {
//                $this->_error_array[] = $this->translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD', $username);
            }
        } catch (ConnectionException $exception) {
//            include_once('functions/error_functions.php');
//            trigger_error('could not connect to server ' . $ldapConnectionString, E_USER_WARNING);
        }

        return false;
    }

    private function encryptPassword(string $password, string $encryption)
    {
        if ($encryption === 'md5') {
            return md5($password);
        }

        return $password;
    }
}