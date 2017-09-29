<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\ContextRequestType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Filter\ProjectFilterType;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;

class ContextController extends Controller
{    
    /**
     * @Route("/room/{roomId}/context")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
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
     */
    public function requestAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                
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

            if ($form->get('request')->isClicked()) {
                $formData = $form->getData();

                // At this point we can assume that the user has accepted agb and
                // provided the correct code if necessary (or provided no code at all).
                // We can now build a new user item and set the appropriate status

                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
                $privateRoomUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();

                if ($privateRoomUserItem) {
                    $newUser = $privateRoomUserItem->cloneData();
                    $newPicture = $privateRoomUserItem->getPicture();
                } else {
                    $newUser = $currentUserItem->cloneData();
                    $newPicture = $currentUserItem->getPicture();
                }

                $newUser->setContextID($roomItem->getItemID());

                if (!empty($newPicture)) {
                    $values = explode('_', $newPicture);
                    $values[0] = 'cid' . $newUser->getContextID();

                    $newPictureName = implode('_', $values);

                    $discManager = $legacyEnvironment->getDiscManager();
                    $discManager->copyImageFromRoomToRoom($newPicture, $newUser->getContextID());
                    $newUser->setPicture($newPictureName);
                }

                if ($formData['description']) {
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

                    // link user with group "all"
                    $groupManager = $legacyEnvironment->getLabelManager();
                    $groupManager->setExactNameLimit('ALL');
                    $groupManager->setContextLimit($roomItem->getItemID());
                    $groupManager->select();
                    $groupList = $groupManager->get();
                    $group = $groupList->getFirst();

                    if ($group) {
                        $group->addMember($newUser);
                    }
                }

                if ($roomItem->getAGBStatus()) {
                    $newUser->setAGBAcceptance();
                }

                // check if user id already exists
                $userTestItem = $roomItem->getUserByUserID($newUser->getUserID(), $newUser->getAuthSource());
                if (!$userTestItem && !$newUser->isReallyGuest() && !$newUser->isRoot()) {
                    $newUser->save();
                    $newUser->setCreatorID2ItemID();

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
                    $message = \Swift_Message::newInstance()
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setReplyTo([$newUser->getEmail() => $newUser->getFullName()]);

                    $userManager = $legacyEnvironment->getUserManager();
                    $userManager->resetLimits();
                    $userManager->setModeratorLimit();
                    $userManager->setContextLimit($roomItem->getItemID());
                    $userManager->select();

                    $moderatorList = $userManager->get();
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
                        if (!$roomItem->isGroupRoom()) {
                            $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        } else {
                            $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        }
                        $body .= "\n\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                        } else {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                        }
                        $body .= "\n\n";

                        if ($formData['description']) {
                            $body .= $translator->getMessage('MAIL_COMMENT_BY', $newUser->getFullname(), $formData['description']);
                            $body .= "\n\n";
                        }

                        $body .= $translator->getMessage('MAIL_SEND_TO', $moderators);
                        $body .= "\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('MAIL_USER_FREE_LINK') . "\n";
                            $body .= $this->generateUrl('commsy_user_list', [
                                'roomId' => $roomItem->getItemID(),
                                'user_filter' => [
                                    'user_status' => 1,
                                ],
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                        } else {
                            $body .= $this->generateUrl('commsy_room_home', [
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
                    $moderatorList = $roomItem->getModeratorList();
                    $contactModerator = $moderatorList->getFirst();

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
                    $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contactModerator->getFullname(), $roomItem->getTitle());
                    $body .= "\n\n";
                    $body .= $this->generateUrl('commsy_room_home', [
                        'roomId' => $roomItem->getItemID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setBody($body, 'text/plain')
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setReplyTo([$contactModerator->getEmail() => $contactModerator->getFullName()])
                        ->setTo([$newUser->getEmail()]);

                    $this->get('mailer')->send($message);

                    $translator->setSelectedLanguage($savedLanguage);
                }
            }

            // redirect to detail page
            $route = "";
            if ($roomItem->isGroupRoom()) {
                $route = $this->redirectToRoute('commsy_group_detail', [
                    'roomId' => $roomId,
                    'itemId' => $roomItem->getLinkedGroupItemID(),
                ]);
            }
            else {
                if ($roomManager->getItem($roomId)) {
                    // in community-context -> redirect to detail view in project rubric.
                    $route = $this->redirectToRoute('commsy_project_detail', [
                        'roomId' => $roomId,
                        'itemId' => $itemId,
                    ]);
                } else {
                    // in private room context -> redirect to detail view of all rooms list.
                    $route = $this->redirectToRoute('commsy_room_detail', [
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
            'title' => $roomItem->getTitle(),
        ];
    }
    
}
