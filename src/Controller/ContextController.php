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
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use App\Utils\GroupService;
use App\Utils\UserService;
use cs_list;
use cs_user_item;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class ContextController.
 */
#[Security("is_granted('ITEM_ENTER', roomId)")]
class ContextController extends AbstractController
{
    private Mailer $mailer;

    #[Required]
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route(path: '/room/{roomId}/context/{itemId}/request', requirements: ['itemId' => '\d+'])]
    public function requestAction(
        Request $request,
        LegacyEnvironment $environment,
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        MembershipManager $membershipManager,
        GroupService $groupService,
        int $roomId,
        int $itemId
    ): array|Response {
        $legacyEnvironment = $environment->getEnvironment();

        $currentUserItem = $legacyEnvironment->getCurrentUserItem();
        if ($currentUserItem->isReallyGuest()) {
            throw new AccessDeniedException();
        }

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        // determine form options
        $formOptions = [
            'checkNewMembersWithCode' => false,
            'withAGB' => false,
            'CheckNewMembersNever' => false,
        ];

        if ($roomItem->checkNewMembersWithCode()) {
            $formOptions['checkNewMembersWithCode'] = $roomItem->getCheckNewMemberCode();
        }

        $agbText = '';
        if (2 != $roomItem->getAGBStatus()) {
            $formOptions['withAGB'] = true;

            // get agb text in users language
            $agbText = $roomItem->getAGBTextArray()[strtoupper($legacyEnvironment->getUserLanguage())];
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

                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
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
                    ($roomItem->checkNewMembersWithCode() && !isset($formData['code']))) {
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

                    // language
                    $language = $roomItem->getLanguage();
                    if ('user' == $language) {
                        $language = $newUser->getLanguage();
                        if ('browser' == $language) {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    $translator = $legacyEnvironment->getTranslationObject();

                    if (!empty($moderatorRecipients)) {
                        $savedLanguage = $translator->getSelectedLanguage();
                        $translator->setSelectedLanguage($language);

                        $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date('Y-m-d H:i:s')),
                            $translator->getTimeInLang(date('Y-m-d H:i:s')));
                        $body .= "\n\n";

                        if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                            $userId = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
                        } else {
                            $userId = $newUser->getUserID();
                        }
                        if ($roomItem->isGroupRoom()) {
                            $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY',
                                $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        } else {
                            if ($roomItem->isCommunityRoom()) {
                                $body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY',
                                    $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                            } else {
                                $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(),
                                    $userId, $newUser->getEmail(), $roomItem->getTitle());
                            }
                        }
                        $body .= "\n\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                        } else {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                        }
                        $body .= "\n\n";

                        if ($form->has('description') && $formData['description']) {
                            $body .= $translator->getMessage('MAIL_COMMENT_BY', $newUser->getFullname(),
                                $formData['description']);
                            $body .= "\n\n";
                        }

                        $moderators = '';
                        foreach ($moderatorRecipients as $recipient) {
                            $moderators .= $recipient->getFirstname().' '.$recipient->getLastname()."\n";
                        }

                        $body .= $translator->getMessage('MAIL_SEND_TO', $moderators);
                        $body .= "\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('MAIL_USER_FREE_LINK')."\n";
                            $body .= $this->generateUrl('app_user_list', [
                                'roomId' => $roomItem->getItemID(),
                                'user_filter' => [
                                    'user_status' => 1,
                                ],
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                        } else {
                            $body .= $this->generateUrl('app_room_home', [
                                'roomId' => $roomItem->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                        }

                        $subject = $translator->getMessage(
                            'USER_JOIN_CONTEXT_MAIL_SUBJECT',
                            $newUser->getFullname(),
                            $roomItem->getTitle()
                        );
                        $this->mailer->sendMultipleRaw($subject, nl2br($body), $moderatorRecipients);

                        $translator->setSelectedLanguage($savedLanguage);
                    }
                }

                // inform user if request required no authorization
                if ($newUser->isUser()) {
                    /** @var cs_list $moderatorList */
                    $moderatorList = $roomItem->getModeratorList();

                    $contactModerator = $moderatorList->getFirst();

                    $modFullName = '';
                    $modEmail = '';

                    if ($contactModerator) {
                        $modFullName = $contactModerator->getFullname();
                        $modEmail = $contactModerator->getEmail();
                    }

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

                    $this->mailer->sendRaw(
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

            // redirect to detail page
            if ($roomItem->isGroupRoom()) {
                if ($form->get('cancel')->isClicked()) {
                    $account = $this->getUser();
                    $group = $groupService->getGroup($roomItem->getLinkedGroupItemID());
                    $membershipManager->leaveGroup($group, $account);
                }
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

        return $this->render('context/request.html.twig', [
            'form' => $form->createView(),
            'agbText' => $agbText,
            'title' => html_entity_decode($roomItem->getTitle()),
        ]);
    }
}
