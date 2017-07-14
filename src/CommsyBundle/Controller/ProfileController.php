<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

use CommsyBundle\Entity\User;
use CommsyBundle\Form\Type\Profile\RoomProfileGeneralType;
use CommsyBundle\Form\Type\Profile\RoomProfileAddressType;
use CommsyBundle\Form\Type\Profile\RoomProfileContactType;
use CommsyBundle\Form\Type\Profile\DeleteType;
use CommsyBundle\Form\Type\Profile\ProfileAccountType;
use CommsyBundle\Form\Type\Profile\ProfileChangePasswordType;
use CommsyBundle\Form\Type\Profile\ProfileMergeAccountsType;
use CommsyBundle\Form\Type\Profile\ProfileNotificationsType;
use CommsyBundle\Form\Type\Profile\ProfileAdditionalType;
use CommsyBundle\Form\Type\Profile\ProfilePersonalInformationType;


class ProfileController extends Controller
{
    /**
    * @Route("/room/{roomId}/user/{itemId}/general")
    * @Template
    * @Security("is_granted('ITEM_EDIT', itemId)")
    */
    public function generalAction($roomId, $itemId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $roomService = $this->get('commsy_legacy.room_service');
        $userItem = $userService->getUser($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }

        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userData = $userTransformer->transform($userItem);

        $form = $this->createForm(RoomProfileGeneralType::class, $userData, array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $formData = $form->getData();

            // save profile picture if given
            if($formData['image_data']) {
                $saveDir = implode("/", array($this->getParameter('files_directory'), $roomService->getRoomFileDirectory($userItem->getContextID())));
                $data = $formData['image_data'];
                list($fileName, $type, $data) = explode(";", $data);
                list(, $data) = explode(",", $data);
                list(, $extension) = explode("/", $type);
                $data = base64_decode($data);
                $fileName = implode("_", array('cid'.$userItem->getContextID(), $userItem->getUserID(), $fileName));
                $absoluteFilepath = implode("/", array($saveDir, $fileName));
                file_put_contents($absoluteFilepath, $data);
                $userItem->setPicture($fileName);
            }

            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();

            $userList = $userItem->getRelatedUserList();
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['imageChangeInAllContexts']) {
                    $discService = $this->get('commsy_legacy.disc_service');
                    $tempFilename = $discService->copyImageFromRoomToRoom($userItem->getPicture(), $tempUserItem->getContextId());
                    if ($tempFilename) {
                        $tempUserItem->setPicture($tempFilename);
                    }
                }
                $tempUserItem->save();
                $tempUserItem = $userList->getNext();    
            }
            
