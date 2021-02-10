<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Form\Type\PasswordForgottenNewPasswordType;
use App\Form\Type\PasswordForgottenStateUserIdType;
use App\Services\LegacyEnvironment;
use App\Utils\MailAssistant;
use App\Utils\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;
use function PHPUnit\Framework\throwException;


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

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'context' => $context,
            'portal' => $portal ?? null,
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
     * @Route("/passwordforgottenuserid/{context}", name="app_password_forgotten_userid")
     * @Template
     * @param Request $request
     */
    public function passwordForgottenStateUserID(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        \Swift_Mailer $mailer,
        UserService $userService,
        TranslatorInterface $translatorInt,
        string $context
    ) {
        $form = $this->createForm(PasswordForgottenStateUserIdType::class, [], [
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->getClickedButton()->getName() === 'cancel') {
                return $this->redirectToRoute('app_login', [
                    'context' => $context,
                ]);
            } elseif ($form->getClickedButton()->getName() === 'save') {

                $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
                $portalItem = $legacyEnvironment->getEnvironment()->getCurrentContextItem();

                $data = $form->getData();
                $userLoginId = $data['userId'] ?? null;

                $session = $request->getSession();
                if(!$session) {
                    $session = new Session();
                    $session->setId($userLoginId);
                }
                $this->passwordForgottenSessionCleanup($session);

                $uuid = uniqid();
                $session->set('uuid_password_forgotten', $uuid);

                $now = date('Y-m-d H:i:s');
                $session->set('password_forget_time', $now);
                $session->set('user_login', $userLoginId);

                if (isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
                    $session->set('password_forget_ip', $_SERVER["SERVER_ADDR"]);
                } else {
                    $session->set('password_forget_ip', $_SERVER["HTTP_HOST"]);
                }

                if ($userLoginId == 'root') {
                    $session->set('commsy_id', $_SERVER["SERVER_ADDR"]);
                } else {
                    $session->set('commsy_id', $legacyEnvironment->getEnvironment()->getCurrentContextID());
                }

                if (!$userLoginId) {
                    $translator->getMessage();
                    $form->get('userId')->addError(new FormError($translatorInt->trans('login unknown')));
                    return [
                        'form' => $form->createView(),
                    ];
                }

                $users = $userService->getUserFromLogin($userLoginId);
                if (empty($users)) {
                    $form->get('userId')->addError(new FormError($translatorInt->trans('login unknown')));
                } else {
                    $url = 'http://' . $_SERVER['HTTP_HOST']. '/login/' . $context .'/newpassword/'. $users[0]->getItemId() . '/' . $uuid;
                    $session->set('user_id', $users[0]->getItemId());
                    $subject = $translator->getMessage('USER_PASSWORD_MAIL_SUBJECT', $portalItem->getTitle());
                    $body = $translator->getMessage('USER_PASSWORD_MAIL_BODY', $userLoginId, $portalItem->getTitle(), $url, '15');

                    $message = $mailAssistant->getSwitftMailForPasswordForgottenMail($subject, $body, $users[0]);
                    $mailer->send($message);

                    $mailSendMessage = $translator->getMessage('USER_PASSWORD_FORGET_SUCCESS_TEXT');
                    $this->addFlash('messageSuccess', str_replace('<br/>', '', $mailSendMessage));

                    return $this->redirectToRoute('app_login', [
                        'context' => $context,
                    ]);
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/login/{context}/newpassword/{userid}/{uuid}", name="app_new_password")
     * @Template
     * @param Request $request
     */
    public function newPassword (
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator,
        string $context,
        string $uuid,
        int $userid
    ) {
        $form = $this->createForm(PasswordForgottenNewPasswordType::class, [], [
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $session = $request->getSession();
            if(!$session) {
                $session = new Session();
                $session->setId($userid);
            }

            // check uuid
            $pwForgottenUuid = $session->get('uuid_password_forgotten');
            if (!($pwForgottenUuid === $uuid)) {
                //TODO: errors do not show...
                $form->get('password')->addError(new FormError($translator->trans('The link expired.')));
                $this->passwordForgottenSessionCleanup($session);
                return [
                    'form' => $form->createView(),
                ];
            }

            // check IP
            if (isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
                $passwordForgottenIP = $_SERVER["SERVER_ADDR"];
            } else {
                $passwordForgottenIP = $_SERVER["HTTP_HOST"];
            }
            if (! $passwordForgottenIP === $session->get('password_forget_ip')) {
                $form->get('password')->addError(new FormError($translator->trans('The link expired.')));
                $this->passwordForgottenSessionCleanup($session);
                return [
                    'form' => $form->createView(),
                ];
            }

            // check whether no more than 15 minutes have passed
            $compareNow = date('Y-m-d H:i:s');
            $pwForgottenNow = $session->get('password_forget_time');
            if ((round(abs(strtotime($pwForgottenNow) - strtotime($compareNow)) / 60,2)) > 15) {
                $form->get('password')->addError(new FormError($translator->trans('The link expired.')));
                $this->passwordForgottenSessionCleanup($session);
                return [
                    'form' => $form->createView(),
                ];
            }

            // update password
            $submittedPassword = $data['password'];
            $user = $userService->getUser($userid);

            $accountRepo = $entityManager->getRepository(Account::class);
            $authRepo = $entityManager->getRepository(AuthSource::class);
            $authSource = $authRepo->find($user->getAuthSource());
            try {
                //TODO: findOneByCredentials broken? Returns null, even though all 3 parameters match.
                $userPwUpdate = $accountRepo->findOneByCredentials($user->getEmail(), $user->getContextID(), $authSource);
                if (!$userPwUpdate) {
                    // fallback
                    $userPwUpdate = $accountRepo->findOneByCredentialsShort($user->getEmail(), $user->getContextID());
                }
            } catch (Exception $exception) {
                $form->get('password')->addError(new FormError($translator->trans('action error')));
                return [
                    'form' => $form->createView(),
                ];
            }

            $userPwUpdate->setPasswordMd5(null);
            $userPwUpdate->setPassword($passwordEncoder->encodePassword($userPwUpdate, $submittedPassword));

            $entityManager->persist($userPwUpdate);
            $entityManager->flush();

            // clean up session to prevent attackers from spoofing
            $this->passwordForgottenSessionCleanup($session);

            $this->addFlash('passwordUpdated', true);

            return $this->redirectToRoute('app_login', [
                'context' => $context,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    private function passwordForgottenSessionCleanup(Session $session) {
        $session->remove('uuid_password_forgotten');
        $session->remove('user_id');
        $session->remove('user_login');
        $session->remove('password_forget_time');
        $session->remove('password_forget_ip');
        $session->remove('commsy_id');
    }

}
