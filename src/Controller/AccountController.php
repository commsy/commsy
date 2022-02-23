<?php

namespace App\Controller;

use App\Account\AccountManager;
use App\Account\AccountMerger;
use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLdap;
use App\Entity\AuthSourceLocal;
use App\Entity\AuthSourceShibboleth;
use App\Entity\Portal;
use App\Event\AccountChangedEvent;
use App\Facade\AccountCreatorFacade;
use App\Form\DataTransformer\PrivateRoomTransformer;
use App\Form\DataTransformer\UserTransformer;
use App\Form\Type\Account\AdditionalType;
use App\Form\Type\Account\ChangePasswordType;
use App\Form\Type\Account\DeleteType;
use App\Form\Type\Account\MergeAccountsType;
use App\Form\Type\Account\NewsletterType;
use App\Form\Type\Account\PersonalInformationType;
use App\Form\Type\Account\PrivacyType;
use App\Form\Type\SignUpFormType;
use App\Privacy\PersonalDataCollector;
use App\Security\AbstractCommsyGuardAuthenticator;
use App\Security\LdapAuthenticator;
use App\Security\LoginFormAuthenticator;
use App\Security\ShibbolethAuthenticator;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use App\Utils\RoomService;
use App\Utils\UserService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountController extends AbstractController
{

    /**
     * @Route("/register/{id}")
     * @Template()
     * @ParamConverter("portal", class="App\Entity\Portal")
     * @param Portal $portal
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param AccountCreatorFacade $accountFacade
     * @param LegacyEnvironment $legacyEnvironment
     * @param TranslatorInterface $translator
     * @param InvitationsService $invitationsService
     * @param UserService $userService
     * @return array|Response
     */
    public function signUp(
        Portal $portal,
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        AccountCreatorFacade $accountFacade,
        LegacyEnvironment $legacyEnvironment,
        TranslatorInterface $translator,
        InvitationsService $invitationsService,
        UserService $userService
    ) {
        $legacyEnvironment->getEnvironment()->setCurrentPortalID($portal->getId());

        /** @var AuthSourceLocal $localAuthSource */
        $localAuthSource = $portal->getAuthSources()->filter(function (AuthSource $authSource) {
            return $authSource->getType() === 'local';
        })->first();

        // deny access if self registration is disabled
        if ($localAuthSource->getAddAccount() === AuthSource::ADD_ACCOUNT_NO) {
            throw $this->createAccessDeniedException('Self-Registration is disabled.');
        }

        // deny access if self registration is only available by invitation and the
        // provided token is invalid
        $isTokenInvalid = false;
        $token = $request->query->get('token', '');
        if ($localAuthSource->getAddAccount() === AuthSource::ADD_ACCOUNT_INVITE) {
            if (!$invitationsService->confirmInvitationCode($localAuthSource, $token)) {
                $isTokenInvalid = true;
            }
        }

        $roomContextId = $invitationsService->getContextIdByAuthAndCode($localAuthSource, $token);

        $account = new Account();
        $account->setAuthSource($localAuthSource);
        $account->setContextId($portal->getId());

        $form = $this->createForm(SignUpFormType::class, $account, [
            'portal' => $portal,
        ]);

        $form->handleRequest($request);
        if ($isTokenInvalid) {
            $form->addError(new FormError($translator->trans('token invalid', [], 'form')));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }
            $account->setLanguage('de');

            $password = $passwordEncoder->encodePassword($account, $account->getPlainPassword());
            $account->setPassword($password);

            $accountFacade->persistNewAccount($account);

            $portalUser = $userService->getPortalUser($account);

            // if the portal has terms of usage, we'll accept them here
            // form validation already checked if they have been accepted
            if ($portal->hasAGBEnabled()) {
                $portalUser->setAGBAcceptanceDate(new DateTimeImmutable());
                $portalUser->save();
            }

            if ($localAuthSource->getAddAccount() === AuthSource::ADD_ACCOUNT_INVITE) {
                $invitationsService->redeemInvitation($localAuthSource, $token);

                $newUser = $userService->cloneUser($portalUser, $roomContextId);

                if ($newUser) {
                    return $this->redirectToRoute('app_room_home', [
                        'roomId' => $roomContextId,
                    ]);
                }
            }

            return $this->redirectToRoute('app_login', [
                'context' => $portal->getId(),
            ]);
        }

        return [
            'portal' => $portal,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/personal")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param EventDispatcherInterface $eventDispatcher
     * @param CoreSecurity $security
     * @return array|RedirectResponse
     */
    public function personal(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        CoreSecurity $security
    ) {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $userData = $userTransformer->transform($portalUser);

        $privateRoomItem = $portalUser->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(PersonalInformationType::class, $userData, [
            'portalUser' => $portalUser,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldUserItem = clone $portalUser;

            $portalUser = $userTransformer->applyTransformation($portalUser, $form->getData());
            $portalUser->save();

            $event = new AccountChangedEvent($oldUserItem, $portalUser);
            $eventDispatcher->dispatch($event);

            return $this->redirectToRoute('app_account_personal', [
                'portalId' => $portalUser->getContextID(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'hasToChangeEmail' => $portalUser->hasToChangeEmail(),
        ];
    }

    /**
     * @Route("/account/changepassword")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param CoreSecurity $security
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @return array
     */
    public function changePassword(
        Request $request,
        CoreSecurity $security,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager
    ) {
        $form = $this->createForm(ChangePasswordType::class);

        $passwordChanged = false;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Account $user */
            $account = $security->getUser();
            if ($account !== null) {
                $formData = $form->getData();

                $account->setPassword($passwordEncoder->encodePassword($account, $formData['new_password']));

                $entityManager->persist($account);
                $entityManager->flush();

                $passwordChanged = true;
            }
        }

        return [
            'form' => $form->createView(),
            'passwordChanged' => $passwordChanged,
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/merge")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param Portal $portal
     * @return array|RedirectResponse
     */
    public function mergeAccounts(
        Request $request,
        Portal $portal,
        CoreSecurity $security,
        UserService $userService,
        EntityManagerInterface $entityManager,
        LdapAuthenticator $ldapAuthenticator,
        ShibbolethAuthenticator $shibbolethAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
        AccountMerger $accountMerger
    ) {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $form = $this->createForm(MergeAccountsType::class, [], [
            'portal' => $portal,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $formData = $form->getData();

            if (strtolower($portalUser->getUserID()) == strtolower($formData['combineUserId']) &&
                $formData['auth_source'] === $account->getAuthSource()
            ) {
                $form->get('combineUserId')->addError(new FormError('Invalid user'));
            } else {
                $accountRepository = $entityManager->getRepository(Account::class);

                /** @var AuthSource $selectedAuthSource */
                $selectedAuthSource = $formData['auth_source'];

                try {
                    $accountToMerge = $accountRepository->findOneByCredentials(
                        $formData['combineUserId'],
                        $portal->getId(),
                        $selectedAuthSource
                    );

                    if ($accountToMerge === null) {
                        throw new \UnexpectedValueException();
                    }

                    $authSourceGuardAuthenticatorMap = [
                        AuthSourceLocal::class => $loginFormAuthenticator,
                        AuthSourceLdap::class => $ldapAuthenticator,
                        AuthSourceShibboleth::class => $shibbolethAuthenticator,
                    ];

                    /** @var AbstractCommsyGuardAuthenticator $guardAuthenticator */
                    $guardAuthenticator = $authSourceGuardAuthenticatorMap[get_class($selectedAuthSource)];

                    $credentials = [
                        'email' => $accountToMerge->getUsername(),
                        'password' => $formData['combinePassword'],
                        'context' => $accountToMerge->getContextId(),
                    ];

                    if (!$guardAuthenticator->checkCredentials($credentials, $accountToMerge)) {
                        $form->get('combineUserId')->addError(new FormError('Authentication error'));
                    }

                    if ($form->isSubmitted() && $form->isValid()) {
                        $accountMerger->mergeAccounts($accountToMerge, $account);

                        return $this->redirectToRoute('app_account_mergeaccounts', [
                            'portalId' => $portal->getId(),
                        ]);
                    }
                } catch (NonUniqueResultException | \UnexpectedValueException $e) {
                    $form->get('combineUserId')->addError(new FormError('User not found'));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/newsletter")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param CoreSecurity $security
     * @return array
     */
    public function newsletter(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        CoreSecurity $security
    ) {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);


        $userData = $userTransformer->transform($portalUser);

        $privateRoomItem = $portalUser->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(NewsletterType::class, $userData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $portalUser = $userTransformer->applyTransformation($portalUser, $form->getData());
            $portalUser->save();
            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();
        }

        return [
            'form' => $form->createView(),
            'portalEmail' => $portalUser->getRoomEmail(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/privacy")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function privacy($portalId, Request $request)
    {
        $form = $this->createForm(PrivacyType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // generate & serve a PDF with the user's personal master data
            return $this->redirectToRoute('app_account_privacyprint', [
                'portalId' => $portalId,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/privacy/print")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @param Portal $portal
     * @param PersonalDataCollector $dataCollector
     * @param PrintService $printService
     * @param RoomService $roomService
     * @param CoreSecurity $security
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function privacyPrint(
        Portal $portal,
        PersonalDataCollector $dataCollector,
        PrintService $printService,
        RoomService $roomService,
        CoreSecurity $security,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $serviceLink = $roomService->buildServiceLink();
        $serviceEmail = $roomService->getServiceEmail();

        // gather the user's personal master data
        $personalData = $dataCollector->getPersonalDataForUserID($portalUser->getItemID());

        // generate HTML data
        $html = $this->renderView('profile/privacy_print.html.twig', [
            'portalId' => $portal->getId(),
            'printProfileImages' => false, // set to `false` to omit profile images when generating the PDF (much faster)
            'accountData' => $personalData->getAccountData(),
            'communityRoomProfileDataArray' => $personalData->getCommunityRoomProfileDataArray(),
            'projectRoomProfileDataArray' => $personalData->getProjectRoomProfileDataArray(),
            'groupRoomProfileDataArray' => $personalData->getGroupRoomProfileDataArray(),
            'serviceLink' => $serviceLink,
            'serviceEmail' => $serviceEmail,
        ]);

        $fileName = $translator->trans('Self assessment', [], 'profile')
            . ' (' . $portal->getTitle() . ').pdf';

        if (str_contains($html,"localhost:81")) { // local fix for wkhtmltopdf
            $html = preg_replace("/<img[^>]+\>/i", "(image) ", $html);
        }


        // return HTML Response containing a PDF generated from the HTML data
        return $printService->buildPdfResponse($html, false, $fileName);
    }

    /**
     * @Route("/portal/{portalId}/account/additional")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param EventDispatcherInterface $eventDispatcher
     * @param CoreSecurity $security
     * @return array|RedirectResponse
     */
    public function additional(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        CoreSecurity $security
    ) {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $userData = $userTransformer->transform($portalUser);

        $privateRoomItem = $portalUser->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(AdditionalType::class, $userData, [
            'emailToCommsy' => $this->getParameter('commsy.upload.enabled'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldUserItem = clone $portalUser;

            $portalUser = $userTransformer->applyTransformation($portalUser, $form->getData());
            $portalUser->save();

            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();

            $event = new AccountChangedEvent($oldUserItem, $portalUser);
            $eventDispatcher->dispatch($event);

            return $this->redirect($request->getUri());
        }

        return [
            'form' => $form->createView(),
            'uploadEmail' => $this->getParameter('commsy.upload.account'),
            'portalEmail' => $portalUser->getRoomEmail(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/account/delete")
     * @Template
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     * @param AccountManager $accountManager
     * @param CoreSecurity $security
     * @return array|RedirectResponse
     */
    public function deleteAccount(
        Request $request,
        ParameterBagInterface $parameterBag,
        TranslatorInterface $translator,
        AccountManager $accountManager,
        Security $security
    ) {
        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');

        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, [
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, [
            'confirm_string' => $translator->trans('delete', [], 'profile'),
        ], [])->getForm();

        // Lock account
        if ($request->request->has('lock_form')) {
            $lockForm->handleRequest($request);
            if ($lockForm->isSubmitted() && $lockForm->isValid()) {
                // lock account

                /** @var $account Account */
                $account = $security->getUser();
                $accountManager->lock($account);

                return $this->redirectToRoute('app_logout');
            }
        } // Delete account
        elseif ($request->request->has('delete_form')) {
            $deleteForm->handleRequest($request);
            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                // delete account

                /** @var $account Account */
                $account = $security->getUser();
                $accountManager->delete($account);

                return $this->redirectToRoute('app_logout');
            }
        }

        return [
            'override' => $deleteParameter,
            'form_lock' => $lockForm->createView(),
            'form_delete' => $deleteForm->createView(),
        ];
    }
}