            return $this->redirectToRoute('commsy_profile_general', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'roomId' => $roomId,
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
        if ($form->isValid()) {
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

            return $this->redirectToRoute('commsy_profile_address', array('roomId' => $roomId, 'itemId' => $itemId));
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
        if ($form->isValid()) {
            $formData = $form->getData();
            $userItem = $userTransformer->applyTransformation($userItem, $formData);
            $userItem->save();

            $userList = $userItem->getRelatedUserList();
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['emailChangeInAllContexts']) {
                    $tempUserItem->setEmail($formData['email']);
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

            return $this->redirectToRoute('commsy_profile_contact', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
        );
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

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfilePersonalInformationType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            return $this->redirectToRoute('commsy_profile_personal', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
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
        if ($form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            return $this->redirectToRoute('commsy_profile_account', array('roomId' => $roomId, 'itemId' => $itemId));
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
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $form = $this->createForm(ProfileMergeAccountsType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {

            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $authentication = $legacyEnvironment->getAuthenticationObject();

            global $c_annonymous_account_array;

            $formData = $form->getData();

            $currentUser = $legacyEnvironment->getCurrentUserItem();
            if ( isset($c_annonymous_account_array) && !empty($c_annonymous_account_array[mb_strtolower($currentUser->getUserID(), 'UTF-8') . '_' . $currentUser->getAuthSource()]) && $currentUser->isOnlyReadUser() )
            {
                throw new \Exception("1014: anonymous account");
            }
            else
            {
                if ( $currentUser->getUserID() == $formData['combineUserId'] && 
                     isset($formData['auth_source']) &&
                     (empty($formData['auth_source']) || $currentUser->getAuthSource() == $formData['auth_source'] ) )
                {
                    throw new \Exception("1015: invalid account");
                }
                else
                {
                    $user_manager = $legacyEnvironment->getUserManager();
                    $user_manager->setUserIDLimitBinary($formData['combineUserId']);

                    $user_manager->select();
                    $user = $user_manager->get();
                    $first_user = $user->getFirst();

                    $current_user = $legacyEnvironment->getCurrentUserItem();

                    if(!empty($first_user)){
                        if(!isset($formData['auth_source']) || empty($formData['auth_source'])) {
                            $authManager = $authentication->getAuthManager($current_user->getAuthSource());
                        } else {
                            $authManager = $authentication->getAuthManager($formData['auth_source']);
                        }
                        if ( !$authManager->checkAccount($formData['combineUserId'], $formData['combinePassword']) )
                        {
                            throw new \Exception("1016: authentication error");
                        }
                    } else {
                        throw new \Exception("1015: invalid account");
                    }
                }
            }

            $currentUser = $legacyEnvironment->getCurrentUserItem();

            if ( isset($formData['auth_source']) )
            {
                $authSourceOld = $formData['auth_source'];
            }
            else
            {
                $authSourceOld = $legacyEnvironment->getCurrentPortalItem()->getAuthDefault();
            }

            ini_set('display_errors', 'on');
            error_reporting(E_ALL);

            $authentication->mergeAccount($currentUser->getUserID(), $currentUser->getAuthSource(), $formData['combineUserId'], $authSourceOld);

            return $this->redirectToRoute('commsy_profile_mergeaccounts', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
    * @Route("/room/{roomId}/user/{itemId}/notifications")
    * @Template
    * @Security("is_granted('ITEM_EDIT', itemId)")
    */
    public function notificationsAction($roomId, $itemId, Request $request)
    {
        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userService = $this->get('commsy_legacy.user_service');
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfileNotificationsType::class, $userData, array(
            'itemId' => $itemId,
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();
        }

        return array(
            'form' => $form->createView(),
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
            'emailToCommsy' => $this->getParameter('email.upload.enabled'),
        ]);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();
            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();
        }

        return array(
            'form' => $form->createView(),
        );
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
    public function menuAction($roomId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        return [
            'userId' => $userService->getCurrentUserItem()->getItemId(),
            'roomId' => $roomId,
        ];
    }

    /**
    * @Route("/room/{roomId}/user/{itemId}/deleteaccount")
    * @Template
    */
    public function deleteAccountAction($roomId, Request $request)
    {
        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('lock', [], 'profile')], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('delete', [], 'profile')], [])->getForm();

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
        if ($form->isValid()) {                 // checks old password and new password criteria constraints

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
    public function deleteRoomProfileAction($roomId, Request $request)
    {
        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('lock', [], 'profile')], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, ['confirm_string' => $this->get('translator')->trans('delete', [], 'profile')], [])->getForm();

        $userService = $this->get('commsy_legacy.user_service');
        $currentUser = $userService->getCurrentUserItem();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $portalUrl = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

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

                // get room from RoomService
                $roomService = $this->get('commsy_legacy.room_service');
                $roomItem = $roomService->getRoomItem($roomId);

                if (!$roomItem) {
                    throw $this->createNotFoundException('No room found for id ' . $roomId);
                }

                if ($roomItem->isGroupRoom()) {
                    $group_item = $roomItem->getLinkedGroupItem();
                    $group_item->removeMember($currentUser->getRelatedUserItemInContext($group_item->getContextID()));
                }

                return $this->redirect($portalUrl);
            }
        }

        return [
            'form_lock' => $lockForm->createView(),
            'form_delete' => $deleteForm->createView()
        ];
    }
}
