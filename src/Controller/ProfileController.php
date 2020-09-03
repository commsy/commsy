<?php

namespace App\Controller;

use App\Form\DataTransformer\PrivateRoomTransformer;
use App\Form\DataTransformer\UserTransformer;
use App\Utils\DiscService;
use App\Utils\UserService;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Calendars;
use App\Event\AccountChangedEvent;
use App\Event\UserLeftRoomEvent;
use App\Form\Type\Profile\DeleteAccountType;
use App\Form\Type\Profile\DeleteType;
use App\Form\Type\Profile\ProfileAccountType;
use App\Form\Type\Profile\ProfileAdditionalType;
use App\Form\Type\Profile\ProfileCalendarsType;
use App\Form\Type\Profile\ProfileChangePasswordType;
use App\Form\Type\Profile\ProfileMergeAccountsType;
use App\Form\Type\Profile\ProfileNewsletterType;
use App\Form\Type\Profile\ProfilePersonalInformationType;
use App\Form\Type\Profile\ProfilePrivacyType;
use App\Form\Type\Profile\RoomProfileAddressType;
use App\Form\Type\Profile\RoomProfileContactType;
use App\Form\Type\Profile\RoomProfileGeneralType;
use App\Form\Type\Profile\RoomProfileNotificationsType;
use App\Privacy\PersonalDataCollector;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\RoomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


/**
 * Class ProfileController
 * @package App\Controller
 */
