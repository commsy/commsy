<?php

namespace App\Controller;

use App\Entity\Calendars;
use App\Form\Type\Profile\DeleteAccountType;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

use App\Utils\RoomService;
use App\Services\LegacyEnvironment;


use App\Entity\User;
use App\Form\Type\Profile\RoomProfileGeneralType;
use App\Form\Type\Profile\RoomProfileAddressType;
use App\Form\Type\Profile\RoomProfileContactType;
use App\Form\Type\Profile\RoomProfileNotificationsType;
use App\Form\Type\Profile\DeleteType;
use App\Form\Type\Profile\ProfileAccountType;
use App\Form\Type\Profile\ProfileChangePasswordType;
use App\Form\Type\Profile\ProfileMergeAccountsType;
use App\Form\Type\Profile\ProfileNewsletterType;
use App\Form\Type\Profile\ProfileCalendarsType;
use App\Form\Type\Profile\ProfileAdditionalType;
use App\Form\Type\Profile\ProfilePersonalInformationType;

/**
 * Class ProfileController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ProfileController extends Controller
{
    /**
     * @Route("/room/{roomId}/user/{itemId}/general")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function generalAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $discManager = $legacyEnvironment->getDiscManager();
        $userService = $this->get('commsy_legacy.user_service');
        $roomService = $this->get('commsy_legacy.room_service');
        $userItem = $userService->getUser($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }

        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userData = $userTransformer->transform($userItem);
        $userData['useProfileImage'] = $userItem->getPicture() != "";

        $form = $this->createForm(RoomProfileGeneralType::class, $userData, array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('app_upload_upload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // use custom profile picture if given
            if($formData['useProfileImage']) {
                if($formData['image_data']) {
                    $saveDir = implode("/", array($this->getParameter('files_directory'), $roomService->getRoomFileDirectory($userItem->getContextID())));
                    if(!file_exists($saveDir)){
                        mkdir($saveDir, 0777, true);
                    }
                    $data = $formData['image_data'];
                    list($fileName, $type, $data) = explode(";", $data);
                    list(, $data) = explode(",", $data);
                    list(, $extension) = explode("/", $type);
                    $data = base64_decode($data);
                    $fileName = implode("_", array('cid'.$userItem->getContextID(), $userItem->getUserID(), $fileName));
                    $absoluteFilepath = implode("/", array($saveDir, $fileName));
                    file_put_contents($absoluteFilepath, $data);
                    $userItem->setPicture($fileName);

                    $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
                    $userItem->save();
                }
            }
            // use user initials else
            else {
                if($discManager->existsFile($userItem->getPicture())) {
                    $discManager->unlinkFile($userItem->getPicture());
                }
                $userItem->setPicture("");
                $userItem->save();
            }

            if ($formData['imageChangeInAllContexts']) {
                $userList = $userItem->getRelatedUserList();
                $tempUserItem = $userList->getFirst();
                $discService = $this->get('commsy_legacy.disc_service');
                while ($tempUserItem) {
                    if ($tempUserItem->getItemId() == $userItem->getItemId()) {
                        $tempUserItem = $userList->getNext();
                        continue;
                    }
                    if($formData['useProfileImage']) {
                        $tempFilename = $discService->copyImageFromRoomToRoom($userItem->getPicture(), $tempUserItem->getContextId());
                        if ($tempFilename) {
                            $tempUserItem->setPicture($tempFilename);
                        }
                    }
                    else {
                        if($discManager->existsFile($tempUserItem->getPicture())) {
                            $discManager->unlinkFile($tempUserItem->getPicture());
                        }
                        $tempUserItem->setPicture("");

                    }
                    $tempUserItem->save();
                    $tempUserItem = $userList->getNext();
                }
            }

            return $this->redirectToRoute('app_profile_general', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        return array(
            'roomId' => $roomId,
            'roomTitle' => $roomItem->getTitle(),
            'user' => $userItem,
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/address")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function addressAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(RoomProfileAddressType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $userItem = $userTransformer->applyTransformation($userItem, $formData);
            $userItem->save();

            $userList = $userItem->getRelatedUserList();
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['titleChangeInAllContexts']) {
                    $tempUserItem->setTitle($formData['title']);
                }
                if ($formData['streetChangeInAllContexts']) {
                    $tempUserItem->setStreet($formData['street']);
                }
                if ($formData['zipcodeChangeInAllContexts']) {
                    $tempUserItem->setZipcode($formData['zipcode']);
                }
                if ($formData['cityChangeInAllContexts']) {
                    $tempUserItem->setCity($formData['city']);
                }
                if ($formData['roomChangeInAllContexts']) {
                    $tempUserItem->setRoom($formData['room']);
                }
                if ($formData['organisationChangeInAllContexts']) {
                    $tempUserItem->setOrganisation($formData['organisation']);
                }
                if ($formData['positionChangeInAllContexts']) {
                    $tempUserItem->setPosition($formData['position']);
                }
                $tempUserItem->save();
                $tempUserItem = $userList->getNext();
            }

            return $this->redirectToRoute('app_profile_address', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/contact")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function contactAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(RoomProfileContactType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $userItem = $userTransformer->applyTransformation($userItem, $formData);
            $userItem->save();
            $userList = $userItem->getRelatedUserList();
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['emailChangeInAllContexts']) {
                    $tempUserItem->setEmail($formData['emailRoom']);
                }
                if ($formData['hideEmailInAllContexts']) {
                    if ($formData['hideEmailInThisRoom']) {
                        $tempUserItem->setEmailNotVisible();
                    } else {
                        $tempUserItem->setEmailVisible();
                    }
                }
                if ($formData['phoneChangeInAllContexts']) {
                    $tempUserItem->setTelephone($formData['phone']);
                }
                if ($formData['mobileChangeInAllContexts']) {
                    $tempUserItem->setCellularphone($formData['mobile']);
                }
                if ($formData['skypeChangeInAllContexts']) {
                    $tempUserItem->setSkype($formData['skype']);
                }
                if ($formData['homepageChangeInAllContexts']) {
                    $tempUserItem->setHomepage($formData['homepage']);
                }
                if ($formData['descriptionChangeInAllContexts']) {
                    $tempUserItem->setDescription($formData['description']);
                }
                $tempUserItem->save();
                $tempUserItem = $userList->getNext();
            }

            return $this->redirectToRoute('app_profile_contact', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/notifications")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function notificationsAction($roomId, $itemId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = [];

        $userData['mail_account'] = $userItem->getAccountWantMail() === 'yes' ? true : false;
        $userData['mail_room'] = $userItem->getOpenRoomWantMail() === 'yes' ? true : false;
        $userData['mail_item_deleted'] = $userItem->getDeleteEntryWantMail();

        $form = $this->createForm(RoomProfileNotificationsType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $userItem->setAccountWantMail($formData['mail_account'] ? 'yes' : 'no');
            $userItem->setOpenRoomWantMail($formData['mail_room'] ? 'yes' : 'no');
            $userItem->setDeleteEntryWantMail($formData['mail_item_deleted']);

            $userItem->save();
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/personal")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function personalAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $portalUser = $userItem->getRelatedPortalUserItem();

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfilePersonalInformationType::class, $userData, array(
            'itemId' => $itemId,
            'portalUser' => $portalUser,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            return $this->redirectToRoute('app_profile_personal', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
            'hasToChangeEmail' => $portalUser->hasToChangeEmail(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/account")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function accountAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfileAccountType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            return $this->redirectToRoute('app_profile_account', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/mergeaccounts")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function mergeAccountsAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $userService = $this->get('commsy_legacy.user_service');

        // account administration page => set language to user preferences
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userItem = $userService->getUser($itemId);

        // external auth sources
        $current_portal_item = $legacyEnvironment->getCurrentPortalItem();
        if(!isset($current_portal_item)) $current_portal_item = $legacyEnvironment->getServerItem();
        $auth_sources = [];
        $auth_source_list = $current_portal_item->getAuthSourceListEnabled();
        if(isset($auth_source_list) && !$auth_source_list->isEmpty()) {
            $auth_source_item = $auth_source_list->getFirst();

            while($auth_source_item) {
                $auth_sources[$auth_source_item->getTitle()] = $auth_source_item->getItemID();
                $auth_source_item = $auth_source_list->getNext();
            }
        }

        // TODO: default auth source!

        // only show auth source list if more than one auth source is configured
        $show_auth_source = count($auth_sources) > 1;
        $form = $this->createForm(ProfileMergeAccountsType::class, [], array(
            'itemId' => $itemId,
            'auth_source_array' => $auth_sources,
            'show_auth_source' => $show_auth_source,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $authentication = $legacyEnvironment->getAuthenticationObject();

            $formData = $form->getData();

            $currentUser = $legacyEnvironment->getCurrentUserItem();
            if ( strtolower($currentUser->getUserID()) == strtolower($formData['combineUserId']) &&
                isset($formData['auth_source']) &&
                (empty($formData['auth_source']) || $currentUser->getAuthSource() == $formData['auth_source'] ) )
            {
                $form->get('combineUserId')->addError(new FormError('Invalid user'));
            }
            else
            {
                $user_manager = $legacyEnvironment->getUserManager();
                $user_manager->setUserIDLimitBinary($formData['combineUserId']);
                $user_manager->setContextLimit($current_portal_item->getItemId());

                $user_manager->select();
                $user = $user_manager->get();
                $first_user = $user->getFirst();

                if(!empty($first_user)){
                    if(!isset($formData['auth_source']) || empty($formData['auth_source'])) {
                        $authManager = $authentication->getAuthManager($currentUser->getAuthSource());
                    } else {
                        $authManager = $authentication->getAuthManager($formData['auth_source']);
                    }
                    if ( !$authManager->checkAccount($formData['combineUserId'], $formData['combinePassword']) )
                    {
                        $form->get('combineUserId')->addError(new FormError('Authentication error'));
                    }
                } else {
                    $form->get('combineUserId')->addError(new FormError('User not found'));
                }
            }

            if ( isset($formData['auth_source']) )
            {
                $authSourceOld = $formData['auth_source'];
            }
            else
            {
                $authSourceOld = $legacyEnvironment->getCurrentPortalItem()->getAuthDefault();
            }
            if($form->isSubmitted() && $form->isValid()) {
                $authentication->mergeAccount($currentUser->getUserID(), $currentUser->getAuthSource(), $formData['combineUserId'], $authSourceOld);

                return $this->redirectToRoute('app_profile_mergeaccounts', array('roomId' => $roomId, 'itemId' => $itemId));
            }
        }

        return array(
            'form' => $form->createView(),
            'show_auth_source' => $show_auth_source,
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/newsletter")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function newsletterAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfileNewsletterType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();
        }

        return array(
            'form' => $form->createView(),
            'portalEmail' => $userItem->getRelatedPortalUserItem()->getRoomEmail(),
        );
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/additional")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function additionalAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfileAdditionalType::class, $userData, [
            'itemId' => $itemId,
            'emailToCommsy' => $this->getParameter('commsy.upload.enabled'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();
            return $this->redirect($request->getUri());
        }

        return [
            'form' => $form->createView(),
            'uploadEmail' => $this->getParameter('commsy.upload.account'),
            'portalEmail' => $userItem->getRelatedPortalUserItem()->getRoomEmail(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/profileImage")
     * @Template
     */
    public function imageAction($roomId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        return array('user' => $userService->getCurrentUserItem());
    }

    /**
     * @Route("/room/{roomId}/user/dropdownmenu")
     * @Template
     */
    public function menuAction(
        $roomId,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        Request $request
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        return [
            'userId' => $userService->getCurrentUserItem()->getItemId(),
            'portalUser' => $userService->getCurrentUserItem()->getRelatedPortalUserItem(),
            'roomId' => $roomId,
            'inPrivateRoom' => $legacyEnvironment->inPrivateRoom(),
            'inPortal' => $legacyEnvironment->inPortal(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/deleteaccount")
     * @Template
     */
    public function deleteAccountAction($roomId, Request $request, ParameterBagInterface $parameterBag)
    {

        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');

        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteAccountType::class, [
            'confirm_string' => $this->get('translator')->trans('lock', [], 'profile'),
        ],[])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteAccountType::class, [
            'confirm_string' => $this->get('translator')->trans('delete', [], 'profile'),
        ], [])->getForm();

        $userService = $this->get('commsy_legacy.user_service');
        $currentUser = $userService->getCurrentUserItem();
        $portalUser = $currentUser->getRelatedCommSyUserItem();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionItem = $legacyEnvironment->getSessionItem();

        $portalUrl = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

        // Lock account
        if ($request->request->has('lock_form')) {
            $lockForm->handleRequest($request);
            if ($lockForm->isSubmitted() && $lockForm->isValid()) {
                // lock account
                $portalUser->reject();
                $portalUser->save();
                // delete session
                $sessionManager->delete($sessionItem->getSessionID());
                $legacyEnvironment->setSessionItem(null);

                return $this->redirect($portalUrl);
            }
        }

        // Delete account
        elseif ($request->request->has('delete_form')) {
            $deleteForm->handleRequest($request);
            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                // delete account
                $authentication = $legacyEnvironment->getAuthenticationObject();
                $authentication->delete($portalUser->getItemID());
                // delete session
                $sessionManager->delete($sessionItem->getSessionID());
                $legacyEnvironment->setSessionItem(null);

                return $this->redirect($portalUrl);
            }
        }

        return [
            'override' => $deleteParameter,
            'form_lock' => $lockForm->createView(),
            'form_delete' => $deleteForm->createView()
        ];
    }


    /**
     * @Route("/room/{roomId}/user/{itemId}/changepassword")
     * @Template
     */
    public function changePasswordAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        if ( !$legacyEnvironment->inPortal() ) {
            $portalUser = $legacyEnvironment->getPortalUserItem();
        }
        else {
            $portalUser = $legacyEnvironment->getCurrentUserItem();
        }

        $form = $this->createForm(ProfileChangePasswordType::class);

        $changed = false;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {                 // checks old password and new password criteria constraints

            $form_data = $form->getData();

            $current_portal_item = $legacyEnvironment->getCurrentPortalItem();
            $authentication = $legacyEnvironment->getAuthenticationObject();
            $currentUser = $legacyEnvironment->getCurrentUserItem();
            $auth_manager = $authentication->getAuthManager($currentUser->getAuthSource());

            $portalUser->setPasswordExpireDate($current_portal_item->getPasswordExpiration());
            $portalUser->save();
            $auth_manager->changePassword($currentUser->getUserID(), $form_data['new_password']);

            $changed = true;

            $error_number = $auth_manager->getErrorNumber();

            if(empty($error_number)) {
                $portalUser->setNewGenerationPassword($form_data['old_password']);

                $caldavService = $this->get('commsy.caldav_service');
                $caldavService->setCalDAVHash($portalUser->getUserId(), $form_data['new_password'], 'CommSy');
            }
        }

        return array(
            'form' => $form->createView(),
            'passwordChanged' => $changed,
        );
    }


    /**
     * @Route("/room/{roomId}/user/{itemId}/deleteroomprofile")
     * @Template
     */
    public function deleteRoomProfileAction($roomId, Request $request, LegacyEnvironment $legacyEnvironment, RoomService $roomService, ParameterBagInterface $parameterBag)
    {
        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');
        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('lock', [], 'profile')], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('delete', [], 'profile')], [])->getForm();

        $userService = $this->get('commsy_legacy.user_service');
        $currentUser = $userService->getCurrentUserItem();

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $portalUrl = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

        $roomItem = $roomService->getRoomItem($roomId);

        // Lock room profile
        if ($request->request->has('lock_form')) {
            $lockForm->handleRequest($request);
            if ($lockForm->isSubmitted() && $lockForm->isValid()) {

                $currentUser->reject();
                $currentUser->save();

                return $this->redirect($portalUrl);
            }
        }

        // Delete room profile
        elseif ($request->request->has('delete_form')) {
            $deleteForm->handleRequest($request);

            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                $currentUser->delete();
                return $this->redirect($portalUrl);
            }


            // get room from RoomService
            $roomItem = $roomService->getRoomItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('No room found for id ' . $roomId);
            }
        }

        return [
            'override' => $deleteParameter,
            'form_lock' => $lockForm->createView(),
            'form_delete' => $deleteForm->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/calendars")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function calendarsAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemService = $this->get('commsy_legacy.item_service');
        $userItem = $legacyEnvironment->getCurrentUserItem();

        /**
         * Workaround:
         * Instead of calling getRelatedUserList directly on the current (room) user, we use the portal user.
         * getRelatedUserList will exclude the current context and the list remains incomplete. As a portal
         * cannot contain calenders, we make sure all relevant rooms are included.
         */
        $portalUser = $userItem->getRelatedPortalUserItem();
        $relatedUsers = $portalUser->getRelatedUserList()->to_array();

        $userContextIds = [];
        foreach ($relatedUsers as $relatedUser) {
            /** @var \cs_user_item $relatedUser */
            if ($relatedUser->isModerator()) {
                $userContextIds[] = $relatedUser->getContextID();
            }
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $repository = $this->getDoctrine()->getRepository(Calendars::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $calendars = $repository->findBy([
            'context_id' => $userContextIds,
            'external_url' => [
                '',
                null,
            ],
        ]);

        $dashboard = [];
        $caldav = [];
        $roomTitles = [];

        $privateRoomItem = $userItem->getOwnRoom();
        $calendarSelection = $privateRoomItem->getCalendarSelection();

        $options = [];
        if ($calendarSelection) {
            $options = $calendarSelection;
        }

        foreach ($calendars as $calendar) {
            $roomItemCalendar = $itemService->getTypedItem($calendar->getContextId());
            $contextArray[$calendar->getContextId()][] = $roomItemCalendar->getTitle();

            $dashboard[] = $calendar->getId();
            $caldav[] = $calendar->getId();
            $roomTitles[] = $roomItemCalendar->getTitle().' / '.$calendar->getTitle();
            if ($calendarSelection === false) {
                $options['calendarsDashboard'][] = $calendar->getId();
                $options['calendarsCalDAV'][] = $calendar->getId();
            }
        }

        $form = $this->createForm(ProfileCalendarsType::class, $options, array(
            'itemId' => $itemId,
            'dashboard' => $dashboard,
            'caldav' => $caldav,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $privateRoomItem->setCalendarSelection($form->getData());
            $privateRoomItem->save();
        }

        $protocoll = 'https://';
        if ($_SERVER['HTTPS'] == 'off') {
            $protocoll = 'http://';
        }
        $caldavUrl = $protocoll . $_SERVER['HTTP_HOST'];

        $caldavPath = $this->generateUrl('app_caldav_caldavprincipal', array(
            'portalId' => $legacyEnvironment->getCurrentPortalId(),
            'userId' => $legacyEnvironment->getCurrentUser()->getUserId(),
        ));

        $allNoneDashboard = false;
        if (isset($options['calendarsDashboard'])) {
            $allNoneDashboard = (sizeof($calendars) == sizeof($options['calendarsDashboard']));
        }

        $allNoneCaldav = false;
        if (isset($options['calendarsCalDAV'])) {
            $allNoneCaldav = (sizeof($calendars) == sizeof($options['calendarsCalDAV']));
        }

        return array(
            'form' => $form->createView(),
            'roomTitles' => $roomTitles,
            'user' => $userItem,
            'caldavUrl' => $caldavUrl,
            'caldavPath' => $caldavPath,
            'allNoneDashboard' => $allNoneDashboard,
            'allNoneCaldav' => $allNoneCaldav,
        );
    }
}
