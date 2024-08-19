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
use App\Entity\AuthSourceOIDC;
use App\Facade\AccountCreatorFacade;
use App\Security\Oidc\Flow\AuthorizationCodeFlow;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lcobucci\JWT\UnencryptedToken;
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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OidcAuthenticator extends AbstractCommsyAuthenticator
{
    use TargetPathTrait;

    private AuthorizationCodeFlow $authorizationCodeFlow;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestContext $requestContext,
        AuthorizationCodeFlow $authorizationCodeFlow,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountCreatorFacade $accountCreator,
        private readonly AccountManager $accountManager
    ) {
        parent::__construct($urlGenerator, $requestContext);
        $this->authorizationCodeFlow = $authorizationCodeFlow;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): bool
    {
        return 'app_oidc_authoidccheck' === $request->attributes->get('_route')
            && $request->isMethod('GET')
            && $this->isSupportedByPortalConfiguration($request);
    }

    protected function getPostParameterName(): string
    {
        // unused
        return '';
    }

    protected function isSupportedByPortalConfiguration(Request $request): bool
    {
        if ($request->attributes->has('context')) {
            $context = $request->attributes->get('context');

            // Try to find an enabled authentication source of type oidc for the given context
            $authSource = $this->entityManager->getRepository(AuthSourceOIDC::class)
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

    public function authenticate(Request $request): Passport
    {
        if ($request->query->has('error')) {
            throw new AuthenticationException($request->query->get('error'));
        }

        $context = $request->attributes->get('context');

        $oidcAuthSource = $this->entityManager->getRepository(AuthSourceOIDC::class)
            ->findOneBy([
                'portal' => $context,
                'enabled' => 1,
            ]);

        try {
            if ($idToken = $this->authorizationCodeFlow->authenticate($request, $oidcAuthSource)) {
                if ($idToken instanceof UnencryptedToken) {
                    $claims = $idToken->claims()->all();

                    $username = $claims['nickname'];
                    $email = $claims['email'];
                    $firstname = $claims['given_name'] ?? '';
                    $lastname = $claims['family_name'] ?? '';

                    // Store the current context and the auth source id in the user session so we can
                    // refer to it later in the user provider to get the correct user.
                    $session = $request->getSession();
                    $session->set('context', $context);
                    $session->set('authSourceId', $oidcAuthSource->getId());

                    $account = $this->entityManager->getRepository(Account::class)
                        ->findOneByCredentials($username, $context, $oidcAuthSource);

                    if (null === $account) {
                        // if we did not find an existing account, create one
                        $account = new Account();
                        $account->setAuthSource($oidcAuthSource);
                        $account->setContextId($context);
                        $account->setLanguage('de');
                        $account->setUsername($username);
                        $account->setFirstname($firstname);
                        $account->setLastname($lastname);
                        $account->setEmail($email);
                        $this->accountCreator->persistNewAccount($account);
                    }

                    // update user object with credentials extracted from request
                    $account->setUsername($username);
                    $account->setFirstname($firstname);
                    $account->setLastname($lastname);
                    $account->setEmail($email);

                    $this->entityManager->persist($account);
                    $this->entityManager->flush();

                    $this->accountManager->propagateAccountDataToProfiles($account);

                    return new SelfValidatingPassport(new UserBadge($username));
                }
            }
        } catch (Exception|TransportExceptionInterface) {
            throw new AuthenticationException();
        }

        throw new AuthenticationException();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // This will redirect the user to the route they visited initially.
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_helper_portalenter', [
            'context' => $request->attributes->get('context'),
        ]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyAuthenticator::LAST_SOURCE, 'oidc');
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }
}