class ProfileController extends AbstractController
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
    public function generalAction(
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
            'uploadUrl' => $this->generateUrl('app_upload_upload',[
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // use custom profile picture if given
            if($formData['useProfileImage']) {
                if($formData['image_data']) {
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
                        'cid'.$userItem->getContextID(),
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
                $userList = $userItem->getRelatedUserList();
                /** @var cs_user_item $tempUserItem */
                $tempUserItem = $userList->getFirst();
                while ($tempUserItem) {
                    if ($tempUserItem->getItemId() == $userItem->getItemId()) {
                        $tempUserItem = $userList->getNext();
                        continue;
                    }
                    if ($formData['useProfileImage']) {
                        $tempFilename = $discService->copyImageFromRoomToRoom($userItem->getPicture(), $tempUserItem->getContextId());
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
    public function addressAction(
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

            $userList = $userItem->getRelatedUserList();
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
    public function contactAction(
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param int $itemId
     * @return array
     */
    public function notificationsAction(
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
     * @Route("/room/{roomId}/user/{itemId}/personal")
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
    public function personalAction(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $portalUser = $userItem->getRelatedPortalUserItem();

        $request->setLocale($userItem->getLanguage());

        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfilePersonalInformationType::class, $userData, [
            'itemId' => $itemId,
            'portalUser' => $portalUser,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldUserItem = clone $userItem;

            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();

            $event = new AccountChangedEvent($oldUserItem, $userItem);
            $eventDispatcher->dispatch($event);

            return $this->redirectToRoute('app_profile_personal', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'form' => $form->createView(),
            'hasToChangeEmail' => $portalUser->hasToChangeEmail(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/account")
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
    public function accountAction(
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

        $request->setLocale($userItem->getLanguage());

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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function mergeAccountsAction(
        Request $request,
        UserService $userService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        // account administration page => set language to user preferences
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
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param int $itemId
     * @return array
     */
    public function newsletterAction(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $request->setLocale($userItem->getLanguage());

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
     * @Route("/room/{roomId}/user/{itemId}/privacy")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function privacyAction($roomId, $itemId, Request $request)
    {
        $form = $this->createForm(ProfilePrivacyType::class, null, [
            'attr' => array(
                'target' => '_blank'
            )
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // generate & serve a PDF with the user's personal master data
            return $this->redirectToRoute('app_profile_privacyprint', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
        );
    }
    /**
     * @Route("/room/{roomId}/user/{itemId}/privacy/print")
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function privacyPrintAction($roomId, $itemId, LegacyEnvironment $legacyEnvironment, PersonalDataCollector $dataCollector, PrintService $printService, RoomService $roomService)
    {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $serviceLink = $roomService->buildServiceLink();
        $serviceEmail = $roomService->getServiceEmail();

        // gather the user's personal master data
        $personalData = $dataCollector->getPersonalDataForUserID($itemId);

        // generate HTML data
        $html = $this->renderView('profile/privacy_print.html.twig', [
            'roomId' => $roomId,
            'printProfileImages' => true, // set to `false` to omit profile images when generating the PDF (much faster)
            'accountData' => $personalData->getAccountData(),
            'communityRoomProfileDataArray' => $personalData->getCommunityRoomProfileDataArray(),
            'projectRoomProfileDataArray' => $personalData->getProjectRoomProfileDataArray(),
            'groupRoomProfileDataArray' => $personalData->getGroupRoomProfileDataArray(),
            'serviceLink' => $serviceLink,
            'serviceEmail' => $serviceEmail,
        ]);

        $fileName = $this->translator->trans('Self assessment', [], 'profile')
            . ' (' . $portal->getTitle() . ').pdf';

        // return HTML Response containing a PDF generated from the HTML data
        return $printService->buildPdfResponse($html, false, $fileName);
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/additional")
     * @Template
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param PrivateRoomTransformer $privateRoomTransformer
     * @param UserTransformer $userTransformer
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function additionalAction(
        Request $request,
        UserService $userService,
        PrivateRoomTransformer $privateRoomTransformer,
        UserTransformer $userTransformer,
        EventDispatcherInterface $eventDispatcher,
        int $itemId
    ) {
        /** @var cs_user_item $userItem */
        $userItem = $userService->getUser($itemId);
        $userData = $userTransformer->transform($userItem);

        $request->setLocale($userItem->getLanguage());

        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(ProfileAdditionalType::class, $userData, [
            'itemId' => $itemId,
            'emailToCommsy' => $this->getParameter('commsy.upload.enabled'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldUserItem = clone $userItem;

            $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
            $userItem->save();

            $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
            $privateRoomItem->save();

            $event = new AccountChangedEvent($oldUserItem, $userItem);
            $eventDispatcher->dispatch($event);

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
     * @param UserService $userService
     * @return array
     */
    public function imageAction(
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
    public function menuAction(
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId
    ) {
        $environment = $legacyEnvironment->getEnvironment();
        return [
            'userId' => $userService->getCurrentUserItem()->getItemId(),
            'portalUser' => $userService->getCurrentUserItem()->getRelatedPortalUserItem(),
            'roomId' => $roomId,
            'inPrivateRoom' => $environment->inPrivateRoom(),
            'inPortal' => $environment->inPortal(),
        ];
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/deleteaccount")
     * @Template
     * @Security("is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param UserService $userService
     * @param LegacyEnvironment $environment
     * @return array|RedirectResponse
     */
    public function deleteAccountAction(
        Request $request,
        UserService $userService,
        LegacyEnvironment $environment,
        ParameterBagInterface $parameterBag
    ) {
        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');

        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteAccountType::class, [
            'confirm_string' => $this->translator->trans('lock', [], 'profile'),
        ],[])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteAccountType::class, [
            'confirm_string' => $this->translator->trans('delete', [], 'profile'),
        ], [])->getForm();

        $currentUser = $userService->getCurrentUserItem();
        $portalUser = $currentUser->getRelatedCommSyUserItem();

        $legacyEnvironment = $environment->getEnvironment();
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
     * @Security("is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @return array
     */
    public function changePasswordAction(
        Request $request,
        LegacyEnvironment $environment
    ) {
        $legacyEnvironment = $environment->getEnvironment();

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
     * @Security("is_granted('ITEM_ENTER', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param UserService $userService
     * @param RoomService $roomService
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function deleteRoomProfileAction(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService,
        ParameterBagInterface $parameterBag,
        EventDispatcherInterface $eventDispatcher,
        int $roomId
    ) {
        $deleteParameter = $parameterBag->get('commsy.security.privacy_disable_overwriting');
        $lockForm = $this->get('form.factory')->createNamedBuilder('lock_form', DeleteType::class, [
            'confirm_string' => $this->translator->trans('lock', [], 'profile'),
        ], [])->getForm();
        $deleteForm = $this->get('form.factory')->createNamedBuilder('delete_form', DeleteType::class, [
            'confirm_string' => $this->translator->trans('delete', [], 'profile')
        ], [])->getForm();

        $currentUser = $userService->getCurrentUserItem();

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portal = $legacyEnvironment->getCurrentPortalItem();

        $portalUrl = $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId();

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
        }

        // Delete room profile
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

        $request->setLocale($userItem->getLanguage());

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
