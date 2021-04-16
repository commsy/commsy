<?php

namespace App\Security;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractCommsyGuardAuthenticator
{
    use TargetPathTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        parent::__construct($urlGenerator);

        $this->entityManager = $entityManager;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
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
            if ($context === 'server') {
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
     *
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = null;
        try {
            if ($credentials['context'] === 'server') {
                $user = $this->entityManager->getRepository(Account::class)
                    ->findOneBy([
                        'username' => $credentials['email'],
                        'contextId' => 99,
                    ]);
            } else {
                /** @var Collection $authSources */
                $authSources = $this->entityManager->getRepository(Portal::class)->find($credentials['context'])->getAuthSources();
                $localAuthSource = $authSources->filter(function (AuthSource $authSource) {
                    return $authSource instanceof AuthSourceLocal;
                })->first();

                $user = $this->entityManager->getRepository(Account::class)
                    ->findOneByCredentials($credentials['email'], $credentials['context'], $localAuthSource);
            }
        } catch (NonUniqueResultException $e) {
            throw new CustomUserMessageAuthenticationException('A problem with your account occurred.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /** @var Account $user */
        $user = $token->getUser();

        $context = ($request->request->get('context') === 'server') ? 99 : $request->request->get('context');

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
            'context' => $request->request->get('context'),
        ]));
    }

    /**
     * Override to change what happens after a bad username/password is submitted.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(AbstractCommsyGuardAuthenticator::LAST_SOURCE, 'local');
        }

        $url = $this->getLoginUrl($request->attributes->get('context'));

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
        return true;
    }
}