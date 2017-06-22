<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Entity\User;
use CommsyBundle\Form\Type\Profile\RoomProfileGeneralType;
use CommsyBundle\Form\Type\Profile\RoomProfileAddressType;
use CommsyBundle\Form\Type\Profile\RoomProfileContactType;
use CommsyBundle\Form\Type\Profile\DeleteType;
use CommsyBundle\Form\Type\Profile\ProfileAccountType;
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
            // TODO: merge accounts
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
        $form = $this->createForm(DeleteType::class, [], []);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // get room from RoomService
            $roomService = $this->get('commsy_legacy.room_service');
            $roomItem = $roomService->getRoomItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('No room found for id ' . $roomId);
            }

            $roomItem->delete();
            $roomItem->save();

            // redirect back to portal
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $portal = $legacyEnvironment->getCurrentPortalItem();

            $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

            return $this->redirect($url);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
    * @Route("/room/{roomId}/user/{itemId}/deleteroomprofile")
    * @Template
    */
    public function deleteRoomProfileAction($roomId, Request $request)
    {
        $form = $this->createForm(DeleteType::class, [], []);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // get room from RoomService
            $roomService = $this->get('commsy_legacy.room_service');
            $roomItem = $roomService->getRoomItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('No room found for id ' . $roomId);
            }

            $roomItem->delete();
            $roomItem->save();

            // redirect back to portal
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $portal = $legacyEnvironment->getCurrentPortalItem();

            $url = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

            return $this->redirect($url);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
