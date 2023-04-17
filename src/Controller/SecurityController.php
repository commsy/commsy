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

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
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
use App\Security\AbstractCommsyAuthenticator;
use App\Services\LegacyEnvironment;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/admin')]
    public function admin(): Response
    {
        /** @var Account $user */
        $user = $this->getUser();

        // If the user is not authenticated, redirect to admin login
        if (null === $user) {
            return $this->redirectToRoute('app_login', [
                'context' => 'server',
            ]);
        }

        // Redirect to portal overview for now
        return $this->redirectToRoute('app_server_show');
    }

    #[Route(path: '/login/{context}/{authSourceId?}', name: 'app_login', priority: -1)]
    #[ParamConverter('authSource', class: AuthSource::class, options: ['mapping' => ['authSourceId' => 'id']])]
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        Request $request,
        string $context = 'server',
        ?AuthSource $authSource = null
    ): Response {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ('server' !== $context) {
            $portal = $entityManager->getRepository(Portal::class)->find($context);
            if (!$portal) {
                throw $this->createNotFoundException('Portal not found');
            }

            if ($authSource && $authSource->getPortal() === $portal) {
                $preSelectAuthSourceId = $authSource->getId();
            }
        } else {
            $lastUsername = 'root';
        }

        if ($request->hasSession() && ($session = $request->getSession())->has(AbstractCommsyAuthenticator::LAST_SOURCE)) {
            $lastSource = $session->get(AbstractCommsyAuthenticator::LAST_SOURCE);
            $session->remove(AbstractCommsyAuthenticator::LAST_SOURCE);
        }

        $server = $entityManager->getRepository(Server::class)->getServer();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'context' => $context,
            'portal' => $portal ?? null,
            'server' => $server,
            'lastSource' => $lastSource ?? null,
            'preSelectAuthSourceId' => $preSelectAuthSourceId ?? null,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route(path: '/login/{portalId}/request_accounts')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function requestAccounts(
        Portal $portal,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        TranslatorInterface $symfonyTranslator
    ): Response {
        $requestAccounts = new RequestAccounts($portal->getId());
        $form = $this->createForm(RequestAccountsType::class, $requestAccounts);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButtonName = $form->getClickedButton()->getName();

            if ('cancel' === $clickedButtonName) {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }

            if ('submit' === $clickedButtonName) {
                $accountsRepository = $this->getDoctrine()->getRepository(Account::class);
                $matchingAccounts = $accountsRepository->findByEmailAndPortalId(
                    $requestAccounts->getEmail(),
                    $portal->getId()
                );

                if ($matchingAccounts) {
                    $usernames = [];
                    foreach ($matchingAccounts as $matchingAccount) {
                        /* @var Account $matchingAccount */
                        $usernames[] = $matchingAccount->getUsername();
                    }

                    /**
                     * TODO: Refactor message creation, do not use legacy translator.
                     */
                    $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
                    $subject = $translator->getMessage('USER_ACCOUNT_FORGET_HEADLINE', $portal->getTitle());
                    $body = $translator->getMessage('USER_ACCOUNT_FORGET_MAIL_BODY', $portal->getTitle(),
                        implode(', ', $usernames));
                    $body .= '. <br><br>'.$translator->getMessage('MAIL_BODY_CIAO_GR', 'CommSy', $portal->getTitle());

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

        return $this->render('security/request_accounts.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/login/{portalId}/request_password_reset')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function requestPasswordReset(
        Portal $portal,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        RouterInterface $router
    ): Response {
        $localAccount = new LocalAccount($portal->getId());
        $form = $this->createForm(RequestPasswordResetType::class, $localAccount);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButtonName = $form->getClickedButton()->getName();

            if ('cancel' === $clickedButtonName) {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }

            if ('submit' === $clickedButtonName) {
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

                $expiresAt = new DateTime();
                $expiresAt->add(new DateInterval('PT15M'));

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
                 * TODO: Refactor message creation, do not use legacy translator.
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

        return $this->render('security/request_password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/login/{portalId}/password_reset/{token}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function passwordReset(
        Portal $portal,
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
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

        $now = new DateTime();
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
            $localAccount->setPassword($passwordHasher->hashPassword($localAccount, $password->getPassword()));

            $entityManager->persist($localAccount);
            $entityManager->flush();

            // clean up session to prevent attackers from spoofing
            $session->remove('ResetPasswordToken');

            return $this->redirectToRoute('app_login', [
                'context' => $portal->getId(),
            ]);
        }

        return $this->render('security/password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/login/{portalId}/simultaneous')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function simultaneousLogin(Portal $portal): Response
    {
        return $this->render('security/simultaneous_login.html.twig');
    }
}
