<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AuthSourceLocal;
use App\Entity\ShibbolethIdentityProvider;
use App\Entity\Portal;
use App\Entity\Server;
use App\Form\Model\LocalAccount;
use App\Form\Model\RequestAccounts;
use App\Form\Type\PasswordChangeType;
use App\Form\Type\RequestAccountsType;
use App\Form\Type\RequestPasswordResetType;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Model\Password;
use App\Model\ResetPasswordToken;
use App\Security\AbstractCommsyGuardAuthenticator;
use App\Services\LegacyEnvironment;
use App\Utils\MailAssistant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;


class SecurityController extends AbstractController
{
    /**
     * @Route("/admin")
     * @return Response
     */
    public function admin(): Response
    {
        /** @var Account $user */
        $user = $this->getUser();

        // If the user is not authenticated, redirect to admin login
        if ($user === null) {
            return $this->redirectToRoute('app_login', [
                'context' => 'server',
            ]);
        }

        // Redirect to portal overview for now
        return $this->redirectToRoute('app_server_show');
    }

    /**
     * @Route("/login/{context}", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @param string $context
     * @return Response
     */
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        Request $request,
        string $context = 'server'
    ): Response {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($context !== 'server') {
            $portal = $entityManager->getRepository(Portal::class)->find($context);
            if (!$portal) {
                throw $this->createNotFoundException('Portal not found');
            }
        } else {
            $lastUsername = 'root';
        }

        $lastSource = null;
        if ($request->hasSession() && ($session = $request->getSession())->has(AbstractCommsyGuardAuthenticator::LAST_SOURCE)) {
            $lastSource = $session->get(AbstractCommsyGuardAuthenticator::LAST_SOURCE);
        }

        $server = $entityManager->getRepository(Server::class)->getServer();

        $idps = [];
//        $authSources = $portal->getAuthSources();
//        foreach($authSources as $authSource) {
//            if($authSource->getType() === 'shib') {
//                $idpRepo = $entityManager->getRepository(Idp::Class);
//                $idps = $idpRepo->findBy(array('authSourceShibboleth' => $authSource));
//            }
//        }
        $choices = [];
        foreach($idps as $currentIpd) {
            $choices[$currentIpd->getName()] = $currentIpd->getId();
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'context' => $context,
            'portal' => $portal ?? null,
            'server' => $server,
            'lastSource' => $lastSource,
            'idps' => $choices,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     * @throws Exception
     */
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @Route("/login/{portalId}/request_accounts")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template
     * @param Portal $portal
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param Mailer $mailer
     * @param TranslatorInterface $symfonyTranslator
     * @return array|RedirectResponse
     */
    public function requestAccounts(
        Portal $portal,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        TranslatorInterface $symfonyTranslator
    ) {
        $requestAccounts = new RequestAccounts($portal->getId());
        $form = $this->createForm(RequestAccountsType::class, $requestAccounts);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButtonName = $form->getClickedButton()->getName();

            if ($clickedButtonName === 'cancel') {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }

            if ($clickedButtonName === 'submit') {
                $accountsRepository = $this->getDoctrine()->getRepository(Account::class);
                $matchingAccounts = $accountsRepository->findByEmailAndPortalId(
                    $requestAccounts->getEmail(),
                    $portal->getId()
                );

                if ($matchingAccounts) {
                    $usernames = [];
                    foreach ($matchingAccounts as $matchingAccount) {
                        /** @var Account $matchingAccount */
                        $usernames[] = $matchingAccount->getUsername();
                    }

                    /**
                     * TODO: Refactor message creation, do not use legacy translator
                     */
                    $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
                    $subject = $translator->getMessage('USER_ACCOUNT_FORGET_HEADLINE', $portal->getTitle());
                    $body = $translator->getMessage('USER_ACCOUNT_FORGET_MAIL_BODY', $portal->getTitle(),
                        implode(', ', $usernames));
                    $body .= '. <br><br>' . $translator->getMessage('MAIL_BODY_CIAO_GR', 'CommSy', $portal->getTitle());

                    $mailer->sendRaw(
                        $subject,
                        nl2br($body),
                        RecipientFactory::createFromAccount($matchingAccounts[0]),
                        $portal->getTitle()
                    );

                    $flashMessage = $translator->getMessage(
                        'USER_ACCOUNT_FORGET_SUCCESS_TEXT',
                        $requestAccounts->getEmail()
                    );
                } else {
                    $flashMessage = $symfonyTranslator->trans('login.request_accounts_none', [], 'login');
                }

                $this->addFlash('primary', strip_tags($flashMessage));

                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/login/{portalId}/request_password_reset")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template
     * @param Portal $portal
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param Mailer $mailer
     * @param RouterInterface $router
     * @return array|RedirectResponse
     * @throws NonUniqueResultException
     */
    public function requestPasswordReset(
        Portal $portal,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        RouterInterface $router
    ) {
        $localAccount = new LocalAccount($portal->getId());
        $form = $this->createForm(RequestPasswordResetType::class, $localAccount);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButtonName = $form->getClickedButton()->getName();

            if ($clickedButtonName === 'cancel') {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }

            if ($clickedButtonName === 'submit') {
                $localSource = $this->getDoctrine()->getRepository(AuthSourceLocal::class)
                    ->findOneBy([
                        'portal' => $localAccount->getContextId(),
                        'enabled' => 1,
                    ]);
                $localAccount = $this->getDoctrine()->getRepository(Account::class)
                    ->findOneByCredentials(
                        $localAccount->getUsername(),
                        $localAccount->getContextId(),
                        $localSource
                    );

                $expiresAt = new \DateTime();
                $expiresAt->add(new \DateInterval('PT15M'));

                $session = $request->getSession();
                if ($session->has('ResetPasswordToken')) {
                    $session->remove('ResetPasswordToken');
                }

                $resetPasswordToken = new ResetPasswordToken(
                    uniqid(),
                    $expiresAt,
                    $localAccount,
                    $request->getClientIp()
                );
                $session->set('ResetPasswordToken', $resetPasswordToken);

                $resetUrl = $router->generate('app_security_passwordreset', [
                    'portalId' => $portal->getId(),
                    'token' => $resetPasswordToken->getToken(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                /**
                 * TODO: Refactor message creation, do not use legacy translator
                 */
                $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
                $subject = $translator->getMessage('USER_PASSWORD_MAIL_SUBJECT', $portal->getTitle());
                $body = $translator->getMessage(
                    'USER_PASSWORD_MAIL_BODY',
                    $resetPasswordToken->getAccount()->getUsername(),
                    $portal->getTitle(),
                    $resetUrl,
                    '15'
                );

                $mailer->sendRaw(
                    $subject,
                    nl2br($body),
                    RecipientFactory::createFromAccount($localAccount),
                    $portal->getTitle()
                );

                $flashMessage = $translator->getMessage('USER_PASSWORD_FORGET_SUCCESS_TEXT');
                $this->addFlash('primary', str_replace('<br/>', '', $flashMessage));

                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/login/{portalId}/password_reset/{token}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template
     * @param Portal $portal
     * @param string $token
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return array|RedirectResponse
     * @throws NonUniqueResultException
     */
    public function passwordReset(
        Portal $portal,
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $session = $request->getSession();
        if (!$session->has('ResetPasswordToken')) {
            throw $this->createAccessDeniedException();
        }

        /** @var ResetPasswordToken $resetPasswordToken */
        $resetPasswordToken = $session->get('ResetPasswordToken');

        if ($token !== $resetPasswordToken->getToken()) {
            // TODO: Form validation would be a better option
            $session->remove('ResetPasswordToken');
            throw $this->createAccessDeniedException();
        }

        if ($request->getClientIp() !== $resetPasswordToken->getIp()) {
            // TODO: Form validation would be a better option
            $session->remove('ResetPasswordToken');
            throw $this->createAccessDeniedException();
        }

        $now = new \DateTime();
        if ($now > $resetPasswordToken->getExpiresAt()) {
            // TODO: Form validation would be a better option
            $session->remove('ResetPasswordToken');
            throw $this->createAccessDeniedException();
        }

        $password = new Password();
        $form = $this->createForm(PasswordChangeType::class, $password);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Account $accountFromToken */
            $accountFromToken = $resetPasswordToken->getAccount();

            // update password
            $localSource = $this->getDoctrine()->getRepository(AuthSourceLocal::class)
                ->findOneBy([
                    'portal' => $accountFromToken->getContextId(),
                    'enabled' => 1,
                ]);
            /** @var Account $localAccount */
            $localAccount = $this->getDoctrine()->getRepository(Account::class)
                ->findOneByCredentials(
                    $accountFromToken->getUsername(),
                    $accountFromToken->getContextId(),
                    $localSource
                );

            $localAccount->setPasswordMd5(null);
            $localAccount->setPassword($passwordEncoder->encodePassword($localAccount, $password->getPassword()));

            $entityManager->persist($localAccount);
            $entityManager->flush();

            // clean up session to prevent attackers from spoofing
            $session->remove('ResetPasswordToken');

            return $this->redirectToRoute('app_login', [
                'context' => $portal->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
