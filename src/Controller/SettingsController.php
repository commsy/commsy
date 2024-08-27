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

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Event\RoomSettingsChangedEvent;
use App\Form\DataTransformer\AdditionalSettingsTransformer;
use App\Form\DataTransformer\AppearanceSettingsTransformer;
use App\Form\DataTransformer\ExtensionSettingsTransformer;
use App\Form\DataTransformer\GeneralSettingsTransformer;
use App\Form\DataTransformer\ModerationSettingsTransformer;
use App\Form\Type\AdditionalSettingsType;
use App\Form\Type\AppearanceSettingsType;
use App\Form\Type\ExtensionSettingsType;
use App\Form\Type\GeneralSettingsType;
use App\Form\Type\InvitationsSettingsType;
use App\Form\Type\ModerationSettingsType;
use App\Form\Type\Room\DeleteType;
use App\Form\Type\Room\LockType;
use App\Form\Type\Room\UserRoomDeleteType;
use App\Mail\Factories\InvitationMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use App\Repository\TermsRepository;
use App\Room\RoomStatus;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\RoomService;
use App\Utils\UserroomService;
use cs_grouproom_item;
use cs_project_item;
use cs_room_item;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SettingsController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class SettingsController extends AbstractController
{
    #[Route(path: '/room/{roomId}/settings/general')]
    #[IsGranted('MODERATOR')]
    public function general(
        Request $request,
        RoomCategoriesService $roomCategoriesService,
        RoomService $roomService,
        GeneralSettingsTransformer $transformer,
        LegacyEnvironment $environment,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        // get room from RoomService
        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $roomData = $transformer->transform($roomItem);
        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($legacyEnvironment->getCurrentPortalId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }
        foreach ($roomCategoriesService->getRoomCategoriesLinkedToContext($roomId) as $roomCategory) {
            $roomData['categories'][] = $roomCategory->getCategoryId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;

        $form = $this->createForm(GeneralSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'roomCategories' => $roomCategories,
            'linkRoomCategoriesMandatory' => $linkRoomCategoriesMandatory,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            if (!$roomItem->isGroupRoom()) {
                $roomItem->save();
            } else {
                /** @var cs_grouproom_item $roomItem */
                $roomItem->save(false);
            }

            $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
            $eventDispatcher->dispatch($roomSettingsChangedEvent);

            return $this->redirectToRoute('app_settings_general', [
                'roomId' => $roomId,
            ]);
        }

        return $this->render('settings/general.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/moderation')]
    #[IsGranted('MODERATOR')]
    public function moderation(
        Request $request,
        RoomService $roomService,
        ModerationSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ): Response {
        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }
        $roomData = $transformer->transform($roomItem);

        $form = $this->createForm(ModerationSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'emailTextTitles' => $roomData['email_configuration']['email_text_titles'],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());
            $roomItem->save();

            $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
            $eventDispatcher->dispatch($roomSettingsChangedEvent);
        }

        return $this->render('settings/moderation.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/additional')]
    #[IsGranted('MODERATOR')]
    public function additional(
        Request $request,
        RoomService $roomService,
        AdditionalSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $legacyEnvironment,
        TermsRepository $termsRepository,
        int $roomId
    ): Response {
        $portalItem = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();
        $portalId = $portalItem->getItemId();

        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $availableTerms = $termsRepository->findByContextId($portalId);
        $portalTerms = ['' => false];
        foreach ($availableTerms as $availableTerm) {
            $portalTerms[$availableTerm->getTitle()] = $availableTerm->getId();
        }

        $roomData = $transformer->transform($roomItem);

        if ($selectedTerms = $request->get('terms')) {
            $currentTerms = $termsRepository->findOneById($selectedTerms);

            $roomData['terms']['agb_text_de'] = $currentTerms->getContentDe();
            $roomData['terms']['agb_text_en'] = $currentTerms->getContentEn();
        }

        $form = $this->createForm(AdditionalSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'isUserroom' => $roomItem->isUserroom(),
            'newStatus' => $roomData['tasks']['additional_status'],
            'portalTerms' => $portalTerms,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());
            $roomItem->save();

            $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
            $eventDispatcher->dispatch($roomSettingsChangedEvent);
        }

        return $this->render('settings/additional.html.twig', [
            'form' => $form,
            'deletesRoomIfUnused' => $portalItem->isActivatedDeletingUnusedRooms(),
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/appearance')]
    #[IsGranted('MODERATOR')]
    public function appearance(
        Request $request,
        RoomService $roomService,
        AppearanceSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        ThemeRepositoryInterface $themeRepository,
        int $roomId
    ): Response {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $roomData = $transformer->transform($roomItem);

        /**
         * If a specific theme is forced, we do not show any selection at all.
         */

        $forceTheme = $this->getParameter('commsy.force_theme');
        $themeArray = !empty($forceTheme) ? null : $themeRepository->findAll();

        $form = $this->createForm(AppearanceSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'themes' => $themeArray,
            'uploadUrl' => $this->generateUrl('app_upload_upload', [
                'roomId' => $roomId,
            ]),
            'themeBackgroundPlaceholder' => $this->generateUrl('getThemeBackground', [
                'theme' => 'THEME',
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            // TODO: should this be used for normal file uploads (materials etc.) while bg images are saved into specific theme subfolders?
            // TODO: add constraintGroup so that 'room_image' is mandatory when 'custom_image' is selected (or load previous custom image, if present)

            $room_image_data = $form['room_image']->getData();

            if ('custom_image' == $room_image_data['choice']) {
                if (!is_null($room_image_data['room_image_data'])) {
                    $saveDir = $this->getParameter('files_directory').'/'.$roomService->getRoomFileDirectory($roomId);
                    if (!is_dir($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $file = $room_image_data['room_image_upload'];
                    $fileName = '';
                    // case 1: file was send as "input file" via "room_image_upload" field (legacy case; does not occur with current client configuration)
                    if (!is_null($file)) {
                        $extension = $file->guessExtension();
                        if (!$extension) {
                            $extension = 'bin';
                        }
                        $fileName = 'cid'.$roomId.'_bgimage_'.$file->getClientOriginalName();
                        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                        $file->move($saveDir, $fileName);
                    } // case 2: file was send as base64 string via hidden "room_image_data" text field
                    else {
                        $data = $room_image_data['room_image_data'];
                        [$fileName, $type, $date] = explode(';', (string) $data);
                        [, $data] = explode(',', (string) $data);
                        [, $extension] = explode('/', $type);
                        $data = base64_decode($data);
                        $fileName = 'cid'.$roomId.'_bgimage_'.$fileName;
                        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                        $absoluteFilepath = $saveDir.'/'.$fileName;
                        file_put_contents($absoluteFilepath, $data);
                    }
                    $roomItem->setBGImageFilename($fileName);
                }
            } else {
                $roomItem->setBGImageFilename('');
            }

            $room_logo_data = $form['room_logo']->getData();

            if (isset($room_logo_data['activate']) && !empty($room_logo_data['activate']) && true == $room_logo_data['activate']) {
                if (!is_null($room_logo_data['room_logo_data'])) {
                    $saveDir = $this->getParameter('files_directory').'/'.$roomService->getRoomFileDirectory($roomId);
                    if (!is_dir($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $fileName = '';
                    $data = $room_logo_data['room_logo_data'];
                    [$fileName, $type, $date] = explode(';', (string) $data);
                    $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                    [, $data] = explode(',', (string) $data);
                    [, $extension] = explode('/', $type);
                    $data = base64_decode($data);
                    $fileName = 'cid'.$roomId.'_logo_'.$fileName;
                    $absoluteFilepath = $saveDir.'/'.$fileName;
                    file_put_contents($absoluteFilepath, $data);
                    $roomItem->setLogoFilename($fileName);
                }
            } else {
                $roomItem->setLogoFilename('');
            }

            $roomItem->save();

            $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
            $eventDispatcher->dispatch($roomSettingsChangedEvent);

            return $this->redirectToRoute('app_settings_appearance', [
                'roomId' => $roomId,
            ]);
        }

        $backgroundImageCustom = $this->generateUrl('getBackground', [
            'roomId' => $roomId,
            'imageType' => 'custom',
        ]);
        $backgroundImageTheme = $this->generateUrl('getBackground', [
            'roomId' => $roomId,
            'imageType' => 'theme',
        ]);
        $logoImage = $this->generateUrl('getLogo', [
            'roomId' => $roomId,
        ]);

        return $this->render('settings/appearance.html.twig', [
            'form' => $form,
            'bgImageFilepathCustom' => $backgroundImageCustom,
            'bgImageFilepathTheme' => $backgroundImageTheme,
            'logoImageFilepath' => $logoImage,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/extensions')]
    #[IsGranted('MODERATOR')]
    public function extensions(
        Request $request,
        RoomService $roomService,
        ExtensionSettingsTransformer $extensionSettingsTransformer,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ): Response {
        $templates = [];
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }
        $defaultUserroomTemplateIDs = [];
        if ('userroom' == $roomItem->getType()) {
            $projectItem = $roomItem->getLinkedProjectItem();
            $userroomTemplate = $projectItem->getUserRoomTemplateItem();
            $defaultUserroomTemplateIDs = ($userroomTemplate) ? [$userroomTemplate->getItemID()] : [];
            $templates = $roomService->getAvailableTemplates($projectItem->getType());
        } elseif ('project' === $roomItem->getType()) {
            $userroomTemplate = $roomItem->getUserRoomTemplateItem();
            $defaultUserroomTemplateIDs = ($userroomTemplate) ? [$userroomTemplate->getItemID()] : [];
            $templates = $roomService->getAvailableTemplates($roomItem->getType());
        }

        $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
        $msg = $translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
        $templates['*'.$msg] = '-1';

        uasort($templates, fn ($a, $b) => $a <=> $b);

        $roomData = $extensionSettingsTransformer->transform($roomItem);

        $form = $this->createForm(ExtensionSettingsType::class, $roomData, [
            'room' => $roomItem,
            'userroomTemplates' => $templates,
            'preferredUserroomTemplates' => $defaultUserroomTemplateIDs,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('deleteUserRooms')->isClicked()) {
                return $this->redirectToRoute('app_settings_deleteuserrooms', ['roomId' => $roomId]);
            } else {
                $oldRoom = clone $roomItem;
                $formData = $form->getData();

                $roomItem = $extensionSettingsTransformer->applyTransformation($roomItem, $formData);

                if ('project' == $roomItem->getType() and isset($formData['userroom_template'])) {
                    $roomItem->setUserRoomTemplateID($formData['userroom_template']);
                }
                $roomItem->save();

                $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
                $eventDispatcher->dispatch($roomSettingsChangedEvent);
            }
        }

        return $this->render('settings/extensions.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/deleteuserrooms')]
    #[IsGranted('MODERATOR')]
    #[IsGranted('ITEM_DELETE', subject: 'roomId')]
    public function deleteUserRooms(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        UserroomService $userroomService
    ): Response {
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $form = $this->createForm(UserRoomDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userroomService->deleteUserroomsForProjectRoomId($roomId);

            return $this->redirectToRoute('app_settings_extensions', ['roomId' => $roomId]);
        }

        return $this->render('settings/delete_user_rooms.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/delete/')]
    #[IsGranted('MODERATOR')]
    #[IsGranted('ITEM_DELETE', subject: 'roomId')]
    public function delete(
        int $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        PortalRepository $portalRepository
    ): Response {
        $portalItem = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();
        $portalId = $portalItem->getItemId();

        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $relatedGroupRooms = [];
        if ($roomItem instanceof cs_project_item) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(DeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('delete', [], 'profile'),
        ]);

        $lockForm = $this->createForm(LockType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ]);

        $deleteForm->handleRequest($request);
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            if ($deleteForm->get('delete')->isClicked()) {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to all rooms
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $portalId,
                ]);
            }
        }

        $lockForm->handleRequest($request);
        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            if ($lockForm->get('lock')->isClicked()) {
                $portal = $portalRepository->find($portalId);
                $status = $this->isGranted('PORTAL_MODERATOR', $portal) ?
                    RoomStatus::LOCKED_PORTAL_MOD : RoomStatus::LOCKED;

                $roomItem->lock($status);
                $roomItem->save();

                // redirect back to all rooms
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $portalId,
                ]);
            }
        }

        return $this->render('settings/delete.html.twig', [
            'delete_form' => $deleteForm,
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm,
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/invitations')]
    #[IsGranted('MODERATOR')]
    public function invitations(
        Request $request,
        InvitationsService $invitationsService,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $environment,
        PortalRepository $portalRepository,
        InvitationMessageFactory $invitationMessageFactory,
        Mailer $mailer,
        int $roomId
    ): Response {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$roomId);
        }

        $legacyEnvironment = $environment->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();
        $portalId = $portalItem->getItemId();

        /** @var Portal $portal */
        $portal = $portalRepository->find($portalId);

        $authSources = $portal->getAuthSources();

        /** @var AuthSourceLocal $localSource */
        $localAuthSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceLocal)->first();

        $invitees = [];
        foreach ($invitationsService->getInvitedEmailAdressesByContextId($localAuthSource, $roomId) as $tempInvitee) {
            $invitees[$tempInvitee] = $tempInvitee;
        }

        $form = $this->createForm(InvitationsSettingsType::class, [], [
            'roomId' => $roomId,
            'invitees' => $invitees,
        ]);

        $form->handleRequest($request);

        $data = $form->getData();
        if (isset($data['email'])) {
            if ($invitationsService->existsInvitationForEmailAddress($localAuthSource, $data['email'])) {
                $form->get('email')->addError(new FormError($translator->trans('An invitation for this email-address already exists in this portal', [])));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = $form->getClickedButton()->getName();

            if ('send' === $clickedButton) {
                // send invitation email
                if (isset($data['email'])) {
                    $invitationCode = $invitationsService->generateInvitationCode($localAuthSource, $roomId, $data['email']);
                    $invitationMessage = $invitationMessageFactory->createInvitationMessage($portal, $roomItem, $invitationCode);

                    $fromSender = $portalItem->getTitle();
                    $mailer->send($invitationMessage, RecipientFactory::createFromRaw($data['email']), $fromSender);
                }
            } elseif ('delete' === $clickedButton) {
                foreach ($data['remove_invitees'] as $removeInvitee) {
                    $invitationsService->removeInvitedEmailAdresses($localAuthSource, $removeInvitee);
                }
            }

            return $this->redirectToRoute('app_settings_invitations', [
                'roomId' => $roomId,
            ]);
        }

        return $this->render('settings/invitations.html.twig', [
            'form' => $form,
        ]);
    }
}
