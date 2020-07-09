<?php

namespace App\Controller;

use App\Entity\Terms;
use App\Form\Type\Room\DeleteType;
use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Room;
use App\Form\DataTransformer\ExtensionSettingsTransformer;
use App\Form\Type\GeneralSettingsType;
use App\Form\Type\ModerationSettingsType;
use App\Form\Type\AdditionalSettingsType;
use App\Form\Type\AppearanceSettingsType;
use App\Form\Type\ExtensionSettingsType;
use App\Form\Type\InvitationsSettingsType;
use App\Utils\RoomService;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SettingsController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SettingsController extends Controller
{
    /**
    * @Route("/room/{roomId}/settings/general")
    * @Template
    * @Security("is_granted('MODERATOR')")
    */
    public function generalAction($roomId, Request $request, RoomService $roomService)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.general_settings');
        $roomData = $transformer->transform($roomItem);

        $roomCategoriesService = $this->get('commsy.roomcategories_service');
        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($legacyEnvironment->getCurrentPortalId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }
        foreach ($roomCategoriesService->getRoomCategoriesLinkedToContext($roomId) as $roomCategory) {
            $roomData['categories'][] = $roomCategory->getCategoryId();
        }

        $form = $this->createForm(GeneralSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'roomCategories' => $roomCategories,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            if (!$roomItem->isGroupRoom()) {
                $roomItem->save();
            }
            else {
                $roomItem->save(false);
            }

            $formData = $form->getData();

            if (isset($formData['categories'])) {
                $roomCategoriesService->setRoomCategoriesLinkedToContext($roomItem->getItemId(), $formData['categories']);
            }

            return $this->redirectToRoute('app_settings_general', ["roomId" => $roomId]);
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/settings/moderation")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function moderationAction($roomId, Request $request, RoomService $roomService)
    {
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.moderation_settings');
        $roomData = $transformer->transform($roomItem);

        $form = $this->createForm(ModerationSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'emailTextTitles' => $roomData['email_configuration']['email_text_titles'],
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            $roomItem->save();
        }

        return array(
            'form' => $form->createView()
        );

    }

    /**
     * @Route("/room/{roomId}/settings/additional")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function additionalAction($roomId, Request $request, RoomService $roomService)
    {
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

        $transformer = $this->get('commsy_legacy.transformer.additional_settings');
        $roomData = $transformer->transform($roomItem);

        if ($selectedTerms = $request->get('terms')) {
            $termsRepository = $this->getDoctrine()->getRepository(Terms::class);
            $currentTerms = $termsRepository->findOneById($selectedTerms);

            $roomData['terms']['agb_text_de'] = $currentTerms->getContentDe();
            $roomData['terms']['agb_text_en'] = $currentTerms->getContentEn();
        }

        $form = $this->createForm(AdditionalSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'newStatus' => $roomData['tasks']['additional_status'],
            'portalTerms' => $portalTerms,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            $roomItem->save();
        }

        $portalItem = $roomItem->getContextItem();

        return array(
            'form' => $form->createView(),
            'deletesRoomIfUnused' => $portalItem->isActivatedDeletingUnusedRooms(),
            'daysUnusedBeforeRoomDeletion' => $portalItem->getDaysUnusedBeforeDeletingRooms(),
        );
    }

    /**
     * @Route("/room/{roomId}/settings/appearance")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function appearanceAction($roomId, Request $request, RoomService $roomService)
    {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.appearance_settings');
        $roomData = $transformer->transform($roomItem);

        // is theme pre-defined in config?
        $preDefinedTheme = $this->container->getParameter('liip_theme_pre_configuration.active_theme');

        //if theme is pre-decined, do not include it in the form
        // get the configured LiipThemeBundle themes

        $themeArray = (!empty($preDefinedTheme)) ? null : $this->container->getParameter('liip_theme.themes');
        $form = $this->createForm(AppearanceSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'themes' => $themeArray,
            'uploadUrl' => $this->generateUrl('app_upload_upload', array(
                'roomId' => $roomId,
            )),
            'themeBackgroundPlaceholder' => $this->generateUrl('getThemeBackground', array(
                'theme' => 'THEME'
            )),
        ));

        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            // TODO: should this be used for normal file uploads (materials etc.) while bg images are saved into specific theme subfolders?
            // TODO: add constraintGroup so that 'room_image' is mandatory when 'custom_image' is selected (or load previous custom image, if present)

            $room_image_data = $form['room_image']->getData();

            if($room_image_data['choice'] == 'custom_image') {
                if(!is_null($room_image_data['room_image_data'])){
                    $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                    if(!is_dir($saveDir)){
                        mkdir($saveDir, 0777, true);
                    }
                    $file = $room_image_data['room_image_upload'];
                    $fileName = "";
                    // case 1: file was send as "input file" via "room_image_upload" field (legacy case; does not occur with current client configuration)
                    if(!is_null($file)){
                        $extension = $file->guessExtension();
                        if(!$extension) {
                            $extension = "bin";
                        }
                        $fileName = "cid" . $roomId . "_bgimage_" . $file->getClientOriginalName();
                        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                        $file->move($saveDir, $fileName);
                    }
                    // case 2: file was send as base64 string via hidden "room_image_data" text field
                    else{
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
            }
            else{
                $roomItem->setBGImageFilename('');
            }

            $room_logo_data = $form['room_logo']->getData();

            if(isset($room_logo_data['activate']) && !empty($room_logo_data['activate']) && $room_logo_data['activate'] == true) {
                if(!is_null($room_logo_data['room_logo_data'])){
                    $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                    if(!is_dir($saveDir)){
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
            }
            else {
                $roomItem->setLogoFilename('');
            }

            $roomItem->save();

            return $this->redirectToRoute('app_settings_appearance', ["roomId" => $roomId]);
        }

        $backgroundImageCustom = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'custom'));
        $backgroundImageTheme = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'theme'));
        $logoImage = $this->generateUrl("getLogo", array('roomId' => $roomId));

        return array(
            'form' => $form->createView(),
            'bgImageFilepathCustom' => $backgroundImageCustom,
            'bgImageFilepathTheme' => $backgroundImageTheme,
            'logoImageFilepath' => $logoImage,
        );
    }
    
    /**
     * @Route("/room/{roomId}/settings/extensions")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function extensionsAction($roomId, Request $request, RoomService $roomService, ExtensionSettingsTransformer $extensionSettingsTransformer)
    {
        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $roomData = $extensionSettingsTransformer->transform($roomItem);

        $form = $this->createForm(ExtensionSettingsType::class, $roomData, [
            'roomId' => $roomId,
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $formData['bilateral'] = $form->get('bilateral')->getViewData();
            $extensionSettingsTransformer->applyTransformation($roomItem, $formData);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/delete/")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function deleteAction(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment
    ) {
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $relatedGroupRooms = [];
        if ($roomItem instanceof \cs_project_item) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $form = $this->createForm(DeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(DeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

if ($form->get('delete')->isClicked()) {
                $roomItem->delete();
                $roomItem->save();


            // redirect back to portal
            $portal = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();
            $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

                return $this->redirect($url);
            }else{
                $form->clearErrors(true);
            }
        }

        $lockForm->handleRequest($request);
        if ($lockForm->isSubmitted() && $form->isValid()) {
            if ($lockForm->get('lock')->isClicked()) {
                $roomItem->reject();
                $roomItem->save();

                // redirect back to portal
                $portal = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();
                $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

                return $this->redirect($url);
            }else{
                $lockForm->clearErrors(true);
            }
        }

        if ($lockForm->get('lock')->isClicked()) {
            $form = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('delete', [], 'profile')
            ]);
        }elseif($form->get('delete')->isClicked()){
            $lockForm = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('lock', [], 'profile')
            ]);
        }

        return [
            'form' => $form->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/invitations")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function invitationsAction($roomId, Request $request, RoomService $roomService, RouterInterface $router)
    {
        $invitationsService = $this->get('commsy.invitations_service');
        $translator = $this->get('translator');

        // get room from RoomService
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $authSourceManager = $legacyEnvironment->getAuthSourceManager();
        $authSourceManager->setContextLimit($legacyEnvironment->getCurrentPortalId());
        $authSourceManager->select();
        $authSourceArray = $authSourceManager->get()->to_array();

        $authSourceItem = null;
        foreach ($authSourceArray as $tempAuthSourceItem) {
            if ($tempAuthSourceItem->isCommSyDefault()) {
                if ($tempAuthSourceItem->allowAddAccountInvitation()) {
                    $authSourceItem = $tempAuthSourceItem;
                }
            }
        }

        $user = $legacyEnvironment->getCurrentUserItem();

        $invitees = array();
        foreach ($invitationsService->getInvitedEmailAdressesByContextId($authSourceItem, $roomId) as $tempInvitee) {
            $invitees[$tempInvitee] = $tempInvitee;
        }

        $form = $this->createForm(InvitationsSettingsType::class, array(), array(
            'roomId' => $roomId,
            'invitees' => $invitees,
        ));

        $form->handleRequest($request);

        $data = $form->getData();
        if (isset($data['email'])) {
            if ($invitationsService->existsInvitationForEmailAddress($authSourceItem, $data['email'])) {
                $form->get('email')->addError(new FormError($translator->trans('An invitation for this email-address already exists in this portal', array())));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // send invitation email
            if (isset($data['email'])) {
                $invitationCode = $invitationsService->generateInvitationCode($authSourceItem, $roomId, $data['email']);

                $invitationLink = $request->getSchemeAndHttpHost();
                $invitationLink .= '?cid=' . $portal->getItemId() . '&mod=home&fct=index&cs_modus=portalmember';
                $invitationLink .= '&invitation_auth_source=' . $authSourceItem->getItemId();
                $invitationLink .= '&invitation_auth_code=' . $invitationCode;

                $mailer = $this->get('mailer');
                $fromAddress = $this->getParameter('commsy.email.from');
                $fromSender = $legacyEnvironment->getCurrentContextItem()->getContextItem()->getTitle();

                $subject = $translator->trans('invitation subject %portal%', array('%portal%' => $portal->getTitle()));
                $body = $translator->trans('invitation body %portal% %link% %sender%', [
                    '%room%' => $roomItem->getTitle(),
                    '%portal%' => $portal->getTitle(),
                    '%link%' => $invitationLink,
                    '%roomLink%' => $router->generate('app_room_home', [
                        'roomId' => $roomItem->getItemID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    '%sender%' => $user->getFullName()
                ]);
                $mailMessage = (new \Swift_Message())
                    ->setSubject($subject)
                    ->setBody($body, 'text/plain')
                    ->setFrom([$fromAddress => $fromSender])
                    ->setTo([$data['email']]);
                $mailer->send($mailMessage);
            }

            foreach ($data['remove_invitees'] as $removeInvitee) {
                $invitationsService->removeInvitedEmailAdresses($authSourceItem, $removeInvitee);
            }

            return $this->redirectToRoute('app_settings_invitations', ["roomId" => $roomId]);
        }

        return array(
            'form' => $form->createView(),
        );
    }
}
