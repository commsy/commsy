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

use App\Account\AccountManager;
use App\Account\AccountMerger;
use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Event\AccountChangedEvent;
use App\Event\AccountCreatedEvent;
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
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use App\Utils\RoomService;
use App\Utils\UserService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;

class AccountController extends AbstractController
{
    #[Route(path: '/register/{id}')]
    public function signUp(
        Portal $portal,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        AccountCreatorFacade $accountFacade,
        LegacyEnvironment $legacyEnvironment,
        TranslatorInterface $translator,
        InvitationsService $invitationsService,
        UserService $userService,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $legacyEnvironment->getEnvironment()->setCurrentPortalID($portal->getId());

        /** @var AuthSourceLocal $localAuthSource */
        $localAuthSource = $portal->getAuthSources()->filter(fn (AuthSource $authSource) => 'local' === $authSource->getType())->first();

        // deny access if self registration is disabled
        if (AuthSource::ADD_ACCOUNT_NO === $localAuthSource->getAddAccount()) {
            throw $this->createAccessDeniedException('Self-Registration is disabled.');
        }

        // deny access if self registration is only available by invitation and the
        // provided token is invalid
        $isTokenInvalid = false;
        $token = $request->query->get('token', '');
        if (AuthSource::ADD_ACCOUNT_INVITE === $localAuthSource->getAddAccount()) {
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

            $password = $passwordHasher->hashPassword($account, $account->getPlainPassword());
            $account->setPassword($password);

            $accountFacade->persistNewAccount($account);

            $eventDispatcher->dispatch(new AccountCreatedEvent($account));

            $portalUser = $userService->getPortalUser($account);

            // if the portal has terms of usage, we'll accept them here
            // form validation already checked if they have been accepted
            if ($portal->hasAGBEnabled()) {
                $portalUser->setAGBAcceptanceDate(new DateTimeImmutable());
                $portalUser->save();
            }

            if (AuthSource::ADD_ACCOUNT_INVITE === $localAuthSource->getAddAccount()) {
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

        return $this->render('account/sign_up.html.twig', [
            'portal' => $portal,
            'form' => $form,
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/personal')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function personal(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        Security $security
    ): Response {
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

        return $this->render('account/personal.html.twig', [
            'form' => $form,
            'hasToChangeEmail' => $portalUser->hasToChangeEmail(),
        ]);
    }

    #[Route(path: '/account/changepassword')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(
        Request $request,
        Security $security,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ChangePasswordType::class);

        $passwordChanged = false;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Account $user */
            $account = $security->getUser();
            if (null !== $account) {
                $formData = $form->getData();

                $account->setPassword($passwordHasher->hashPassword($account, $formData['new_password']));

                $entityManager->persist($account);
                $entityManager->flush();

                $passwordChanged = true;
            }
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form,
            'passwordChanged' => $passwordChanged,
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/merge')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function mergeAccounts(
        Request $request,
        #[MapEntity(id: 'portalId')]
        Portal $portal,
        Security $security,
        UserService $userService,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AccountMerger $accountMerger
    ): Response {
        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $form = $this->createForm(MergeAccountsType::class, [], [
            'portal' => $portal,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $formData = $form->getData();

            if (strtolower($portalUser->getUserID()) == strtolower((string) $formData['combineUserId']) &&
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

                    if (null === $accountToMerge) {
                        throw new UnexpectedValueException();
                    }

                    // We only support merging local accounts
                    if (!$selectedAuthSource instanceof AuthSourceLocal) {
                        throw new UnexpectedValueException();
                    }

                    if (!$passwordHasher->isPasswordValid($accountToMerge, $formData['combinePassword'])) {
                        $form->get('combineUserId')->addError(new FormError('Invalid credentials.'));
                    }

                    if ($form->isSubmitted() && $form->isValid()) {
                        $accountMerger->mergeAccounts($accountToMerge, $account);

                        return $this->redirectToRoute('app_account_mergeaccounts', [
                            'portalId' => $portal->getId(),
                        ]);
                    }
                } catch (NonUniqueResultException|UnexpectedValueException) {
                    $form->get('combineUserId')->addError(new FormError('User not found'));
                }
            }
        }

        return $this->render('account/merge_accounts.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/newsletter')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function newsletter(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        Security $security
    ): Response {
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

        return $this->render('account/newsletter.html.twig', [
            'form' => $form,
            'portalEmail' => $portalUser->getRoomEmail(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/privacy')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function privacy($portalId, Request $request): Response
    {
        $form = $this->createForm(PrivacyType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // generate & serve a PDF with the user's personal master data
            return $this->redirectToRoute('app_account_privacyprint', [
                'portalId' => $portalId,
            ]);
        }

        return $this->render('account/privacy.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/privacy/print')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function privacyPrint(
        #[MapEntity(id: 'portalId')]
        Portal $portal,
        PersonalDataCollector $dataCollector,
        PrintService $printService,
        RoomService $roomService,
        Security $security,
        UserService $userService,
        TranslatorInterface $translator
    ): Response {
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
            .' ('.$portal->getTitle().').pdf';

        if (str_contains($html, 'localhost:81')) { // local fix for wkhtmltopdf
            $html = preg_replace("/<img[^>]+\>/i", '(image) ', $html);
        }

        // return HTML Response containing a PDF generated from the HTML data
        return $printService->buildPdfResponse($html, false, $fileName);
    }

    #[Route(path: '/portal/{portalId}/account/additional')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function additional(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        Security $security
    ): Response {
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

        return $this->render('account/additional.html.twig', [
            'form' => $form,
            'uploadEmail' => $this->getParameter('commsy.upload.account'),
            'portalEmail' => $portalUser->getRoomEmail(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/account/delete')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteAccount(
        Request $request,
        TranslatorInterface $translator,
        AccountManager $accountManager,
        Security $security,
        FormFactoryInterface $formFactory
    ): Response {
        $deleteParameter = $this->getParameter('commsy.security.privacy_disable_overwriting');

        $lockForm = $formFactory->createNamedBuilder('lock_form', DeleteType::class, [
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ], [])->getForm();
        $deleteForm = $formFactory->createNamedBuilder('delete_form', DeleteType::class, [
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

        return $this->render('account/delete_account.html.twig', [
            'override' => $deleteParameter,
            'form_lock' => $lockForm,
            'form_delete' => $deleteForm,
        ]);
    }
}
