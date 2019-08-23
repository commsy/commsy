<?php

namespace App\Security;

use App\Entity\Auth;
use App\Entity\AuthSource;
use App\Entity\RoomPrivat;
use App\Repository\AuthSourceRepository;
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

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
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

            // TODO
            $authSource = $this->entityManager->getRepository(AuthSource::class)
                ->findBy([
                    'id' => 102,
                    'portal' => $context,
                ]);

            if (!empty($authSource)) {
                return true;
            }
        }

        return false;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $context = $credentials['context'] === 'server' ? 99 : $credentials['context'];

        $user = null;
        try {
            $user = $this->entityManager->getRepository(Auth::class)
                ->findOneByCredentials($credentials['email'], $context);
        } catch (NonUniqueResultException $e) {
            throw new CustomUserMessageAuthenticationException('A problem with your account occurred.');
        }

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // This will redirect the user to the route they visited initially.
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // Store the current context in the user session so we can refer to it later in the user provider
        // to get the correct user.
        $session = $request->getSession();
        $session->set('context', $request->request->get('context'));

        // The default redirect to the dashboard.
        /** @var Auth $user */
        $user = $token->getUser();

        $privateRoom = $this->entityManager->getRepository(RoomPrivat::class)
            ->findByContextIdAndUsername($request->request->get('context'), $user->getUsername());

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard_overview', [
            'roomId' => $privateRoom->getItemId(),
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
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
        return true;
    }

    private function getLoginUrl(Request $request)
    {
        return $this->urlGenerator->generate('app_login', [
            'context' => $request->attributes->get('context'),
        ]);
    }
}
