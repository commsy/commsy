<?php

namespace App\Controller;

use App\Event\UserJoinedRoomEvent;
use App\Filter\ProjectFilterType;
use App\Form\Type\ContextRequestType;
use App\Utils\UserService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContextController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ContextController extends Controller
{    
    /**
     * @Route("/room/{roomId}/context")
     *
     * @param int $roomId
     * @param Request $request
     *
     * @return array
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('app_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'context',
            'itemsCountArray' => $itemsCountArray
        );
    }
    
    /**
     * @Route("/room/{roomId}/context/{itemId}/request", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     *
     * @param int $roomId
     * @param int $itemId
     * @param Request $request
     *
     * @return array|Response
     */
    public function requestAction(
        $roomId,
        $itemId,
        Request $request,
        UserService $userService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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
        ];

        if ($roomItem->checkNewMembersWithCode()) {
            $formOptions['checkNewMembersWithCode'] = $roomItem->getCheckNewMemberCode();
        }
        
        $agbText = '';
        if ($roomItem->getAGBStatus() != 2) {
            $formOptions['withAGB'] = true;

            // get agb text in users language
            $agbText = $roomItem->getAGBTextArray()[strtoupper($legacyEnvironment->getUserLanguage())];
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
                    $newUser->setAGBAcceptance();
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
                    $message = (new \Swift_Message())
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setReplyTo([$newUser->getEmail() => $newUser->getFullName()]);

                    $userManager = $legacyEnvironment->getUserManager();
                    $userManager->resetLimits();
                    $userManager->setModeratorLimit();
                    $userManager->setContextLimit($roomItem->getItemID());
                    $userManager->select();

                    $moderatorList = $userManager->get();

                    /** @var \cs_user_item $moderator */
                    $moderator = $moderatorList->getFirst();
                    $moderators = '';
                    while ($moderator) {
                        if ($moderator->getAccountWantMail() == 'yes') {
                            $message->addTo($moderator->getEmail(), $moderator->getFullname());
                            $moderators .= $moderator->getFullname() . "\n";
                        }

                        $moderator = $moderatorList->getNext();
                    }

                    // language
                    $language = $roomItem->getLanguage();
                    if ($language == 'user') {
                        $language = $newUser->getLanguage();
                        if ($language == 'browser') {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    $translator = $legacyEnvironment->getTranslationObject();

                    if ($message->getTo()) {
                        $savedLanguage = $translator->getSelectedLanguage();
                        $translator->setSelectedLanguage($language);

                        $message->setSubject($translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT', $newUser->getFullname(), $roomItem->getTitle()));

                        $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date("Y-m-d H:i:s")), $translator->getTimeInLang(date("Y-m-d H:i:s")));
                        $body .= "\n\n";

                        if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                            $userId = 'XXX ' . $translator->getMessage('COMMON_DATASECURITY');
                        } else {
                            $userId = $newUser->getUserID();
                        }
                        if ($roomItem->isGroupRoom()) {
                            $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        } else if ($roomItem->isCommunityRoom()) {
                            $body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        } else {
                            $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        }
                        $body .= "\n\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                        } else {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                        }
                        $body .= "\n\n";

                        if ($form->has('description') && $formData['description']) {
                            $body .= $translator->getMessage('MAIL_COMMENT_BY', $newUser->getFullname(), $formData['description']);
                            $body .= "\n\n";
                        }

                        $body .= $translator->getMessage('MAIL_SEND_TO', $moderators);
                        $body .= "\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('MAIL_USER_FREE_LINK') . "\n";
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

                        $message->setBody($body, 'text/plain');

                        $this->get('mailer')->send($message);

                        $translator->setSelectedLanguage($savedLanguage);
                    }
                }

                // inform user if request required no authorization
                if ($newUser->isUser()) {
                    /** @var \cs_list $moderatorList */
                    $moderatorList = $roomItem->getModeratorList();

                    $contactModerator = $moderatorList->getFirst();

                    $modFullName = "";
                    $modEmail = "";

                    if ($contactModerator) {
                        $modFullName = $contactModerator->getFullname();
                        $modEmail = $contactModerator->getEmail();
                    }

                    $translator = $legacyEnvironment->getTranslationObject();
                    $translator->setEmailTextArray($roomItem->getEmailTextArray());
                    $translator->setContext('project');

                    $savedLanguage = $translator->getSelectedLanguage();

                    $language = $roomItem->getLanguage();
                    if ($language == 'user') {
                        $language = $newUser->getLanguage();
                        if ($language == 'browser') {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                        $userId = 'XXX ' . $translator->getMessage('COMMON_DATASECURITY');
                    } else {
                        $userId = $newUser->getUserID();
                    }

                    $translator->setSelectedLanguage($language);

                    $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $roomItem->getTitle());

                    $body  = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date("Y-m-d H:i:s")), $translator->getTimeInLang(date("Y-m-d H:i:s")));
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $newUser->getFullname());
                    $body .= "\n\n";
                    if ($roomItem->isCommunityRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GR', $userId, $roomItem->getTitle());
                    } else if ($roomItem->isProjectRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_PR', $userId, $roomItem->getTitle());
                    } else if ($roomItem->isGroupRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GP', $userId, $roomItem->getTitle());
                    }
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $modFullName, $roomItem->getTitle());
                    $body .= "\n\n";
                    $body .= $this->generateUrl('app_room_home', [
                        'roomId' => $roomItem->getItemID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $message = (new \Swift_Message())
                        ->setSubject($subject)
                        ->setBody($body, 'text/plain')
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setTo([$newUser->getEmail()]);

                    if ($modEmail != '' && $modFullName != '') {
                        $message->setReplyTo([$modEmail => $modFullName]);
                    }

                    $this->get('mailer')->send($message);

                    $translator->setSelectedLanguage($savedLanguage);
                }

                $event = new UserJoinedRoomEvent($newUser, $roomItem);
                $eventDispatcher->dispatch($event);
            }

            // redirect to detail page
            if ($roomItem->isGroupRoom()) {
                $route = $this->redirectToRoute('app_group_detail', [
                    'roomId' => $roomId,
                    'itemId' => $roomItem->getLinkedGroupItemID(),
                ]);
            }
            else {
                if ($roomManager->getItem($roomId)) {
                    // in community-context -> redirect to detail view in project rubric.
                    $route = $this->redirectToRoute('app_project_detail', [
                        'roomId' => $roomId,
                        'itemId' => $itemId,
                    ]);
                } else {
                    // in private room context -> redirect to detail view of all rooms list.
                    $route = $this->redirectToRoute('app_room_detail', [
                        'roomId' => $roomId,
                        'itemId' => $itemId,
                    ]);
                }
            }
            return $route;
        }
        
        return [
            'form' => $form->createView(),
            'agbText' => $agbText,
            'title' => html_entity_decode($roomItem->getTitle()),
        ];
    }
    
}
