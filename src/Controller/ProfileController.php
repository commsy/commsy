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

use App\Entity\Account;
use App\Facade\MembershipManager;
use App\Form\DataTransformer\PrivateRoomTransformer;
use App\Form\DataTransformer\UserTransformer;
use App\Form\Type\Profile\DeleteType;
use App\Form\Type\Profile\RoomProfileAddressType;
use App\Form\Type\Profile\RoomProfileContactType;
use App\Form\Type\Profile\RoomProfileGeneralType;
use App\Form\Type\Profile\RoomProfileNotificationsType;
use App\Services\LegacyEnvironment;
use App\Utils\DiscService;
use App\Utils\GroupService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ProfileController.
 */
class ProfileController extends AbstractController
{
    #[Route(path: '/room/{roomId}/user/{itemId}/general')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function general(
        Request $request,
        DiscService $discService,
        RoomService $roomService,
        UserService $userService,
        UserTransformer $userTransformer,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ): Response {
        /** @var cs_environment $legacyEnvironment */
        $legacyEnvironment = $environment->getEnvironment();
        $discManager = $legacyEnvironment->getDiscManager();

        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id '.$itemId);
        }

        $userData = $userTransformer->transform($userItem);
        $userData['useProfileImage'] = '' != $userItem->getPicture();

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
                    $saveDir = implode('/', [
                        $this->getParameter('files_directory'),
                        $roomService->getRoomFileDirectory($userItem->getContextID()),
                    ]);
                    if (!file_exists($saveDir)) {
                        mkdir($saveDir, 0777, true);
                    }
                    $data = $formData['image_data'];
                    [$fileName, $type, $data] = explode(';', (string) $data);
                    [, $data] = explode(',', $data);
                    [, $extension] = explode('/', $type);
                    $data = base64_decode($data);
                    $fileName = implode('_', [
                        'cid'.$userItem->getContextID(),
                        $userItem->getUserID(),
                        $fileName,
                    ]);
                    $absoluteFilepath = implode('/', [$saveDir, $fileName]);
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
                $userItem->setPicture('');
                $userItem->save();
            }

            if ($formData['imageChangeInAllContexts']) {
                $userList = $userItem->getRelatedUserList(true);
                foreach ($userList as $tempUserItem) {
                    /** @var cs_user_item $tempUserItem */
                    if ($tempUserItem->getItemId() == $userItem->getItemId()) {
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
                        $tempUserItem->setPicture('');
                    }
                    $tempUserItem->save();
                }
            }

            return $this->redirectToRoute('app_profile_general', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        $roomItem = $roomService->getRoomItem($roomId);

        return $this->render('profile/general.html.twig', [
            'roomId' => $roomId,
            'roomTitle' => $roomItem->getTitle(),
            'user' => $userItem,
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/address')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function address(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        int $roomId,
        int $itemId
    ): Response {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(RoomProfileAddressType::class, $userData, ['itemId' => $itemId]);

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

            return $this->redirectToRoute('app_profile_address', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        return $this->render('profile/address.html.twig', ['form' => $form]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/contact')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function contact(
        Request $request,
        PrivateRoomTransformer $privateRoomTransformer,
        UserService $userService,
        UserTransformer $userTransformer,
        int $roomId,
        int $itemId
    ): Response {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(RoomProfileContactType::class, $userData, ['itemId' => $itemId]);

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
                    $usePortalEmail = ('account' === $formData['emailChoice']) ? 1 : 0;
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

            return $this->redirectToRoute('app_profile_contact', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        return $this->render('profile/contact.html.twig', ['form' => $form]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/notifications')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function notifications(
        Request $request,
        UserService $userService,
        int $roomId,
        int $itemId
    ): Response {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = [];

        $userData['mail_account'] = 'yes' === $userItem->getAccountWantMail();
        $userData['mail_room'] = 'yes' === $userItem->getOpenRoomWantMail();
        $userData['mail_item_deleted'] = $userItem->getDeleteEntryWantMail();

        $form = $this->createForm(RoomProfileNotificationsType::class, $userData, ['itemId' => $itemId]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $userItem->setAccountWantMail($formData['mail_account'] ? 'yes' : 'no');
            $userItem->setOpenRoomWantMail($formData['mail_room'] ? 'yes' : 'no');
            $userItem->setDeleteEntryWantMail($formData['mail_item_deleted']);

            $userItem->save();
        }

        return $this->render('profile/notifications.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/profileImage')]
    public function image(
        UserService $userService
    ): Response {
        return $this->render('profile/image.html.twig', ['user' => $userService->getCurrentUserItem()]);
    }

    #[Route(path: '/room/{roomId}/user/dropdownmenu')]
    public function menu(
        UserService $userService,
        int $roomId,
        bool $uikit3 = false
    ): Response {
        return $this->render('profile/menu.html.twig', [
            'portalUser' => $userService->getCurrentUserItem()->getRelatedPortalUserItem(),
            'roomId' => $roomId,
            'uikit3' => $uikit3,
        ]);
    }

    #[Route(path: '/room/{roomId}/user/{itemId}/deleteroomprofile')]
    #[IsGranted('ITEM_ENTER', subject: 'roomId')]
    public function deleteRoomProfile(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService,
        ParameterBagInterface $parameterBag,
        TranslatorInterface $translator,
        MembershipManager $membershipManager,
        GroupService $groupService,
        FormFactoryInterface $formFactory,
        int $roomId
    ): Response {
        /** @var Account $account */
        $account = $this->getUser();
        if (!$account) {
            throw $this->createAccessDeniedException();
        }

        $deleteParameter = $this->getParameter('commsy.security.privacy_disable_overwriting');
        $lockForm = $formFactory->createNamedBuilder('lock_form', DeleteType::class, [
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ], [])->getForm();
        $deleteForm = $formFactory->createNamedBuilder('delete_form', DeleteType::class, [
            'confirm_string' => $translator->trans('delete', [], 'profile'),
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
                $userService->propagateStatusToGrouproomUsersForUser($currentUser);

                return $this->redirect($portalUrl);
            }
        } // Delete room profile
        elseif ($request->request->has('delete_form')) {
            $deleteForm->handleRequest($request);

            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                $membershipManager->leaveWorkspace($roomItem, $account);

                return $this->redirect($portalUrl);
            }
            if ($request->query->has('groupId')) {
                $groupId = $request->query->get('groupId');
                $roomEndId = $request->query->get('roomEndId');
                $group = $groupService->getGroup($groupId);
                $membershipManager->leaveGroup($group, $account);
                $membershipManager->leaveWorkspace($roomItem, $account);
                $group = $groupService->getGroup($groupId);
                $roomItem->delete();
                $group->delete();

                return $this->redirectToRoute('app_group_list', [
                    'roomId' => $roomEndId,
                ]);
            }
        }

        return $this->render('profile/delete_room_profile.html.twig', [
            'override' => $deleteParameter,
            'form_lock' => $lockForm,
            'form_delete' => $deleteForm,
        ]);
    }
}
