<?php

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Entity\Terms;
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
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\RoomService;
use App\Utils\UserroomService;
use cs_room_item;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SettingsController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SettingsController extends AbstractController
{
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @required
     * @param ParameterBagInterface $params
     */
    public function setParameterBag(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @Route("/room/{roomId}/settings/general")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param RoomCategoriesService $roomCategoriesService
     * @param RoomService $roomService
     * @param GeneralSettingsTransformer $transformer
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function generalAction(
        Request $request,
        RoomCategoriesService $roomCategoriesService,
        RoomService $roomService,
        GeneralSettingsTransformer $transformer,
        LegacyEnvironment $environment,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        // get room from RoomService
        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $roomData = $transformer->transform($roomItem);
        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($legacyEnvironment->getCurrentPortalId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }
        foreach ($roomCategoriesService->getRoomCategoriesLinkedToContext($roomId) as $roomCategory) {
            $roomData['categories'][] = $roomCategory->getCategoryId();
        }

        $form = $this->createForm(GeneralSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'roomCategories' => $roomCategories,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            if (!$roomItem->isGroupRoom()) {
                $roomItem->save();
            } else {
                $roomItem->save(false);
            }

            $formData = $form->getData();

            if (isset($formData['categories'])) {
                $roomCategoriesService->setRoomCategoriesLinkedToContext($roomItem->getItemId(), $formData['categories']);
            }

            $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
            $eventDispatcher->dispatch($roomSettingsChangedEvent);

            return $this->redirectToRoute('app_settings_general', [
                "roomId" => $roomId,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/moderation")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param RoomService $roomService
     * @param ModerationSettingsTransformer $transformer
     * @param int $roomId
     * @return array
     */
    public function moderationAction(
        Request $request,
        RoomService $roomService,
        ModerationSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    )
    {
        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
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

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/additional")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param RoomService $roomService
     * @param AdditionalSettingsTransformer $transformer
     * @param int $roomId
     * @return array
     */
    public function additionalAction(
        Request $request,
        RoomService $roomService,
        AdditionalSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    )
    {
        /** @var cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $termsRepository = $this->getDoctrine()->getRepository(Terms::class);
        $availableTerms = $termsRepository->findByContextId($roomItem->getContextId());
        $portalTerms = ['' => false];
        foreach ($availableTerms as $availableTerm) {
            $portalTerms[$availableTerm->getTitle()] = $availableTerm->getId();
        }

        $roomData = $transformer->transform($roomItem);

        if ($selectedTerms = $request->get('terms')) {
            $termsRepository = $this->getDoctrine()->getRepository(Terms::class);
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

        $portalItem = $roomItem->getContextItem();

        return [
            'form' => $form->createView(),
            'deletesRoomIfUnused' => $portalItem->isActivatedDeletingUnusedRooms(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/appearance")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param RoomService $roomService
     * @param AppearanceSettingsTransformer $transformer
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function appearanceAction(
        Request $request,
        RoomService $roomService,
        AppearanceSettingsTransformer $transformer,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    )
    {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $roomData = $transformer->transform($roomItem);

        // is theme pre-defined in config?
        $preDefinedTheme = $this->params->get('liip_theme_pre_configuration.active_theme');

        //if theme is pre-decined, do not include it in the form
        // get the configured LiipThemeBundle themes

        $themeArray = (!empty($preDefinedTheme)) ? null : $this->params->get('liip_theme.themes');
        $form = $this->createForm(AppearanceSettingsType::class, $roomData, [
            'roomId' => $roomId,
            'themes' => $themeArray,
            'uploadUrl' => $this->generateUrl('app_upload_upload', [
                'roomId' => $roomId,
            ]),
            'themeBackgroundPlaceholder' => $this->generateUrl('getThemeBackground', [
                'theme' => 'THEME'
            ]),
        ]);


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldRoom = clone $roomItem;

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            // TODO: should this be used for normal file uploads (materials etc.) while bg images are saved into specific theme subfolders?
            // TODO: add constraintGroup so that 'room_image' is mandatory when 'custom_image' is selected (or load previous custom image, if present)

            $room_image_data = $form['room_image']->getData();

            if ($room_image_data['choice'] == 'custom_image') {
                if (!is_null($room_image_data['room_image_data'])) {
                    $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                    if (!is_dir($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $file = $room_image_data['room_image_upload'];
                    $fileName = "";
                    // case 1: file was send as "input file" via "room_image_upload" field (legacy case; does not occur with current client configuration)
                    if (!is_null($file)) {
                        $extension = $file->guessExtension();
                        if (!$extension) {
                            $extension = "bin";
                        }
                        $fileName = "cid" . $roomId . "_bgimage_" . $file->getClientOriginalName();
                        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                        $file->move($saveDir, $fileName);
                    } // case 2: file was send as base64 string via hidden "room_image_data" text field
                    else {
                        $data = $room_image_data['room_image_data'];
                        list($fileName, $type, $date) = explode(";", $data);
                        list(, $data) = explode(",", $data);
                        list(, $extension) = explode("/", $type);
                        $data = base64_decode($data);
                        $fileName = "cid" . $roomId . "_bgimage_" . $fileName;
                        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                        $absoluteFilepath = $saveDir . "/" . $fileName;
                        file_put_contents($absoluteFilepath, $data);
                    }
                    $roomItem->setBGImageFilename($fileName);
                }
            } else {
                $roomItem->setBGImageFilename('');
            }

            $room_logo_data = $form['room_logo']->getData();

            if (isset($room_logo_data['activate']) && !empty($room_logo_data['activate']) && $room_logo_data['activate'] == true) {
                if (!is_null($room_logo_data['room_logo_data'])) {
                    $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                    if (!is_dir($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $fileName = "";
                    $data = $room_logo_data['room_logo_data'];
                    list($fileName, $type, $date) = explode(";", $data);
                    $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                    list(, $data) = explode(",", $data);
                    list(, $extension) = explode("/", $type);
                    $data = base64_decode($data);
                    $fileName = "cid" . $roomId . "_logo_" . $fileName;
                    $absoluteFilepath = $saveDir . "/" . $fileName;
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

        return [
            'form' => $form->createView(),
            'bgImageFilepathCustom' => $backgroundImageCustom,
            'bgImageFilepathTheme' => $backgroundImageTheme,
            'logoImageFilepath' => $logoImage,
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/extensions")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param RoomService $roomService
     * @param ExtensionSettingsTransformer $extensionSettingsTransformer
     * @param int $roomId
     * @return array
     */
    public function extensionsAction(
        Request $request,
        RoomService $roomService,
        ExtensionSettingsTransformer $extensionSettingsTransformer,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    )
    {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }
        $defaultUserroomTemplateIDs = [];
        if ($roomItem->getType() == 'userroom') {
            $projectItem = $roomItem->getLinkedProjectItem();
            $userroomTemplate = $projectItem->getUserRoomTemplateItem();
            $defaultUserroomTemplateIDs = ($userroomTemplate) ? [$userroomTemplate->getItemID()] : [];
            $templates = $roomService->getAvailableTemplates($projectItem->getType());
        } else if ($roomItem->getType() === 'project') {
            $userroomTemplate = $roomItem->getUserRoomTemplateItem();
            $defaultUserroomTemplateIDs = ($userroomTemplate) ? [$userroomTemplate->getItemID()] : [];
            $templates = $roomService->getAvailableTemplates($roomItem->getType());
        }

        $translator = $legacyEnvironment->getEnvironment()->getTranslationObject();
        $msg = $translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
        $templates['*' . $msg] = '-1';

        uasort($templates, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        $roomData = $extensionSettingsTransformer->transform($roomItem);

        $form = $this->createForm(ExtensionSettingsType::class, $roomData, [
            'room' => $roomItem,
            'userroomTemplates' => $templates,
            'preferredUserroomTemplates' => $defaultUserroomTemplateIDs,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('deleteUserRooms')->isClicked()) {
                return $this->redirectToRoute('app_settings_deleteuserrooms', ["roomId" => $roomId]);
            } else {
                $oldRoom = clone $roomItem;
                $formData = $form->getData();

                $roomItem = $extensionSettingsTransformer->applyTransformation($roomItem, $formData);

                if ($roomItem->getType() == 'project' and isset($formData['userroom_template'])) {
                    $roomItem->setUserRoomTemplateID($formData['userroom_template']);
                }
                $roomItem->save();

                $roomSettingsChangedEvent = new RoomSettingsChangedEvent($oldRoom, $roomItem);
                $eventDispatcher->dispatch($roomSettingsChangedEvent);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/deleteuserrooms")
     * @Template
     * @Security("is_granted('MODERATOR') and is_granted('ITEM_DELETE', roomId)")
     */
    public function deleteUserRoomsAction(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        UserroomService $userroomService
    )
    {
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $form = $this->createForm(UserRoomDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userroomService->deleteUserroomsForProjectRoomId($roomId);
            return $this->redirectToRoute('app_settings_extensions', ["roomId" => $roomId]);
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/delete/")
     * @Template
     * @Security("is_granted('MODERATOR') and is_granted('ITEM_DELETE', roomId)")
     * @param Request $request
     * @param RoomService $roomService
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function deleteAction(
        int $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment
    ) {
        $portal = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();

        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $relatedGroupRooms = [];
        if ($roomItem instanceof \cs_project_item) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(DeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(LockType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $deleteForm->handleRequest($request);
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            if ($deleteForm->get('delete')->isClicked()) {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to all rooms
                return $this->redirectToRoute('app_room_listall', [
                    "roomId" => $portal->getItemId()
                ]);
            }
        }

        $lockForm->handleRequest($request);
        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            if ($lockForm->get('lock')->isClicked()) {
                $roomItem->lock();
                $roomItem->save();

                // redirect back to all rooms
                return $this->redirectToRoute('app_room_listall', [
                    "roomId" => $portal->getItemId()
                ]);
            }
        }

        return [
            'delete_form' => $deleteForm->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/invitations")
     * @Template
     * @Security("is_granted('MODERATOR')")
     * @param Request $request
     * @param InvitationsService $invitationsService
     * @param RoomService $roomService
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $environment
     * @param PortalRepository $portalRepository
     * @param Mailer $mailer
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function invitationsAction(
        Request $request,
        InvitationsService $invitationsService,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $environment,
        PortalRepository $portalRepository,
        Mailer $mailer,
        int $roomId
    ) {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $legacyEnvironment = $environment->getEnvironment();

        /** @var Portal $portal */
        $portal = $portalRepository->find($roomItem->getContextID());

        $authSources = $portal->getAuthSources();

        /** @var AuthSourceLocal $localSource */
        $localAuthSource = $authSources->filter(function (AuthSource $authSource) {
            return $authSource instanceof AuthSourceLocal;
        })->first();

        $user = $legacyEnvironment->getCurrentUserItem();

        $invitees = array();
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
                $form->get('email')->addError(new FormError($translator->trans('An invitation for this email-address already exists in this portal', array())));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = $form->getClickedButton()->getName();

            if ($clickedButton === 'send') {
                // send invitation email
                if (isset($data['email'])) {
                    $invitationCode = $invitationsService->generateInvitationCode($localAuthSource, $roomId, $data['email']);
                    $invitationLink = $this->generateUrl('app_account_signup', [
                        'id' => $portal->getId(),
                        'token' => $invitationCode,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $fromAddress = $this->getParameter('commsy.email.from');
                    $fromSender = $legacyEnvironment->getCurrentContextItem()->getContextItem()->getTitle();

                    $subject = $translator->trans('invitation subject %portal%', [
                        '%portal%' => $portal->getTitle(),
                    ]);
                    $body = $translator->trans('invitation body %portal% %link% %sender%', [
                        '%room%' => $roomItem->getTitle(),
                        '%portal%' => $portal->getTitle(),
                        '%link%' => $invitationLink,
                        '%roomLink%' => $this->generateUrl('app_room_home', [
                            'roomId' => $roomItem->getItemID(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL)." ",
                        '%sender%' => $user->getFullName(),
                    ]);

                    $mailer->sendRaw(
                        $subject,
                        $body,
                        RecipientFactory::createFromRaw($data['email']),
                        $fromSender
                    );
                }
            } else if ($clickedButton === 'delete') {
                foreach ($data['remove_invitees'] as $removeInvitee) {
                    $invitationsService->removeInvitedEmailAdresses($localAuthSource, $removeInvitee);
                }
            }

            return $this->redirectToRoute('app_settings_invitations', [
                "roomId" => $roomId,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
