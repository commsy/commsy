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

use App\Event\UserJoinedRoomEvent;
use App\Facade\MembershipManager;
use App\Form\Type\ContextRequestType;
use App\Mail\Factories\RoomMessageFactory;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\RoomRepository;
use App\Services\LegacyEnvironment;
use App\Utils\GroupService;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContextController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class ContextController extends AbstractController
{
    #[Route(path: '/room/{roomId}/context/{itemId}/request', requirements: ['itemId' => '\d+'])]
    public function requestAction(
        Request $request,
        LegacyEnvironment $environment,
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        MembershipManager $membershipManager,
        GroupService $groupService,
        RoomMessageFactory $roomMessageFactory,
        RoomRepository $roomRepository,
        Mailer $mailer,
        int $roomId,
        int $itemId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        $currentUserItem = $legacyEnvironment->getCurrentUserItem();
        if ($currentUserItem->isReallyGuest()) {
            throw $this->createAccessDeniedException();
        }

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        // redirect to the room's detail page if a room membership (request) already exists for this user
        $userTestItem = $roomItem->getUserByUserID($currentUserItem->getUserID(), $currentUserItem->getAuthSource());
        if ($userTestItem) {
            $route = $this->redirectToRoomDetailPage($legacyEnvironment, $roomId, $itemId);

            return $route;
        }

        // determine form options
        $formOptions = [
            'checkNewMembersWithCode' => false,
            'withAGB' => false,
            'CheckNewMembersNever' => false,
        ];

        if ($roomItem->checkNewMembersWithCode()) {
            $formOptions['checkNewMembersWithCode'] = true;
            $formOptions['checkNewMemberCode'] = $roomItem->getCheckNewMemberCode();
        }

        $agbText = '';
        if (2 != $roomItem->getAGBStatus()) {
            $formOptions['withAGB'] = true;

            // get agb text in users language
            $agbText = $roomItem->getAGBTextArray()[strtoupper((string) $legacyEnvironment->getUserLanguage())];
        }

        if ($roomItem->checkNewMembersNever()) {
            $formOptions['CheckNewMembersNever'] = true;
        }

        $form = $this->createForm(ContextRequestType::class, null, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (($form->has('request') && $form->get('request')->isClicked()) ||
                ($form->has('coderequest') && $form->get('coderequest')->isClicked())
            ) {
                $formData = $form->getData();

                // At this point we can assume that the user has accepted agb and
                // provided the correct code if necessary (or provided no code at all).
                // We can now build a new user item and set the appropriate status

                // TODO: try to make use of UserService->cloneUser() instead

                $privateRoomUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();
                $portalUserItem = $legacyEnvironment->getPortalUserItem();

                $sourceUser = $privateRoomUserItem ?? $currentUserItem;
                $newUser = $sourceUser->cloneData();

                // TODO: fix inconsistency!! privateRoomUser or portalUser as "account" user?
                if ($portalUserItem) {
                    $newUser->setEmail($portalUserItem->getEmail());
                }

                $newUser->setUsePortalEmail(1);
                $newUser->setContextID($roomItem->getItemID());

                $userService->cloneUserPicture($sourceUser, $newUser);

                if ($form->has('description') && $formData['description']) {
                    $newUser->setUserComment($formData['description']);
                }

                if ($roomItem->checkNewMembersAlways() ||
                    ($roomItem->checkNewMembersWithCode() && $form->get('request')->isClicked())) {
                    // The user either needs to ask for access or provided no code
                    $newUser->request();
                    $isRequest = true;
                } else {
                    // no authorization is needed at all or the code was correct
                    $newUser->makeUser();
                    $isRequest = false;
                }

                if ($roomItem->getAGBStatus()) {
                    $newUser->setAGBAcceptanceDate(new DateTimeImmutable());
                }

                if ($legacyEnvironment->getCurrentPortalItem()->getConfigurationHideMailByDefault()) {
                    $newUser->setEmailNotVisible();
                }

                // check if user id already exists
                $userTestItem = $roomItem->getUserByUserID($newUser->getUserID(), $newUser->getAuthSource());
                if (!$userTestItem && !$newUser->isReallyGuest() && !$newUser->isRoot()) {
                    $newUser->save();
                    $newUser->setCreatorID2ItemID();

                    // link user with group "all"
                    $userService->addUserToSystemGroupAll($newUser, $roomItem);

                    // save task
                    if ($isRequest) {
                        $taskManager = $legacyEnvironment->getTaskManager();
                        $taskItem = $taskManager->getNewItem();

                        $taskItem->setCreatorItem($currentUserItem);
                        $taskItem->setContextID($roomItem->getItemID());
                        $taskItem->setTitle('TASK_USER_REQUEST');
                        $taskItem->setStatus('REQUEST');
                        $taskItem->setItem($newUser);
                        $taskItem->save();
                    }

                    // mail to moderators
                    $moderatorRecipients = RecipientFactory::createModerationRecipients(
                        $roomItem, fn ($moderator) =>
                            /* @var cs_user_item $moderator */
                            'yes' == $moderator->getAccountWantMail()
                    );

                    $room = $roomRepository->find($itemId);
                    $message = $roomMessageFactory->createUserJoinedContextMessage(
                        $room,
                        $newUser,
                        $form->has('description') ? $formData['description'] : null
                    );
                    $mailer->sendMultiple($message, $moderatorRecipients);
                }

                // inform user if request required no authorization
                if ($newUser->isUser()) {
                    $moderatorList = $roomItem->getModeratorList();
                    $contactModerator = $moderatorList->getFirst();

                    $modFullName = $contactModerator ? $contactModerator->getFullname() : '';
                    $modEmail = $contactModerator ? $contactModerator->getEmail() : '';

                    $translator = $legacyEnvironment->getTranslationObject();
                    $translator->setEmailTextArray($roomItem->getEmailTextArray());
                    $translator->setContext('project');

                    $savedLanguage = $translator->getSelectedLanguage();

                    $language = $roomItem->getLanguage();
                    if ('user' == $language) {
                        $language = $newUser->getLanguage();
                        if ('browser' == $language) {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                        $userId = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
                    } else {
                        $userId = $newUser->getUserID();
                    }

                    $translator->setSelectedLanguage($language);

                    $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $roomItem->getTitle());

                    $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date('Y-m-d H:i:s')),
                        $translator->getTimeInLang(date('Y-m-d H:i:s')));
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $newUser->getFullname());
                    $body .= "\n\n";
                    if ($roomItem->isCommunityRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GR', $userId,
                            $roomItem->getTitle());
                    } else {
                        if ($roomItem->isProjectRoom()) {
                            $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_PR', $userId,
                                $roomItem->getTitle());
                        } else {
                            if ($roomItem->isGroupRoom()) {
                                $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GP', $userId,
                                    $roomItem->getTitle());
                            }
                        }
                    }
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $modFullName, $roomItem->getTitle());
                    $body .= "\n\n";
                    $body .= $this->generateUrl('app_room_home', [
                        'roomId' => $roomItem->getItemID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $replyTo = [];
                    if ('' != $modEmail && '' != $modFullName) {
                        $replyTo[] = new Address($modEmail, $modFullName);
                    }

                    $mailer->sendRaw(
                        $subject,
                        nl2br($body),
                        RecipientFactory::createRecipient($newUser),
                        $roomItem->getContextItem()->getTitle(),
                        $replyTo
                    );

                    $translator->setSelectedLanguage($savedLanguage);
                }

                $event = new UserJoinedRoomEvent($newUser, $roomItem);
                $eventDispatcher->dispatch($event);
            }

            // leave group in case of a cancelled group room membership request
            if ($form->get('cancel')->isClicked() && $roomItem->isGroupRoom()) {
                $account = $this->getUser();
                $group = $groupService->getGroup($roomItem->getLinkedGroupItemID());
                $membershipManager->leaveGroup($group, $account);
            }

            // redirect to detail page
            $route = $this->redirectToRoomDetailPage($legacyEnvironment, $roomId, $itemId);

            return $route;
        }

        return $this->render('context/request.html.twig', [
            'form' => $form,
            'agbText' => $agbText,
            'title' => html_entity_decode($roomItem->getTitle()),
        ]);
    }

    /**
     * Redirects to an appropriate room detail page for the given item & room IDs.
     *
     * @param cs_environment $legacyEnvironment
     * @param int $roomId ID of the context for the item with $itemId
     * @param int $itemId ID of the (group) room whose detail page shall be displayed
     * @return RedirectResponse
     */
    private function redirectToRoomDetailPage(
        cs_environment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        if ($roomItem->isGroupRoom()) {
            $route = $this->redirectToRoute('app_group_detail', [
                'roomId' => $roomId,
                'itemId' => $roomItem->getLinkedGroupItemID(),
            ]);
        } else {
            if ($roomManager->getItem($roomId)) {
                // in community-context -> redirect to detail view in project rubric.
                $route = $this->redirectToRoute('app_project_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            } else {
                // in private room context -> redirect to detail view of all rooms list.
                $route = $this->redirectToRoute('app_roomall_detail', [
                    'portalId' => $legacyEnvironment->getCurrentPortalID(),
                    'itemId' => $itemId,
                ]);
            }
        }

        return $route;
    }
}
