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
use App\Entity\Account;
use App\Entity\AccountIndex;
use App\Entity\AccountIndexSendMail;
use App\Entity\AccountIndexSendMergeMail;
use App\Entity\AccountIndexUser;
use App\Entity\AuthSource;
use App\Entity\AuthSourceGuest;
use App\Entity\AuthSourceLdap;
use App\Entity\AuthSourceLocal;
use App\Entity\AuthSourceShibboleth;
use App\Entity\License;
use App\Entity\Portal;
use App\Entity\PortalUserAssignWorkspace;
use App\Entity\PortalUserChangeStatus;
use App\Entity\PortalUserEdit;
use App\Entity\Room;
use App\Entity\RoomCategories;
use App\Entity\Server;
use App\Entity\Terms;
use App\Entity\Translation;
use App\Event\CommsyEditEvent;
use App\Facade\UserCreatorFacade;
use App\Form\Type\CsvImportType;
use App\Form\Type\Portal\AccessibilityType;
use App\Form\Type\Portal\AccountInactiveType;
use App\Form\Type\Portal\AccountIndexDeleteUserType;
use App\Form\Type\Portal\AccountIndexDetailAssignWorkspaceType;
use App\Form\Type\Portal\AccountIndexDetailChangePasswordType;
use App\Form\Type\Portal\AccountIndexDetailChangeStatusType;
use App\Form\Type\Portal\AccountIndexDetailEditType;
use App\Form\Type\Portal\AccountIndexDetailType;
use App\Form\Type\Portal\AccountIndexPerformUserActionType;
use App\Form\Type\Portal\AccountIndexSendMailType;
use App\Form\Type\Portal\AccountIndexSendMergeMailType;
use App\Form\Type\Portal\AccountIndexType;
use App\Form\Type\Portal\AuthGuestType;
use App\Form\Type\Portal\AuthLdapType;
use App\Form\Type\Portal\AuthLocalType;
use App\Form\Type\Portal\AuthShibbolethType;
use App\Form\Type\Portal\AuthWorkspaceMembershipType;
use App\Form\Type\Portal\CommunityRoomsCreationType;
use App\Form\Type\Portal\DataPrivacyType;
use App\Form\Type\Portal\ImpressumType;
use App\Form\Type\Portal\LicenseSortType;
use App\Form\Type\Portal\LicenseType;
use App\Form\Type\Portal\MailtextsType;
use App\Form\Type\Portal\MandatoryAssignmentType;
use App\Form\Type\Portal\PortalAnnouncementsType;
use App\Form\Type\Portal\PortalAppearanceType;
use App\Form\Type\Portal\PortalGeneralType;
use App\Form\Type\Portal\PortalhomeType;
use App\Form\Type\Portal\PrivacyType;
use App\Form\Type\Portal\ProjectRoomsCreationType;
use App\Form\Type\Portal\RoomCategoriesType;
use App\Form\Type\Portal\RoomInactiveType;
use App\Form\Type\Portal\ServerAnnouncementsType;
use App\Form\Type\Portal\ServerAppearanceType;
use App\Form\Type\Portal\ServerGeneralType;
use App\Form\Type\Portal\SupportRequestsType;
use App\Form\Type\Portal\SupportType;
use App\Form\Type\Portal\TermsType;
use App\Form\Type\Portal\TimePulsesType;
use App\Form\Type\Portal\TimePulseTemplateType;
use App\Form\Type\TermType;
use App\Form\Type\TranslationType;
use App\Mail\Mailer;
use App\Model\TimePulseTemplate;
use App\Repository\AuthSourceRepository;
use App\Room\RoomManager;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\User\UserListBuilder;
use App\Utils\AccountMail;
use App\Utils\ItemService;
use App\Utils\MailAssistant;
use App\Utils\RoomService;
use App\Utils\TimePulsesService;
use App\Utils\UserService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PortalSettingsController extends AbstractController
{
    #[Route(path: '/portal/{portalId}/settings')]
    public function index(int $portalId): RedirectResponse
    {
        return $this->redirectToRoute('app_portalsettings_general', [
            'portalId' => $portalId,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/general')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function general(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $portalForm = $this->createForm(PortalGeneralType::class, $portal);
        $portalForm->handleRequest($request);
        if ($portalForm->isSubmitted() && $portalForm->isValid()) {
            if ('save' === $portalForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_portalsettings_general', [
                'portalId' => $portal->getId(),
                'tab' => 'portal',
            ]);
        }

        $server = $entityManager->getRepository(Server::class)->getServer();
        $serverForm = $this->createForm(ServerGeneralType::class, $server);
        $serverForm->handleRequest($request);
        if ($serverForm->isSubmitted() && $serverForm->isValid()) {
            if ('save' === $serverForm->getClickedButton()->getName()) {
                $entityManager->persist($server);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_portalsettings_general', [
                'portalId' => $portal->getId(),
                'tab' => 'server',
            ]);
        }

        return $this->render('portal_settings/general.html.twig', [
            'portalForm' => $portalForm->createView(),
            'serverForm' => $serverForm->createView(),
            'tab' => $request->query->has('tab') ? $request->query->get('tab') : 'portal',
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/appearance')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function appearance(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $portalForm = $this->createForm(PortalAppearanceType::class, $portal);
        $portalForm->handleRequest($request);
        if ($portalForm->isSubmitted() && $portalForm->isValid()) {
            if ('save' === $portalForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_appearance', [
                    'portalId' => $portal->getId(),
                    'tab' => 'portal',
                ]);
            }
        }

        $server = $entityManager->getRepository(Server::class)->getServer();
        $serverForm = $this->createForm(ServerAppearanceType::class, $server);
        if ($this->isGranted(RootVoter::ROOT)) {
            $serverForm->handleRequest($request);
            if ($serverForm->isSubmitted() && $serverForm->isValid()) {
                if ('save' === $serverForm->getClickedButton()->getName()) {
                    $entityManager->persist($server);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_portalsettings_appearance', [
                        'portalId' => $portal->getId(),
                        'tab' => 'server',
                    ]);
                }
            }
        }

        return $this->render('portal_settings/appearance.html.twig', [
            'portalForm' => $portalForm->createView(),
            'serverForm' => $serverForm->createView(),
            'tab' => $request->query->has('tab') ? $request->query->get('tab') : 'portal',
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/support')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function support(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        // support page form
        $supportPageForm = $this->createForm(SupportType::class, $portal);

        $supportPageForm->handleRequest($request);
        if ($supportPageForm->isSubmitted() && $supportPageForm->isValid()) {
            if ('save' === $supportPageForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        // support requests form
        $supportRequestsForm = $this->createForm(SupportRequestsType::class, $portal);

        $supportRequestsForm->handleRequest($request);
        if ($supportRequestsForm->isSubmitted() && $supportRequestsForm->isValid()) {
            if ('save' === $supportRequestsForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/support.html.twig', [
            'supportPageForm' => $supportPageForm->createView(),
            'supportRequestsForm' => $supportRequestsForm->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/portalhome')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[ParamConverter('environment', class: LegacyEnvironment::class)]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function portalhome(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PortalhomeType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ('save' === $form->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/portalhome.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/roomcreation')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function roomCreation(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager,
        RoomService $roomService
    ): Response {
        // community rooms creation form
        $templateChoices = array_merge(['No template' => '-1'], $roomService->getAvailableTemplates(CS_COMMUNITY_TYPE));

        $communityRoomsForm = $this->createForm(CommunityRoomsCreationType::class, $portal, [
            'templateChoices' => $templateChoices,
        ]);

        $communityRoomsForm->handleRequest($request);
        if ($communityRoomsForm->isSubmitted() && $communityRoomsForm->isValid()) {
            if ('save' === $communityRoomsForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        // project rooms creation form
        $templateChoices = array_merge(['No template' => '-1'], $roomService->getAvailableTemplates(CS_PROJECT_TYPE));

        $projectRoomsForm = $this->createForm(ProjectRoomsCreationType::class, $portal, [
            'templateChoices' => $templateChoices,
        ]);

        $projectRoomsForm->handleRequest($request);
        if ($projectRoomsForm->isSubmitted() && $projectRoomsForm->isValid()) {
            if ('save' === $projectRoomsForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/room_creation.html.twig', [
            'communityRoomsForm' => $communityRoomsForm->createView(),
            'projectRoomsForm' => $projectRoomsForm->createView(),
        ]);
    }

    /**
     * @param int|null $roomCategoryId
     */
    #[Route(path: '/portal/{portalId}/settings/roomcategories/{roomCategoryId?}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function roomCategories(
        Portal $portal,
        $roomCategoryId,
        Request $request,
        RoomCategoriesService $roomCategoriesService,
        EventDispatcherInterface $dispatcher,
        EntityManagerInterface $entityManager
    ): Response {
        $editForm = null;
        $portalId = $portal->getId();
        $repository = $entityManager->getRepository(RoomCategories::class);

        if ($roomCategoryId) {
            $roomCategory = $repository->find($roomCategoryId);
        } else {
            $roomCategory = new RoomCategories();
            $roomCategory->setContextId($portalId);
        }

        $editForm = $this->createForm(RoomCategoriesType::class, $roomCategory);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $clickedButtonName = $editForm->getClickedButton()->getName();

            if ('new' === $clickedButtonName || 'update' === $clickedButtonName) {
                $entityManager->persist($roomCategory);
                $entityManager->flush();
            } else {
                if ('delete' === $clickedButtonName) {
                    $roomCategoriesService->removeRoomCategory($roomCategory);
                    $entityManager->flush();
                }
            }

            return $this->redirectToRoute('app_portalsettings_roomcategories', [
                'portalId' => $portal->getId(),
            ]);
        }

        $roomCategories = $repository->findBy([
            'context_id' => $portalId,
        ]);

        $dispatcher->dispatch(new CommsyEditEvent(null), CommsyEditEvent::EDIT);

        // ensure that room categories aren't mandatory if there currently aren't any room categories
        if (empty($roomCategories)) {
            $portal->setTagMandatory(false);
            $entityManager->persist($portal);
            $entityManager->flush();
        }

        // mandatory links form
        $linkForm = $this->createForm(MandatoryAssignmentType::class, $portal);

        $linkForm->handleRequest($request);
        if ($linkForm->isSubmitted() && $linkForm->isValid()) {
            if ('save' === $linkForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/room_categories.html.twig', [
            'editForm' => $editForm->createView(),
            'linkForm' => $linkForm->createView(),
            'portal' => $portal,
            'roomCategoryId' => $roomCategoryId,
            'roomCategories' => $roomCategories,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/auth/ldap')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function authLdap(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * Try to find an existing shibboleth auth source or create an empty one. We assume
         * that there is only one auth source per type.
         */
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceShibboleth $ldapSource */
        $ldapSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceLdap)->first();

        if (false === $ldapSource) {
            // TODO: This could be moved to a creational pattern
            $ldapSource = new AuthSourceLdap();
            $ldapSource->setPortal($portal);
        }

        $ldapForm = $this->createForm(AuthLdapType::class, $ldapSource);
        $ldapForm->handleRequest($request);

        if ($ldapForm->isSubmitted() && $ldapForm->isValid()) {
            // handle switch to other auth types
            $clickedButtonName = $ldapForm->getClickedButton()->getName();
            if ('type' === $clickedButtonName) {
                $typeSwitch = $ldapForm->get('typeChoice')->getData();

                return $this->generateRedirectForAuthType($typeSwitch, $portal);
            }

            if ('save' === $clickedButtonName) {
                if ($ldapSource->isDefault()) {
                    $authSources->map(function (AuthSource $authSource) use ($entityManager) {
                        $authSource->setDefault(false);
                        $entityManager->persist($authSource);
                    });
                    $ldapSource->setDefault(true);
                }

                $entityManager->persist($ldapSource);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/auth_ldap.html.twig', [
            'form' => $ldapForm->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/auth/local')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function authLocal(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * Try to find an existing local auth source or create an empty one. We assume
         * that there is only one auth source per type.
         */
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceLocal $localSource */
        $localSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceLocal)->first();

        if (false === $localSource) {
            // TODO: This could be moved to a creational pattern
            $localSource = new AuthSourceLocal();
            $localSource->setPortal($portal);
        }

        $localSource->setPortal($portal);
        $localForm = $this->createForm(AuthLocalType::class, $localSource);
        $localForm->handleRequest($request);

        if ($localForm->isSubmitted() && $localForm->isValid()) {
            // handle switch to other auth types
            $clickedButtonName = $localForm->getClickedButton()->getName();
            if ('type' === $clickedButtonName) {
                $typeSwitch = $localForm->get('typeChoice')->getData();

                return $this->generateRedirectForAuthType($typeSwitch, $portal);
            }

            if ('save' === $clickedButtonName) {
                if ($localSource->isDefault()) {
                    $authSources->map(function (AuthSource $authSource) use ($entityManager) {
                        $authSource->setDefault(false);
                        $entityManager->persist($authSource);
                    });
                    $localSource->setDefault(true);
                }

                $entityManager->persist($localSource);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/auth_local.html.twig', [
            'form' => $localForm->createView(),
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/auth/workspacemembership')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function authWorkspaceMembership(
        Portal $portal,
        ManagerRegistry $doctrine,
        Request $request
    ): Response {
        $form = $this->createForm(AuthWorkspaceMembershipType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $doctrine->getManager()->flush();
            } else {
                $doctrine->getManager()->refresh($portal);
            }
        }

        return $this->render('portal_settings/auth_workspace_membership.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/csvimport')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function csvImportAction(
        Request $request,
        UserCreatorFacade $userCreator,
        Portal $portal
    ): Response {
        $importForm = $this->createForm(CsvImportType::class, [], [
            'portal' => $portal,
        ]);

        $importForm->handleRequest($request);
        if ($importForm->isSubmitted() && $importForm->isValid()) {
            /** @var ArrayCollection $datasets */
            $datasets = $importForm->get('csv')->getData();

            /** @var AuthSource $authSource */
            $authSource = $importForm->get('auth_sources')->getData();

            foreach ($datasets as $dataset) {
                $userCreator->createFromCsvDataset($authSource, $dataset);
            }

            $this->addFlash('notice', 'Import completed successfully.');
        }

        return $this->render('portal_settings/csv_import.html.twig', [
            'form' => $importForm->createView(),
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/auth/guest')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function authGuest(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * Try to find an existing shibboleth auth source or create an empty one. We assume
         * that there is only one auth source per type.
         */
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceGuest $guestSource */
        $guestSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceGuest)->first();

        if (false === $guestSource) {
            // TODO: This could be moved to a creational pattern
            $guestSource = new AuthSourceGuest();
            $guestSource->setPortal($portal);
        }

        $authGuestForm = $this->createForm(AuthGuestType::class, $guestSource);
        $authGuestForm->handleRequest($request);

        if ($authGuestForm->isSubmitted() && $authGuestForm->isValid()) {
            // handle switch to other auth types
            $clickedButtonName = $authGuestForm->getClickedButton()->getName();
            if ('type' === $clickedButtonName) {
                $typeSwitch = $authGuestForm->get('typeChoice')->getData();

                return $this->generateRedirectForAuthType($typeSwitch, $portal);
            }

            if ('save' === $clickedButtonName) {
                if ($guestSource->isDefault()) {
                    $authSources->map(function (AuthSource $authSource) use ($entityManager) {
                        $authSource->setDefault(false);
                        $entityManager->persist($authSource);
                    });
                    $guestSource->setDefault(true);
                }

                $entityManager->persist($guestSource);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/auth_guest.html.twig', [
            'form' => $authGuestForm->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/auth/shib')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function authShibboleth(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * Try to find an existing shibboleth auth source or create an empty one. We assume
         * that there is only one auth source per type.
         */
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceShibboleth $shibSource */
        $shibSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceShibboleth)->first();

        if (false === $shibSource) {
            // TODO: This could be moved to a creational pattern
            $shibSource = new AuthSourceShibboleth();
            $shibSource->setPortal($portal);
        }

        $authShibbolethForm = $this->createForm(AuthShibbolethType::class, $shibSource);
        $authShibbolethForm->handleRequest($request);

        if ($authShibbolethForm->isSubmitted() && $authShibbolethForm->isValid()) {
            // handle switch to other auth types
            $clickedButtonName = $authShibbolethForm->getClickedButton()->getName();
            if ('type' === $clickedButtonName) {
                $typeSwitch = $authShibbolethForm->get('typeChoice')->getData();

                return $this->generateRedirectForAuthType($typeSwitch, $portal);
            }

            if ('save' === $clickedButtonName) {
                if ($shibSource->isDefault()) {
                    $authSources->map(function (AuthSource $authSource) use ($entityManager) {
                        $authSource->setDefault(false);
                        $entityManager->persist($authSource);
                    });
                    $shibSource->setDefault(true);
                }
                /** @var AuthSourceShibboleth $formData */
                $formData = $authShibbolethForm->getData();
                $shibSource->setIdentityProviders($formData->getIdentityProviders());

                $entityManager->persist($shibSource);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/auth_shibboleth.html.twig', [
            'form' => $authShibbolethForm->createView(),
            'portal' => $portal,
        ]);
    }

    /**
     * @param RoomService $roomService
     */
    #[Route(path: '/portal/{portalId}/settings/mailtexts')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function mailtexts(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager,
        LegacyEnvironment $environment
    ): Response {
        $defaultData = [
            'userIndexFilterChoice' => -1,
            'contentGerman' => '',
            'contentEnglish' => '',
            'resetContentGerman' => false,
            'resetContentEnglish' => false,
        ];

        $roomItem = $environment->getEnvironment()->getCurrentContextItem();

        $translator = $environment->getEnvironment()->getTranslationObject();
        $portalId = $portal->getId();
        $mailTextForm = $this->createForm(MailtextsType::class, $defaultData);
        $langDe = 'de';
        $langEn = 'en';

        $mailTextForm->handleRequest($request);
        if ($mailTextForm->isSubmitted()) {
            $formData = $mailTextForm->getData();
            $textChoice = $formData['userIndexFilterChoice'];
            $previousMailTexts = $roomItem->getEmailTextArray();

            if ($mailTextForm->isValid() && $mailTextForm->get('save')->isClicked()) {
                if ($formData['resetContentGerman']) {
                    $translator->setEmailTextArray([]);
                    $germanText = $translator->getEmailMessageInLang($langDe, $textChoice);
                } else {
                    $germanText = $formData['contentGerman'];
                }

                if ($formData['resetContentEnglish']) {
                    $translator->setEmailTextArray([]);
                    $englishText = $translator->getEmailMessageInLang($langEn, $textChoice);
                } else {
                    $englishText = $formData['contentEnglish'];
                }

                $roomItem->setEmailText($textChoice, [
                    $langDe => $germanText,
                    $langEn => $englishText,
                ]);

                $entityManager->persist($portal);
                $entityManager->flush();

            // $roomItem->save();
            } elseif ($mailTextForm->get('loadMailTexts')->isClicked()) {
                if (!in_array($textChoice, $previousMailTexts)) {
                    $germanText = $translator->getEmailMessageInLang($langDe, $textChoice);
                } else {
                    if ($formData['resetContentGerman']) {
                        $translator->setEmailTextArray([]);
                        $germanText = $translator->getEmailMessageInLang($langDe, $textChoice);
                    } else {
                        $germanText = $previousMailTexts[$textChoice][$langDe];
                    }
                }

                if (!in_array($textChoice, $previousMailTexts)) {
                    $englishText = $translator->getEmailMessageInLang($langEn, $textChoice);
                } else {
                    if ($formData['resetContentEnglish']) {
                        $translator->setEmailTextArray([]);
                        $englishText = $translator->getEmailMessageInLang($langEn, $textChoice);
                    } else {
                        $englishText = $previousMailTexts[$textChoice][$langEn];
                    }
                }
            }

            $defaultData = $formData;
            $defaultData['contentGerman'] = $germanText;
            $defaultData['contentEnglish'] = $englishText;

            $mailTextForm = $this->createForm(MailtextsType::class, $defaultData);
        }

        return $this->render('portal_settings/mailtexts.html.twig', [
            'form' => $mailTextForm->createView(),
            'portalId' => $portalId,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/licenses/{licenseId?}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function licenses(
        Portal $portal,
        ?int $licenseId,
        Request $request,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment
    ): Response {
        $portalId = $portal->getId();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(License::class);

        $license = new License();
        if ($licenseId) {
            $license = $repository->findOneById($licenseId);
            $license->setTitle(html_entity_decode($license->getTitle()));
        }

        $licenseForm = $this->createForm(LicenseType::class, $license);

        // determine title
        $pageTitle = '';
        if ($licenseForm->has('new')) {
            $pageTitle = 'Create new license';
        } elseif ($licenseForm->has('update')) {
            $pageTitle = 'Edit license';
        }

        // handle new/edit form
        $licenseForm->handleRequest($request);
        if ($licenseForm->isSubmitted() && $licenseForm->isValid()) {
            if (!$licenseForm->has('cancel') || !$licenseForm->get('cancel')->isClicked()) {
                $license->setContextId($portalId);

                if (!$license->getPosition()) {
                    $position = 0;
                    $highestPosition = $repository->findHighestPosition($portalId);

                    if ($highestPosition) {
                        $highestPosition = $highestPosition[0];
                        $position = $highestPosition['position'] + 1;
                    }

                    $license->setPosition($position);
                }

                $em->persist($license);
                $em->flush();

                $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');
            }

            return $this->redirectToRoute('app_portalsettings_licenses', [
                'portalId' => $portalId,
            ]);
        }

        // sort form
        $sortForm = $this->createForm(LicenseSortType::class, null, [
            'portalId' => $portalId,
        ]);
        $sortForm->handleRequest($request);

        if ($sortForm->isSubmitted() && $sortForm->isValid()) {
            $data = $sortForm->getData();

            /** @var ArrayCollection $delete */
            $delete = $data['license'];
            if (!$delete->isEmpty()) {
                $legacyEnvironment = $environment->getEnvironment();

                $materialManager = $legacyEnvironment->getMaterialManager();
                $materialManager->unsetLicenses($delete->get(0));

                $em->remove($delete->get(0));
                $em->flush();
            }

            $structure = $data['structure'];
            if ($structure) {
                $structure = json_decode($structure, true, 512, JSON_THROW_ON_ERROR);

                // update position
                $repository->updatePositions($structure, $portalId);
            }

            return $this->redirectToRoute('app_portalsettings_licenses', [
                'portalId' => $portalId,
            ]);
        }

        return $this->render('portal_settings/licenses.html.twig', [
            'licenseForm' => $licenseForm->createView(),
            'licenseSortForm' => $sortForm->createView(),
            'portalId' => $portalId,
            'pageTitle' => $pageTitle,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/privacy')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function privacy(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PrivacyType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ('save' === $form->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }
        }

        return $this->render('portal_settings/privacy.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/inactive')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function inactive(
        Portal $portal,
        Request $request,
        EntityManagerInterface $entityManager,
        AccountManager $accountManager,
        RoomManager $roomManager
    ): Response {
        $accountInactiveForm = $this->createForm(AccountInactiveType::class, $portal);
        $accountInactiveForm->handleRequest($request);
        if ($accountInactiveForm->isSubmitted() && $accountInactiveForm->isValid()) {
            // Reset all account if the feature has been disabled
            if (!$portal->isClearInactiveAccountsFeatureEnabled()) {
                $accountManager->resetInactivityToPreviousNonNotificationState();
            }

            $entityManager->persist($portal);
            $entityManager->flush();

            return $this->redirectToRoute('app_portalsettings_inactive', [
                'portalId' => $portal->getId(),
                'tab' => 'inactiveAccounts',
            ]);
        }

        $roomInactiveForm = $this->createForm(RoomInactiveType::class, $portal, []);
        $roomInactiveForm->handleRequest($request);
        if ($roomInactiveForm->isSubmitted() && $roomInactiveForm->isValid()) {
            if (!$portal->isClearInactiveRoomsFeatureEnabled()) {
                $roomManager->resetInactivityToPreviousNonNotificationState();
            }

            $entityManager->persist($portal);
            $entityManager->flush();

            return $this->redirectToRoute('app_portalsettings_inactive', [
                'portalId' => $portal->getId(),
                'tab' => 'inactiveRooms',
            ]);
        }

        return $this->render('portal_settings/inactive.html.twig', [
            'inactiveAccountsForm' => $accountInactiveForm->createView(),
            'inactiveRoomsForm' => $roomInactiveForm->createView(),
            'tab' => $request->query->has('tab') ? $request->query->get('tab') : 'inactive',
        ]);
    }

    /**
     * @param int|null $timePulseTemplateId
     */
    #[Route(path: '/portal/{portalId}/settings/timepulses/{timePulseTemplateId?}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function timePulses(
        Portal $portal,
        $timePulseTemplateId,
        Request $request,
        TimePulsesService $timePulsesService,
        EntityManagerInterface $entityManager
    ): Response {
        // time pulses options form
        $optionsForm = $this->createForm(TimePulsesType::class, $portal);

        $optionsForm->handleRequest($request);
        if ($optionsForm->isSubmitted() && $optionsForm->isValid()) {
            if ('save' === $optionsForm->getClickedButton()->getName()) {
                $entityManager->persist($portal);
                $entityManager->flush();
                $timePulsesService->updateTimePulseLabels($portal);
            }
        }

        // time pulses templates form
        $timePulseTemplates = $timePulsesService->getTimePulseTemplates($portal);

        // NOTE: the TimePulseTemplate data objects used here will be transformed again by
        // TimePulesesService->updateTimePulseTemplate() and stored in an array in the extras
        // column of the `portal` database table (with key 'TIME_TEXT_ARRAY')
        if (isset($timePulseTemplateId)) {
            $timePulseTemplate = $timePulsesService->getTimePulseTemplate($portal, $timePulseTemplateId);
            if (!$timePulseTemplate) {
                throw new Exception('could not find time pulse template with ID '.$timePulseTemplateId);
            }
        } else {
            $timePulseTemplate = new TimePulseTemplate();
            $timePulseTemplate->setContextId($portal->getId());
            if (0 === count($timePulseTemplates)) {
                // NOTE: if defined, the id property of the TimePulseTemplate data object will
                // get used as the item's index in the 'TIME_TEXT_ARRAY'; for the first array
                // item, we explicitly set the id to 1 since the legacy code (which processes
                // the 'TIME_TEXT_ARRAY' items) requires 1-based array indexes
                $timePulseTemplate->setId(1);
            }
        }

        $editForm = $this->createForm(TimePulseTemplateType::class, $timePulseTemplate);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $clickedButtonName = $editForm->getClickedButton()->getName();

            if ('new' === $clickedButtonName || 'update' === $clickedButtonName) {
                $timePulsesService->updateTimePulseTemplate($portal, $timePulseTemplate);
            } else {
                if ('delete' === $clickedButtonName) {
                    $timePulsesService->removeTimePulseTemplate($portal, $timePulseTemplateId);
                }
            }

            if ('new' === $clickedButtonName || 'update' === $clickedButtonName || 'delete' === $clickedButtonName) {
                $entityManager->persist($portal);
                $entityManager->flush();
                $timePulsesService->updateTimePulseLabels($portal);
            }

            return $this->redirectToRoute('app_portalsettings_timepulses', [
                'portalId' => $portal->getId(),
            ]);
        }

        return $this->render('portal_settings/time_pulses.html.twig', [
            'optionsForm' => $optionsForm->createView(),
            'editForm' => $editForm->createView(),
            'portal' => $portal,
            'timePulseTemplateId' => $timePulseTemplateId,
            'timePulseTemplates' => $timePulseTemplates,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/announcements')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function announcements(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $portalForm = $this->createForm(PortalAnnouncementsType::class, $portal);
        $portalForm->handleRequest($request);
        if ($portalForm->isSubmitted() && $portalForm->isValid()) {
            $entityManager->persist($portal);
            $entityManager->flush();

            return $this->redirectToRoute('app_portalsettings_announcements', [
                'portalId' => $portal->getId(),
                'tab' => 'portal',
            ]);
        }

        $server = $entityManager->getRepository(Server::class)->getServer();
        $serverForm = $this->createForm(ServerAnnouncementsType::class, $server);
        if ($this->isGranted(RootVoter::ROOT)) {
            $serverForm->handleRequest($request);
            if ($serverForm->isSubmitted() && $serverForm->isValid()) {
                $entityManager->persist($server);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_announcements', [
                    'portalId' => $portal->getId(),
                    'tab' => 'server',
                ]);
            }
        }

        return $this->render('portal_settings/announcements.html.twig', [
            'portalForm' => $portalForm->createView(),
            'serverForm' => $serverForm->createView(),
            'tab' => $request->query->has('tab') ? $request->query->get('tab') : 'portal',
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/contents')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function contents(Portal $portal, Request $request, EntityManagerInterface $entityManager): Response
    {
        $termsForm = $this->createForm(TermsType::class, $portal);
        $termsForm->handleRequest($request);
        if ($termsForm->isSubmitted() && $termsForm->isValid()) {
            if ('save' === $termsForm->getClickedButton()->getName()) {
                $portal->setAGBChangeDate(new DateTimeImmutable());
                $entityManager->persist($portal);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_contents', [
                    'portalId' => $portal->getId(),
                    'tab' => 'tou',
                ]);
            }
        }

        $server = $entityManager->getRepository(Server::class)->getServer();
        $dataPrivacyForm = $this->createForm(DataPrivacyType::class, $server);
        $impressumForm = $this->createForm(ImpressumType::class, $server);
        $accessibilityForm = $this->createForm(AccessibilityType::class, $server);
        if ($this->isGranted(RootVoter::ROOT)) {
            $dataPrivacyForm->handleRequest($request);
            if ($dataPrivacyForm->isSubmitted() && $dataPrivacyForm->isValid()) {
                if ('save' === $dataPrivacyForm->getClickedButton()->getName()) {
                    $entityManager->persist($server);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_portalsettings_contents', [
                        'portalId' => $portal->getId(),
                        'tab' => 'data_privacy',
                    ]);
                }
            }

            $impressumForm->handleRequest($request);
            if ($impressumForm->isSubmitted() && $impressumForm->isValid()) {
                if ('save' === $impressumForm->getClickedButton()->getName()) {
                    $entityManager->persist($server);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_portalsettings_contents', [
                        'portalId' => $portal->getId(),
                        'tab' => 'impressum',
                    ]);
                }
            }

            $accessibilityForm->handleRequest($request);
            if ($accessibilityForm->isSubmitted() && $accessibilityForm->isValid()) {
                if ('save' === $accessibilityForm->getClickedButton()->getName()) {
                    $entityManager->persist($server);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_portalsettings_contents', [
                        'portalId' => $portal->getId(),
                        'tab' => 'accessibility',
                    ]);
                }
            }
        }

        return $this->render('portal_settings/contents.html.twig', [
            'termsForm' => $termsForm->createView(),
            'dataPrivacyForm' => $dataPrivacyForm->createView(),
            'impressumForm' => $impressumForm->createView(),
            'accessibilityForm' => $accessibilityForm->createView(),
            'portal' => $portal,
            'tab' => $request->query->has('tab') ? $request->query->get('tab') : 'portal',
        ]);
    }

    /**
     * Handles portal terms templates for use inside rooms.
     */
    #[Route(path: '/portal/{portalId}/settings/contents/roomTermsTemplates/{termId}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function roomTermsTemplates(
        Portal $portal,
        Request $request,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment,
        int $termId = null
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(Terms::class);

        if ($termId) {
            /** @noinspection PhpUndefinedMethodInspection */
            $term = $repository->findOneById($termId);
        } else {
            $term = new Terms();
            $term->setContextId($portal->getId());
        }

        $form = $this->createForm(TermType::class, $term, []);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ('delete' == $form->getClickedButton()->getName()) {
                $em->remove($term);
                $em->flush();
            } else {
                $em->persist($term);
            }

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('app_portalsettings_roomtermstemplates', [
                'portalId' => $portal->getId(),
            ]);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $terms = $repository->findByContextId($portal->getId());

        $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');

        return $this->render('portal_settings/room_terms_templates.html.twig', [
            'form' => $form->createView(),
            'portalId' => $portal->getId(),
            'terms' => $terms,
            'termId' => $termId,
            'item' => $legacyEnvironment->getCurrentPortalItem(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/{userId}/deleteUser')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDeleteUser(
        $portalId,
        $userId,
        Portal $portal,
        UserService $userService,
        Request $request
    ): Response {
        $IdsMailRecipients = [];
        $user = $userService->getUser($userId);

        $formOptions = [
            'user' => $user,
            'portal' => $portal,
        ];

        $form = $this->createForm(AccountIndexDeleteUserType::class, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($form->get('execute')->isClicked()) {
                $IdsMailRecipients[] = $userId;
                $user = $userService->getUser($userId);
                $user->delete();
                $user->save();
                $this->addFlash('deleteSuccess', true);

                return $this->redirectToRoute('app_portalsettings_accountindexsendmail', [
                    'portalId' => $portalId,
                    'recipients' => implode(', ', $IdsMailRecipients),
                    'action' => 'user-delete',
                ]);
            } else {
                if ($form->get('cancel')->isClicked()) {
                    return $this->redirectToRoute('app_portalsettings_accountindex', [
                        'portalId' => $portalId,
                    ]);
                }
            }
        }

        return $this->render('portal_settings/account_index_delete_user.html.twig', [
            'form' => $form->createView(),
            'portalId' => $portalId,
            'userId' => $userId,
            'user' => $user,
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/{userIds}/performUserAction/{action}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexPerformUserAction(
        $portalId,
        $userIds,
        $action,
        Portal $portal,
        UserService $userService,
        Request $request,
        AccountManager $accountManager
    ): Response {
        $users = [];
        $userNames = [];

        foreach (explode(', ', $userIds) as $userId) {
            $user = $userService->getUser($userId);
            $users[] = $user;
            $userNames[] = $user->getFullName();
        }

        $formOptions = [
            'action' => $action,
            'users' => $users,
            'portal' => $portal,
        ];

        $form = $this->createForm(AccountIndexPerformUserActionType::class, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($form->get('execute')->isClicked()) {
                $IdsMailRecipients = [];
                switch ($action) {
                    case 'user-delete':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->delete();
                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }
                        $this->addFlash('deleteSuccess', true);
                        break;
                    case 'user-block':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->reject();

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->lock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-confirm':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->makeUser();

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->unlock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-status-reading-user':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->setStatus(4);

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->unlock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-status-user':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->makeUser();
                            $user->setStatus(2);

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->unlock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-status-moderator':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->makeModerator();
                            $user->setStatus(3);

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->unlock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-contact':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->makeContactPerson();

                            $account = $accountManager->getAccount($user, $portal->getId());
                            $accountManager->unlock($account);

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    case 'user-contact-remove':
                        foreach (explode(',', $userIds) as $userId) {
                            $user = $userService->getUser($userId);
                            $user->makeNoContactPerson();

                            $user->save();
                            $IdsMailRecipients[] = $userId;
                        }

                        $this->addFlash('performedSuccessfully', true);
                        break;
                    default:
                        // $user->delete();
                        // $user->save();
                        $this->addFlash('deleteSuccess', true);
                        $action = 'user-delete';
                }

                return $this->redirectToRoute('app_portalsettings_accountindexsendmail', [
                    'portalId' => $portalId,
                    'recipients' => implode(', ', $IdsMailRecipients),
                    'action' => $action,
                ]);
            } else {
                if ($form->get('cancel')->isClicked()) {
                    return $this->redirectToRoute('app_portalsettings_accountindex', [
                        'portalId' => $portalId,
                    ]);
                }
            }
        }

        return $this->render('portal_settings/account_index_perform_user.html.twig', [
            'form' => $form->createView(),
            'portalId' => $portalId,
            'users' => implode(', ', $userNames),
            'action' => $action,
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndex(
        $portalId,
        Portal $portal,
        UserService $userService,
        Request $request,
        LegacyEnvironment $environment,
        PaginatorInterface $paginator,
        AuthSourceRepository $authSourceRepository
    ): Response {
        // moderation is true to avoid limit of status=2 being set, which would exclude e.g. locked users
        $portalUsers = $userService->getListUsers($portal->getId(), null, null, true);
        $authSources = $authSourceRepository->findByPortal($portalId);
        $userList = [];
        $alreadyIncludedUserIDs = [];
        foreach ($portalUsers as $portalUser) {
            if (!in_array($portalUser->getUserID(),
                $alreadyIncludedUserIDs) and $portalUser->getContextID() == $portalId) {
                $userList[] = $portalUser;
                $alreadyIncludedUserIDs[] = $portalUser->getUserID();
            }
        }
        unset($alreadyIncludedUserIDs);

        $accountIndex = new AccountIndex();

        $accountIndexUserList = [];
        $accountIndexUserIds = [];

        foreach ($userList as $singleUser) {
            $singleAccountIndexUser = new AccountIndexUser();
            $singleAccountIndexUser->setName($singleUser->getFullName());
            $singleAccountIndexUser->setChecked(false);
            $singleAccountIndexUser->setItemId($singleUser->getItemID());
            $singleAccountIndexUser->setMail($singleUser->getEmail());
            $singleAccountIndexUser->setUserId($singleUser->getUserID());
            $accountIndexUserList[] = $singleAccountIndexUser;
            $accountIndexUserIds[$singleUser->getItemID()] = false;
        }

        $accountIndex->setAccountIndexUsers($accountIndexUserList);
        $accountIndex->setIds($accountIndexUserIds);
        $form = $this->createForm(AccountIndexType::class, $accountIndex);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($form->get('search')->isClicked()) {
                // moderation is true to avoid limit of status=2 being set, which would exclude e.g. locked users
                $portalUsers = $userService->getListUsers($portal->getId(), null, null, true);
                $tempUserList = [];
                $userList = [];
                foreach ($portalUsers as $portalUser) {
                    $tempUserList[] = $portalUser;
                }
                $searchParam = $data->getAccountIndexSearchString();

                if (empty($searchParam)) {
                    foreach ($tempUserList as $singleUser) {
                        if ($this->meetsFilterChoiceCriteria($data->getUserIndexFilterChoice(), $singleUser, $portal,
                            $environment)) {
                            $userList[] = $singleUser; // remove users not fitting the search string
                        }
                    }
                } else {
                    foreach ($tempUserList as $singleUser) {
                        $machtesUserIdLowercased = str_contains(strtolower($singleUser->getUserID()),
                            strtolower($searchParam));
                        $machtesUserNameLowercased = str_contains(strtolower($singleUser->getFullName()),
                            strtolower($searchParam));
                        $matchesFirstNameLowercased = str_contains(strtolower($singleUser->getFirstName()),
                            strtolower($searchParam));
                        $matchesLastNameLowercased = str_contains(strtolower($singleUser->getLastName()),
                            strtolower($searchParam));
                        $matchMailLowercased = str_contains(strtolower($singleUser->getEmail()),
                            strtolower($searchParam));

                        if (($matchesLastNameLowercased
                                or $machtesUserIdLowercased
                                or $matchesFirstNameLowercased
                                or $machtesUserNameLowercased
                                or $matchMailLowercased) and $this->meetsFilterChoiceCriteria($data->getUserIndexFilterChoice(),
                                    $singleUser, $portal, $environment)) {
                            $userList[] = $singleUser; // remove users not fitting the search string
                        }
                    }
                }

                $accountIndex = new AccountIndex();
                $accountIndex->setUserIndexFilterChoice($data->getUserIndexFilterChoice());
                $accountIndexUserList = [];
                $accountIndexUserIds = [];

                foreach ($userList as $singleUser) {
                    $singleAccountIndexUser = new AccountIndexUser();
                    $singleAccountIndexUser->setName($singleUser->getFullName());
                    $singleAccountIndexUser->setChecked(false);
                    $singleAccountIndexUser->setItemId($singleUser->getItemID());
                    $singleAccountIndexUser->setMail($singleUser->getEmail());
                    $singleAccountIndexUser->setUserId($singleUser->getUserID());
                    $accountIndexUserList[] = $singleAccountIndexUser;
                    $accountIndexUserIds[$singleUser->getItemID()] = false;
                }

                $accountIndex->setAccountIndexUsers($accountIndexUserList);
                $accountIndex->setIds($accountIndexUserIds);
                $form = $this->createForm(AccountIndexType::class, $accountIndex);
            } elseif ($form->get('execute')->isClicked()) {
                $data = $form->getData();
                $ids = $data->getIds();
                $userIds = [];

                foreach ($ids as $id => $checked) {
                    if ($checked) {
                        $userIds[] = $id;
                    }
                }

                switch ($data->getIndexViewAction()) {
                    case 0:
                        break;
                    case 1: // user-delete
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-delete',
                        ]);
                    case 2: // user-block
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-block',
                        ]);

                    case 3: // user-confirm
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-confirm',
                        ]);

                    case 4: // change user mail the next time he/she logs in
                        foreach ($ids as $id => $checked) {
                            if ($checked) {
                                $user = $userService->getUser($id);
                                $user->setHasToChangeEmail();
                                $user->save();
                            }
                        }
                        break;
                    case 'user-status-reading-user':
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-status-reading-user',
                        ]);

                    case 5: // 'user-status-user
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-status-user',
                        ]);

                    case 6: // user-status-moderator
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-status-moderator',
                        ]);
                    case 7: // user-contact
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-contact',
                        ]);
                    case 8: // user-contact-remove
                        return $this->redirectToRoute('app_portalsettings_accountindexperformuser', [
                            'portalId' => $portalId,
                            'userIds' => implode(', ', $userIds),
                            'action' => 'user-contact-remove',
                        ]);
                    case 9: // send mail
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if ($checked) {
                                array_push($IdsMailRecipients, $id);
                            }
                        }

                        return $this->redirectToRoute('app_portalsettings_accountindexsendmail', [
                            'portalId' => $portalId,
                            'recipients' => implode(', ', $IdsMailRecipients),
                        ]);
                    case 11: // send mail merge userIDs
                        $IdsMailRecipients = [];
                        foreach ($ids as $id => $checked) {
                            if ($checked) {
                                array_push($IdsMailRecipients, $id);
                            }
                        }

                        return $this->redirectToRoute('app_portalsettings_accountindexsendmergemail', [
                            'portalId' => $portalId,
                            'recipients' => implode(', ', $IdsMailRecipients),
                        ]);
                    case 13: // hide mail everywhere
                        foreach ($ids as $id => $checked) {
                            if ($checked) {
                                $user = $userService->getUser($id);
                                $user->setEmailNotVisible();
                                $user->save();
                                $allRelatedUsers = $user->getRelatedUserList(true);
                                foreach ($allRelatedUsers as $relatedUser) {
                                    $relatedUser->setEmailNotVisible();
                                    $relatedUser->save();
                                }
                            }
                        }
                        break;
                    case 15: // show mail everywhere
                        foreach ($ids as $id => $checked) {
                            if ($checked) {
                                $user = $userService->getUser($id);
                                $user->setEmailVisible();
                                $user->save();
                                $allRelatedUsers = $user->getRelatedUserList(true);
                                foreach ($allRelatedUsers as $relatedUser) {
                                    $relatedUser->setEmailVisible();
                                    $relatedUser->save();
                                }
                            }
                        }
                        break;
                }

                $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                    'portalId' => $portal->getId(),
                ]);

                if (0 != $data->getIndexViewAction()) {
                    $this->addFlash('performedSuccessfully', $returnUrl);

                    return $this->redirectToRoute('app_portalsettings_accountindex', [
                        'portalId' => $portal->getId(),
                    ]);
                }
            }
        }
        $pagination = $paginator->paginate(
            $userList,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('portal_settings/account_index.html.twig', [
            'form' => $form->createView(),
            'userList' => $userList,
            'portal' => $portal,
            'pagination' => $pagination,
            'authSources' => $authSources,
        ]);
    }

    private function meetsFilterChoiceCriteria($filterChoice, $userInQuestion, $portal, LegacyEnvironment $environment)
    {
        $meetsCriteria = false;
        switch ($filterChoice) {
            case 0: // no selection
                $meetsCriteria = true;
                break;
            case 1: // Members
                if ($userInQuestion->isRoomMember()) {
                    $meetsCriteria = true;
                }
                break;
            case 2: // locked // ->isLocked() only exhibits the extra flag 'LOCKED', not the set status
                if ('0' == $userInQuestion->getStatus()) {
                    $meetsCriteria = true;
                }
                break;
            case 3: // In activation
                if ($userInQuestion->isRequested()) {
                    $meetsCriteria = true;
                }
                break;
            case 4: // User
                if ($userInQuestion->isUser()) {
                    $meetsCriteria = true;
                }
                break;
            case 5: // Moderator
                if ($userInQuestion->isModerator()) {
                    $meetsCriteria = true;
                }
                break;
            case 6: // Contact
                if ($userInQuestion->isContact()) {
                    $meetsCriteria = true;
                }
                break;
            case 7: // Community workspace moderator
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isModerator()
                        and 'community' === $continuousWorkspace->getType()) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 8: // Community workspace contact
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isContact()
                        and 'community' === $continuousWorkspace->getType()) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 9: // Project workspace moderator
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isModerator()
                        and 'project' === $continuousWorkspace->getType()) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 10: // project workspace contact
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isContact
                        and 'project' === $continuousWorkspace->getType()) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 11: // moderator of any workspace
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);
                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isModerator()) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 12: // contact of any workspace
                $continuousWorkspaces = $this->getContinuousRoomList($environment, $portal);

                foreach ($continuousWorkspaces as $continuousWorkspace) {
                    if ($continuousWorkspace->getItemID() === $userInQuestion->getContextItem()->getItemID()
                        and $userInQuestion->isCOntact) {
                        $meetsCriteria = true;
                    }
                }
                break;
            case 13: // no workspace membership
                if (!$userInQuestion->isRoomMember()) {
                    $meetsCriteria = true;
                }
                break;
        }

        return $meetsCriteria;
    }

    private function getContinuousRoomList($environment, $portal)
    {
        $manager = $environment->getEnvironment()->getRoomManager();
        $manager->reset();
        $manager->resetLimits();
        $manager->setContextLimit($portal->getId());
        $manager->setContinuousLimit();
        $manager->select();

        return $manager->get();
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/sendmail/{recipients}/{action}', defaults: ['action' => 'user-account_send_mail'])]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexSendMail(
        $portalId,
        $recipients,
        $action,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserService $userService,
        ItemService $itemService,
        Mailer $mailer,
        Portal $portal,
        RouterInterface $router
    ): Response {
        $user = $userService->getCurrentUserItem();
        $recipientArray = [];
        $recipients = explode(', ', $recipients);
        foreach ($recipients as $recipient) {
            $currentUser = $userService->getUser($recipient);
            array_push($recipientArray, $currentUser);
        }

        $multipleRecipients = sizeof($recipientArray) > 1;

        $sendMail = new AccountIndexSendMail();
        $sendMail->setRecipients($recipientArray);

        $chosenAction = $action ?? 'user-account_send_mail';
        $accountMail = new AccountMail($legacyEnvironment, $router);
        $body = $accountMail->generateBody($recipientArray[0], $chosenAction, $multipleRecipients);
        $subject = $accountMail->generateSubject($chosenAction);
        $sendMail->setSubject($subject);
        $sendMail->setMessage($body);

        $form = $this->createForm(AccountIndexSendMailType::class, $sendMail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();
                $mailRecipients = $data->getRecipients();

                if ($data->getCopyToSender()) {
                    $mailRecipients[] = $userService->getCurrentUserItem();
                }

                $recipientCount = 0;

                foreach ($mailRecipients as $mailRecipient) {
                    $item = $itemService->getTypedItem($mailRecipient->getItemId());
                    $email = $mailAssistant->getAccountIndexActionMessage($form, $item);
                    $mailer->sendEmailObject($email, $portal->getTitle());

                    if (!is_null($email->getTo())) {
                        $recipientCount += count($email->getTo());
                    }
                    if (!is_null($email->getCc())) {
                        $recipientCount += count($email->getCc());
                    }
                    if (!is_null($email->getBcc())) {
                        $recipientCount += count($email->getBcc());
                    }
                }

                $this->addFlash('recipientCount', $recipientCount);

                $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                    'portalId' => $portal->getId(),
                ]);
                $this->addFlash('savingSuccessfull', $returnUrl);
            } elseif ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_portalsettings_accountindex', [
                    'portalId' => $portal->getId(),
                ]);
            }
        }

        return $this->render('portal_settings/account_index_send_mail.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'recipients' => $recipientArray,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/sendmergemail/{recipients}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexSendMergeMail(
        Portal $portal,
        $portalId,
        $recipients,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        MailAssistant $mailAssistant,
        UserService $userService,
        ItemService $itemService,
        Mailer $mailer,
        RouterInterface $router
    ): Response {
        $recipientArray = [];
        $recipients = explode(', ', $recipients);
        foreach ($recipients as $recipient) {
            $currentUser = $userService->getUser($recipient);
            array_push($recipientArray, $currentUser);
        }

        $sendMail = new AccountIndexSendMergeMail();
        $sendMail->setRecipients($recipientArray);

        $action = 'user-account-merge';
        $accountMail = new AccountMail($legacyEnvironment, $router);
        $body = $accountMail->generateBody($userService->getCurrentUserItem(), $action);
        $subject = $accountMail->generateSubject($action);
        $sendMail->setSubject($subject);
        $sendMail->setMessage($body);

        $form = $this->createForm(AccountIndexSendMergeMailType::class, $sendMail);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $mailRecipients = $data->getRecipients();

            $recipientCount = 0;
            foreach ($mailRecipients as $mailRecipient) {
                $item = $itemService->getTypedItem($mailRecipient->getItemId());
                $email = $mailAssistant->getAccountIndexPasswordMessage($form, $item);
                $mailer->sendEmailObject($email, $portal->getTitle());

                if (!is_null($email->getTo())) {
                    $recipientCount += count($email->getTo());
                }
                if (!is_null($email->getCc())) {
                    $recipientCount += count($email->getCc());
                }
                if (!is_null($email->getBcc())) {
                    $recipientCount += count($email->getBcc());
                }
            }

            $this->addFlash('recipientCount', $recipientCount);

            $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                'portalId' => $portal->getId(),
            ]);
            $this->addFlash('savedSuccess', $returnUrl);
        }

        return $this->render('portal_settings/account_index_send_merge_mail.html.twig', [
            'portal' => $portal,
            'form' => $form->createView(),
            'recipients' => $recipientArray,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/detail/{userId}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetail(
        Portal $portal,
        Request $request,
        UserService $userService,
        AuthSourceRepository $authSourceRepository,
        RoomService $roomService,
        TranslatorInterface $translator,
        Security $security,
        UserListBuilder $userListBuilder,
        AccountManager $accountManager
    ): Response {
        $userList = $userService->getListUsers($portal->getId());
        $form = $this->createForm(AccountIndexDetailType::class, $portal);
        $form->handleRequest($request);
        $user = $userService->getUser($request->get('userId'));

        $communityArchivedListNames = [];
        $communityListNames = [];
        $projectsListNames = [];
        $projectsArchivedListNames = [];
        $userRoomListNames = [];
        $userRoomsArchivedListNames = [];
        $privateRoomNameList = [];
        $privateRoomArchivedNameList = [];

        $accountOfUser = $accountManager->getAccount($user, $portal->getId());
        $relatedUsers = $userListBuilder
            ->fromAccount($accountOfUser)
            ->withProjectRoomUser()
            ->withCommunityRoomUser()
            ->withUserRoomUser()
            ->withPrivateRoomUser()
            ->getList();

        foreach ($relatedUsers as $relatedUser) {
            $contextID = $relatedUser->getContextID();
            $locked = '0' === $relatedUser->getStatus() ? '('.$translator->trans('Locked', [], 'portal').') ' : '';
            $relatedRoomItem = $roomService->getRoomItem($contextID);
            if ('project' === $relatedRoomItem->getType()) {
                if ('2' == $relatedRoomItem->getStatus()) {
                    $projectsArchivedListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' ) (ARCH.)';
                } else {
                    $projectsListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' )';
                }
            } elseif ('community' === $relatedRoomItem->getType()) {
                if ('2' == $relatedRoomItem->getStatus()) {
                    $communityArchivedListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' ) (ARCH.)';
                } else {
                    $communityListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' )';
                }
            } elseif ('userroom' === $relatedRoomItem->getType()) {
                if ('2' == $relatedRoomItem->getStatus()) {
                    $userRoomsArchivedListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' ) (ARCH.)';
                } else {
                    $userRoomListNames[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' )';
                }
            } elseif ('privateroom' === $relatedRoomItem->getType()) {
                if ('2' == $relatedRoomItem->getStatus()) {
                    $privateRoomArchivedNameList[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' ) (ARCH.)';
                } else {
                    $privateRoomNameList[] = $locked.$relatedRoomItem->getTitle().'( ID: '.$relatedRoomItem->getItemID().' )';
                }
            }
        }

        $key = 0;
        $counter = 0;
        $hasNext = true;
        $hasPrevious = true;

        foreach ($userList as $userItem) {
            if ($userItem->getItemID() === $user->getItemID()) {
                $key = $counter;
                break;
            }
            $counter = $counter + 1;
        }

        if ($key + 1 == sizeof($userList)) {
            $hasNext = false;
        }
        if (0 == $key) {
            $hasPrevious = false;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('next')->isClicked() or $form->get('previous')->isClicked()) {
                if ($form->get('next')->isClicked()) {
                    if ($key + 1 < sizeof($userList)) {
                        $user = $userList[$key + 1];
                    }
                }
                if ($form->get('previous')->isClicked()) {
                    if ($key > 0) {
                        $user = $userList[$key - 1];
                    }
                }

                return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
                    'portal' => $portal,
                    'portalId' => $portal->getId(),
                    'userId' => $user->getItemID(),
                    'communities' => implode(', ', $communityListNames),
                    'projects' => implode(', ', $projectsListNames),
                    'privaterooms' => implode(', ', $privateRoomNameList),
                    'userrooms' => implode(', ', $userRoomListNames),
                    'communitiesArchived' => implode(', ', $communityArchivedListNames),
                    'projectsArchived' => implode(', ', $projectsArchivedListNames),
                    'privateRoomsArchived' => implode(', ', $privateRoomArchivedNameList),
                    'userroomsArchived' => implode(', ', $userRoomsArchivedListNames),
                    'hasNext' => $hasNext,
                    'hasPrevious' => $hasPrevious,
                ]);
            }

            if ($form->get('back')->isClicked()) {
                return $this->redirectToRoute('app_portalsettings_accountindex', [
                    'portalId' => $portal->getId(),
                ]);
            }
        }

        $canImpersonate = $security->isGranted('ROLE_ROOT');
        if (!$canImpersonate) {
            /** @var Account $account */
            $account = $security->getUser();
            $portalUser = $userService->getPortalUser($account);

            $canImpersonate = $portalUser->getCanImpersonateAnotherUser() ||
                ($portalUser->getImpersonateExpiryDate() !== null && $portalUser->getImpersonateExpiryDate() < new DateTimeImmutable());
        }

        return $this->render('portal_settings/account_index_detail.html.twig', [
            'accountOfUser' => $accountOfUser,
            'user' => $user,
            'canImpersonate' => $canImpersonate,
            'authSource' => $authSourceRepository->findOneBy(['id' => $user->getAuthSource()]),
            'form' => $form->createView(),
            'portal' => $portal,
            'communities' => implode(', ', $communityListNames),
            'projects' => implode(', ', $projectsListNames),
            'privaterooms' => implode(', ', $privateRoomNameList),
            'userrooms' => implode(', ', $userRoomListNames),
            'communitiesArchived' => implode(', ', $communityArchivedListNames),
            'projectsArchived' => implode(', ', $projectsArchivedListNames),
            'privateRoomsArchived' => implode(', ', $privateRoomArchivedNameList),
            'userroomsArchived' => implode(', ', $userRoomsArchivedListNames),
            'hasNext' => $hasNext,
            'hasPrevious' => $hasPrevious,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountindex/detail/{userId}/edit')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailEdit(
        Portal $portal,
        Request $request,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ): Response {
        $environment = $legacyEnvironment->getEnvironment();

        $user = $userService->getUser($request->get('userId'));
        $userEdit = new PortalUserEdit();
        $userEdit->setFirstName($user->getFirstname());
        $userEdit->setLastName($user->getLastName());
        $userEdit->setAcademicDegree($user->getTitle());

        $userEdit->setBirthday($user->getBirthday());
        $userEdit->setStreet($user->getStreet());
        $userEdit->setZip($user->getZipcode());
        $userEdit->setCity($user->getCity());
        $userEdit->setWorkspace($user->getRoom());
        $userEdit->setTelephone($user->getTelephone());
        $userEdit->setSecondTelephone($user->getCellularphone());
        $userEdit->setEmail($user->getEmail());
        $userEdit->setICQ($user->getIcq());
        $userEdit->setMSN($user->getMsn());
        $userEdit->setSkype($user->getSkype());
        $userEdit->setYahoo($user->getYahoo());
        $userEdit->setHomepage($user->getHomepage());
        $userEdit->setDescription($user->getDescription());
        $userEdit->setMayCreateContext($user->getIsAllowedToCreateContext());
//        $userEdit->setPicture($user->getPicture());

//        $uploadUrl = $this->generateUrl('app_upload_upload', array(
//            'roomId' => $portal->getId(),
//            'itemId' => $user->getItemID(),
//        ));

//        $userEdit->setUploadUrl($uploadUrl);

        $form = $this->createForm(AccountIndexDetailEditType::class, $userEdit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PortalUserEdit $editAccountIndex */
            $editAccountIndex = $form->getData();
            $user->setFirstname($editAccountIndex->getFirstName());
            $user->setLastname($editAccountIndex->getLastName());
            $user->setTitle($editAccountIndex->getAcademicDegree());
            $user->setBirthday($editAccountIndex->getBirthday());
            $user->setStreet($editAccountIndex->getStreet());
            $user->setZipcode($editAccountIndex->getZip());
            $user->setCity($editAccountIndex->getCity());
            $user->setRoom($editAccountIndex->getWorkspace());
            $user->setTelephone($editAccountIndex->getTelephone());
            $user->setCellularphone($editAccountIndex->getSecondTelephone());
            $user->setEmail($editAccountIndex->getEmail());

            if ($editAccountIndex->getEmailChangeAll()) {
                $relatedUsers = $user->getRelatedUserList();
                foreach ($relatedUsers as $relatedUser) {
                    $relatedUser->setEmail($editAccountIndex->getEmail());
                    $relatedUser->save();
                }
            }
            $user->setICQ($editAccountIndex->getIcq());
            $user->setMSN($editAccountIndex->getMsn());
            $user->setSkype($editAccountIndex->getSkype());
            $user->setYahoo($editAccountIndex->getYahoo());
            $user->setHomepage($editAccountIndex->getHomepage());
            $user->setDescription($editAccountIndex->getDescription());

//            if (!empty($editAccountIndex->getPicture())) {
//                //TODO: Does this piece of code make sense, if we set a new picture anyway?
//                if ($editAccountIndex->isOverrideExistingPicture()) {
//                    $disc_manager = $environment->getDiscManager();
//                    if ($disc_manager->existsFile($user->getPicture())) {
//                        $disc_manager->unlinkFile($user->getPicture());
//                    }
//                    $user->setPicture('');
//                    if (isset($portal_user_item)) {
//                        $portal_user_item->setPicture('');
//                    }
//                }
//
//                $filename = 'cid' . $environment->getCurrentContextID() . '_' . $user_item->getUserID() . '_' . $_FILES['upload']['name'];
//                $disc_manager = $environment->getDiscManager();
//                $disc_manager->copyFile($_FILES['upload']['tmp_name'], $filename, true);
//                $user_item->setPicture($filename);
//                if (isset($portal_user_item)) {
//                    if ($disc_manager->copyImageFromRoomToRoom($filename, $portal_user_item->getContextID())) {
//                        $value_array = explode('_', $filename);
//                        $old_room_id = $value_array[0];
//                        $old_room_id = str_replace('cid', '', $old_room_id);
//                        $value_array[0] = 'cid' . $portal_user_item->getContextID();
//                        $new_picture_name = implode('_', $value_array);
//                        $portal_user_item->setPicture($new_picture_name);
//                    }
//                }
//
//                $user->setPicture($editAccountIndex->getPicture());
//            }

            if ('standard' == $editAccountIndex->getMayCreateContext()) {
                $user->setIsAllowedToCreateContext(true); // TODO how do we get the pre-set portal value?
            } elseif ('1' == $editAccountIndex->getMayCreateContext()) {
                $user->setIsAllowedToCreateContext(true);
                $user->getRelatedPortalUserItem()->setIsAllowedToCreateContext(true);
            } else {
                $user->setIsAllowedToCreateContext(false);
                $user->getRelatedPortalUserItem()->setIsAllowedToCreateContext(false);
            }

            $returnUrl = $this->generateUrl('app_portalsettings_accountindexdetail', [
                'portalId' => $portal->getId(),
                'userId' => $user->getItemID(),
            ]);
            $user->save();
            $this->addFlash('savedSuccess', $returnUrl);
        }

        return $this->render('portal_settings/account_index_detail_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{userId}/changeStatus')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailChangeStatus(
        Portal $portal,
        Request $request,
        UserService $userService,
        TranslatorInterface $translator,
        AccountManager $accountManager
    ): Response {
        $user = $userService->getUser($request->get('userId'));
        $userChangeStatus = new PortalUserChangeStatus();
        $userChangeStatus->setName($user->getFullName());
        $userChangeStatus->setUserID($user->getUserID());
        $userChangeStatus->setLastLogin($user->getLastLogin());

        $userStatus = $user->getStatus();
        $currentStatus = 'Moderator';
        if (0 == $userStatus) {
            $currentStatus = 'Close';
        } elseif (2 == $userStatus) {
            $currentStatus = 'User';
        } elseif (3 == $userStatus) {
            $currentStatus = 'Moderator';
        }

        $trans = $translator->trans($currentStatus, [], 'portal');

        $userChangeStatus->setCurrentStatus($trans);
        $userChangeStatus->setNewStatus(strtolower($currentStatus));
        $userChangeStatus->setContact($user->isContact());
        $userChangeStatus->setLoginIsDeactivated(!$user->getCanImpersonateAnotherUser());
        $userChangeStatus->setImpersonateExpiryDate($user->getImpersonateExpiryDate());

        $form = $this->createForm(AccountIndexDetailChangeStatusType::class, $userChangeStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userService->getUser($request->get('userId'));

            $account = $accountManager->getAccount($user, $portal->getId());

            /** @var PortalUserChangeStatus $data */
            $data = $form->getData();
            $newStatus = $data->getNewStatus();
            if (0 == strcmp($newStatus, 'user')) {
                $user->makeUser();
                $accountManager->unlock($account);
            } elseif (0 == strcmp($newStatus, 'moderator')) {
                $user->makeModerator();
                $accountManager->unlock($account);
            } elseif (0 == strcmp($newStatus, 'close')) {
                $user->reject();
                $accountManager->lock($account);
            }

            if ($data->isContact()) {
                $user->makeContactPerson();
            } else {
                $user->makeNoContactPerson();
            }

            if ($this->isGranted(RootVoter::ROOT)) {
                $user->setCanImpersonateAnotherUser(!$data->getLoginIsDeactivated());
                $user->setImpersonateExpiryDate($data->getImpersonateExpiryDate());
            }

            $user->save();

            $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                'portalId' => $portal->getId(),
            ]);

            $this->addFlash('performedSuccessfully', $returnUrl);

            return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
                'portalId' => $request->get('portalId'),
                'userId' => $request->get('userId'),
            ]);
        }

        return $this->render('portal_settings/account_index_detail_change_status.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'portal' => $portal,
            'portalId' => $portal->getId(),
            'userId' => $user->getItemID(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{userId}/hidemailallwrks')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailHideMailAllWrks(
        Portal $portal,
        Request $request,
        UserService $userService
    ): RedirectResponse {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailNotVisible();
        $user->save();

        $relatedUsers = $user->getRelatedUserList();
        foreach ($relatedUsers as $relatedUser) {
            $relatedUser->setEmailNotVisible();
            $relatedUser->save();
        }

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{userId}/showmailallwroks')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailShowMailAllWroks(
        Portal $portal,
        Request $request,
        UserService $userService
    ): RedirectResponse {
        $user = $userService->getUser($request->get('userId'));
        $user->setEmailVisible();
        $user->save();

        $relatedUsers = $user->getRelatedUserList();
        foreach ($relatedUsers as $relatedUser) {
            $relatedUser->setEmailVisible();
            $relatedUser->save();
        }

        $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
            'portalId' => $portal->getId(),
        ]);

        $this->addFlash('performedSuccessfully', $returnUrl);

        return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
            'portalId' => $request->get('portalId'),
            'userId' => $request->get('userId'),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{userId}/takeOver')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailTakeOver(
        Portal $portal,
        UserService $userService,
        Request $request,
        $userId
    ): RedirectResponse {
        $portalUser = $userService->getUser($userId);

        $session = $request->getSession();
        $session->set('takeover_context', $portal->getId());
        $session->set('takeover_authSourceId', (int) $portalUser->getAuthSource());

        return $this->redirectToRoute('app_helper_portalenter', [
            'context' => $portal->getId(),
            '_switch_user' => $portalUser->getUserID(),
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{userId}/assignWorkspace')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailAssignWorkspace(
        Portal $portal,
        Request $request,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        AccountManager $accountManager
    ): Response {
        $user = $userService->getUser($request->get('userId'));
        $userAssignWorkspace = new PortalUserAssignWorkspace();
        $userAssignWorkspace->setUserID($user->getUserID());
        $userAssignWorkspace->setName($user->getFullName());
        $userAssignWorkspace->setWorkspaceSelection('0');
        $form = $this->createForm(AccountIndexDetailAssignWorkspaceType::class, $userAssignWorkspace);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked()) {
                $assignFlag = true;
                $choiceWorkspaceId = $form->get('workspaceSelection')->getViewData();
                $user = $userService->getUser($request->get('userId'));
                $relatedUsers = $user->getRelatedUserList();
                foreach ($relatedUsers as $relatedUser) {
                    if ($relatedUser->getContextID() == $choiceWorkspaceId) {
                        $assignFlag = false;
                        break;
                    }
                }

                if ($assignFlag) {
                    $formData = $form->getData();
                    $newUser = $user->cloneData();
                    $projectRoomManager = $legacyEnvironment->getEnvironment()->getProjectManager();
                    $newAssignedRoom = $projectRoomManager->getItem($choiceWorkspaceId);
                    $newUser->setContextID($newAssignedRoom->getItemID());
                    $newUser->setUserComment($formData->getDescriptionOfParticipation());
                    $newUser->save();

                    $newUser->makeUser();
                    $newUser->save();

                    $account = $accountManager->getAccount($newUser, $portal->getId());
                    $accountManager->unlock($account);

                    $returnUrl = $this->generateUrl('app_portalsettings_accountindex', [
                        'portalId' => $portal->getId(),
                    ]);

                    $this->addFlash('performedSuccessfully', $returnUrl);

                    return $this->redirectToRoute('app_portalsettings_accountindexdetail', [
                        'portalId' => $request->get('portalId'),
                        'userId' => $request->get('userId'),
                    ]);
                }

                $this->addFlash('unsuccessful', 'Already assigned');
            } elseif ($form->get('search')->isClicked()) {
                $user = $userService->getUser($request->get('userId'));
                $userAssignWorkspace = new PortalUserAssignWorkspace();
                $userAssignWorkspace->setUserID($user->getUserID());
                $userAssignWorkspace->setName($user->getFullName());
                $userAssignWorkspace->setWorkspaceSelection('0');

                $formData = $form->getData();

                $form = $this->createForm(AccountIndexDetailAssignWorkspaceType::class, $userAssignWorkspace);

                $projectRoomManager = $legacyEnvironment->getEnvironment()->getProjectManager();
                $projectRooms = $projectRoomManager->getRoomsByTitle($formData->getSearchForWorkspace(),
                    $portal->getId());

                if ($projectRooms->getCount() < 1) {
                    $repository = $this->getDoctrine()->getRepository(Room::class);
                    $projectRooms = $repository->findAll();
                }

                $choiceArray = [];

                foreach ($projectRooms as $currentRoom) {
                    $choiceArray[$currentRoom->getTitle()] = $currentRoom->getItemID();
                }

                $formOptions = [
                    'label' => 'Select workspace',
                    'expanded' => false,
                    'placeholder' => false,
                    'choices' => $choiceArray,
                    'translation_domain' => 'portal',
                    'required' => false,
                ];

                $form->add('workspaceSelection', ChoiceType::class, $formOptions);

                return $this->render('portal_settings/account_index_detail_assign_workspace.html.twig', [
                    'portal' => $portal,
                    'form' => $form->createView(),
                    'user' => $user,
                ]);
            }
        }

        return $this->render('portal_settings/account_index_detail_assign_workspace.html.twig', [
            'portal' => $portal,
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/accountIndex/detail/{accountId}/changePassword')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[ParamConverter('account', class: Account::class, options: ['id' => 'accountId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function accountIndexDetailChangePassword(
        Portal $portal,
        Account $account,
        Request $request,
        UserService $userService,
        UserPasswordHasherInterface $passwordHasher,
        ManagerRegistry $managerRegistry
    ): Response {
        $portalUser = $userService->getPortalUser($account);

        $formData = [
            'userName' => $account->getFirstname() . ' ' . $account->getLastname(),
            'userId' => $account->getUsername(),
            'password' => '',
        ];
        $form = $this->createForm(AccountIndexDetailChangePasswordType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $account->setPassword($passwordHasher->hashPassword($account, $data['password']));

            $em = $managerRegistry->getManager();
            $em->persist($account);
            $em->flush();
        }

        return $this->render('portal_settings/account_index_detail_change_password.html.twig', [
            'form' => $form->createView(),
            'portalUser' => $portalUser,
            'portal' => $portal,
        ]);
    }

    #[Route(path: '/portal/{portalId}/settings/translations/{translationId?}')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function translations(
        Portal $portal,
        ?int $translationId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $editForm = null;

        $repository = $entityManager->getRepository(Translation::class);

        $translation = null;
        if ($translationId) {
            $translation = $repository->find($translationId);

            if (!$translation) {
                throw new NotFoundHttpException('No translation found for given id');
            }

            $editForm = $this->createForm(TranslationType::class, $translation, []);

            $editForm->handleRequest($request);
            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $entityManager->persist($translation);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_translations', [
                    'portalId' => $portal->getId(),
                ]);
            }
        }

        $translations = $repository->findBy([
            'contextId' => $portal->getId(),
        ]);

        return $this->render('portal_settings/translations.html.twig', [
            'form' => $editForm?->createView(),
            'portal' => $portal,
            'translations' => $translations,
            'selectedTranslation' => $translation,
        ]);
    }

    private function generateRedirectForAuthType(string $type, Portal $portal)
    {
        switch ($type) {
            case 'ldap':
                return $this->redirectToRoute('app_portalsettings_authldap', [
                    'portalId' => $portal->getId(),
                ]);
            case 'shib':
                return $this->redirectToRoute('app_portalsettings_authshibboleth', [
                    'portalId' => $portal->getId(),
                ]);
            case 'guest':
                return $this->redirectToRoute('app_portalsettings_authguest', [
                    'portalId' => $portal->getId(),
                ]);
            default:
            case 'commsy':
                return $this->redirectToRoute('app_portalsettings_authlocal', [
                    'portalId' => $portal->getId(),
                ]);
        }
    }
}
