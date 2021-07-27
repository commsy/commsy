<?php

namespace App\Controller;

use App\Event\UserLeftRoomEvent;
use App\Form\DataTransformer\PrivateRoomTransformer;
use App\Form\DataTransformer\UserTransformer;
use App\Form\Type\Profile\DeleteType;
use App\Form\Type\Profile\ProfileAccountType;
use App\Form\Type\Profile\ProfileCalendarsType;
use App\Form\Type\Profile\RoomProfileAddressType;
use App\Form\Type\Profile\RoomProfileContactType;
use App\Form\Type\Profile\RoomProfileGeneralType;
use App\Form\Type\Profile\RoomProfileNotificationsType;
use App\Services\LegacyEnvironment;
use App\Utils\DiscService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_user_item;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class ProfileController
 * @package App\Controller
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/user/{itemId}/general")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param DiscService $discService
     * @param RoomService $roomService
     * @param UserService $userService
     * @param UserTransformer $userTransformer
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function general(
        Request $request,
        DiscService $discService,
        RoomService $roomService,
        UserService $userService,
        UserTransformer $userTransformer,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $environment->getEnvironment();
        $discManager = $legacyEnvironment->getDiscManager();

        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }

        $userData = $userTransformer->transform($userItem);
        $userData['useProfileImage'] = $userItem->getPicture() != "";

        $form = $this->createForm(RoomProfileGeneralType::class, $userData, [
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('app_upload_upload', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // use custom profile picture if given
            if ($formData['useProfileImage']) {
                if ($formData['image_data']) {
                    $saveDir = implode("/", [
                        $this->getParameter('files_directory'),
                        $roomService->getRoomFileDirectory($userItem->getContextID()),
                    ]);
                    if (!file_exists($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $data = $formData['image_data'];
                    list($fileName, $type, $data) = explode(";", $data);
                    list(, $data) = explode(",", $data);
                    list(, $extension) = explode("/", $type);
                    $data = base64_decode($data);
                    $fileName = implode("_", [
                        'cid' . $userItem->getContextID(),
                        $userItem->getUserID(),
                        $fileName,
                    ]);
                    $absoluteFilepath = implode("/", [$saveDir, $fileName]);
                    file_put_contents($absoluteFilepath, $data);
                    $userItem->setPicture($fileName);

                    $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
                    $userItem->save();
                }
            } else {
                // use user initials else
                if ($discManager->existsFile($userItem->getPicture())) {
                    $discManager->unlinkFile($userItem->getPicture());
                }
                $userItem->setPicture("");
                $userItem->save();
            }

            if ($formData['imageChangeInAllContexts']) {
                $userList = $userItem->getRelatedUserList(true);
                /** @var cs_user_item $tempUserItem */
                $tempUserItem = $userList->getFirst();
                while ($tempUserItem) {
                    if ($tempUserItem->getItemId() == $userItem->getItemId()) {
                        $tempUserItem = $userList->getNext();
                        continue;
                    }
                    if ($formData['useProfileImage']) {
                        $tempFilename = $discService->copyImageFromRoomToRoom($userItem->getPicture(),
                            $tempUserItem->getContextId());
                        if ($tempFilename) {
                            $tempUserItem->setPicture($tempFilename);
                        }
                    } else {
                        if ($discManager->existsFile($tempUserItem->getPicture())) {
                            $discManager->unlinkFile($tempUserItem->getPicture());
                        }
                        $tempUserItem->setPicture("");

                    }
                    $tempUserItem->save();
                    $tempUserItem = $userList->getNext();
                }
            }

            return $this->redirectToRoute('app_profile_general', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        $roomItem = $roomService->getRoomItem($roomId);

        return [
            'roomId' => $roomId,
            'roomTitle' => $roomItem->getTitle(),
            'user' => $userItem,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/address")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function address(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

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

            $userList = $userItem->getRelatedUserList(true);
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['titleChangeInAllContexts']) {
                    $tempUserItem->setTitle($formData['title']);
                }
                if ($formData['streetChangeInAllContexts']) {
                    $tempUserItem->setStreet($formData['street']);
                }
                if ($formData['zipCodeChangeInAllContexts']) {
                    $tempUserItem->setZipcode($formData['zipCode']);
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserService $userService
     * @param UserTransformer $userTransformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function contact(
        Request $request,
        PrivateRoomTransformer $privateRoomTransformer,
        UserService $userService,
        UserTransformer $userTransformer,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

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
            $userList = $userItem->getRelatedUserList(true);
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                if ($formData['emailChangeInAllContexts']) {
                    // do not change the account email address (or private room) even if the user
                    // wants to change all related room users
                    $contextItem = $tempUserItem->getContextItem();
                    if ($contextItem && !$contextItem->isPortal() && !$contextItem->isPrivateRoom()) {
                        $tempUserItem->setEmail($formData['emailRoom']);
                    }
                    $usePortalEmail = ($formData['emailChoice'] === 'account') ? 1 : 0;
                    $tempUserItem->setUsePortalEmail($usePortalEmail);
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param int $itemId
     * @return array
     */
    public function notifications(
        Request $request,
        UserService $userService,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
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
     * @Route("/room/{roomId}/user/profileImage")
     * @Template
     * @param UserService $userService
     * @return array
     */
    public function image(
        UserService $userService
    ) {
        return array('user' => $userService->getCurrentUserItem());
    }

    /**
     * @Route("/room/{roomId}/user/dropdownmenu")
     * @Template
     * @param UserService $userService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @return array
     */
    public function menu(
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        bool $uikit3 = false
    ) {
        $environment = $legacyEnvironment->getEnvironment();
        return [
            'userId' => $userService->getCurrentUserItem()->getItemId(),
            'portalUser' => $userService->getCurrentUserItem()->getRelatedPortalUserItem(),
            'roomId' => $roomId,
            'inPrivateRoom' => $environment->inPrivateRoom(),
            'inPortal' => $environment->inPortal(),
            'uikit3' => $uikit3,
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/deleteroomprofile")
     * @Template
     * @Security("is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param UserService $userService
     * @param ParameterBagInterface $parameterBag
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface $translator
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function deleteRoomProfile(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService,
        ParameterBagInterface $parameterBag,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        int $roomId
    ) {
        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');
        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, [
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ], [])->getForm();

        $currentUser = $userService->getCurrentUserItem();

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $portalUrl = $this->generateUrl('app_helper_portalenter', [
            'context' => $portal->getItemId(),
        ]);

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        // Lock room profile
        if ($request->request->has('lock_form')) {
            $lockForm->handleRequest($request);
            if ($lockForm->isSubmitted() && $lockForm->isValid()) {

                $currentUser->reject();
                $currentUser->save();

                return $this->redirect($portalUrl);
            }
        } // Delete room profile
        elseif ($request->request->has('delete_form')) {
            $deleteForm->handleRequest($request);

            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                $currentUser->delete();

                $event = new UserLeftRoomEvent($currentUser, $roomItem);
                $eventDispatcher->dispatch($event);

                return $this->redirect($portalUrl);
            }
        }

        return [
            'override' => $deleteParameter,
            'form_lock' => $lockForm->createView(),
            'form_delete' => $deleteForm->createView(),
        ];
    }
}
