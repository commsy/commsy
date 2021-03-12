<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Entity\Server;
use App\Form\Model\LocalAccount;
use App\Form\Type\PasswordChangeType;
use App\Form\Type\RequestPasswordResetType;
use App\Model\Password;
use App\Model\ResetPasswordToken;
use App\Security\AbstractCommsyGuardAuthenticator;
use App\Services\LegacyEnvironment;
use App\Utils\MailAssistant;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
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
        string $context = 'server',
        Request $request
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

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'context' => $context,
            'portal' => $portal ?? null,
            'server' => $server,
            'lastSource' => $lastSource,
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
     * @Route("/login/{portalId}/request_password_reset")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template
     * @param Request $request
     */
    public function requestPasswordReset(
        Portal $portal,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        \Swift_Mailer $mailer,
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

                $message = $mailAssistant->getSwitftMailForPasswordForgottenMail($subject, $body, $localAccount);
                $mailer->send($message);

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
     * @param Request $request
     */
    public function passwordReset(
        Portal $portal,
        string $token,
        Request $request,
        UserService $userService,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
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

//            $this->addFlash('primary', 'passwordUpdated');

            return $this->redirectToRoute('app_login', [
                'context' => $portal->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
