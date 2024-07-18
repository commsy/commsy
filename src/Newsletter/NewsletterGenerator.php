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

namespace App\Newsletter;

use App\Account\AccountManager;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use cs_annotations_manager;
use cs_context_item;
use cs_dates_manager;
use cs_environment;
use cs_list;
use cs_manager;
use cs_privateroom_item;
use cs_user_item;
use cs_user_manager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class NewsletterGenerator
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RouterInterface $router,
        private readonly Mailer $mailer,
        private readonly AccountManager $accountManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Returns the list of rooms for the user of the given private room.
     */
    private function getRoomListForUserWithPrivatRoom(cs_privateroom_item $privateRoom): cs_list
    {
        $user = $privateRoom->getOwnerUserItem();
        $id = $user->getItemID();

        $portal = $privateRoom->getContextItem();
        $room_manager = $this->legacyEnvironment->getRoomManager();

        // TODO: remove functionality around customized rooms (as this feature isn't supported anymore)
        $customizedRoomList = $privateRoom->getCustomizedRoomList();
        if (!isset($customizedRoomList)) {
            $customizedRoomList = $room_manager->getRelatedContextListForUserInt($user->getUserID(),
                $user->getAuthSource(), $portal->getItemID(), true, true);
        }

        $roomList = new cs_list();
        foreach ($customizedRoomList as $customizedRoomItem) {
            if (!$customizedRoomItem->isPrivateRoom()
                && $customizedRoomItem->isShownInPrivateRoomHomeByItemID($id)
                && $customizedRoomItem->isOpen()
                && $customizedRoomItem->getItemID() > 0
            ) {
                $roomList->add($customizedRoomItem);
            }
        }

        return $roomList;
    }

    /**
     * Returns an array of configuration strings for the rubrics used by the given room.
     * Returns an empty array if the given room's home configuration couldn't be found.
     */
    private function rubricsForRoom(cs_context_item $roomItem): array
    {
        $rubrics = [];

        $conf = $roomItem->getHomeConf();
        if (!empty($conf)) {
            $rubrics = explode(',', (string) $conf);
        }

        return $rubrics;
    }

    /**
     * Returns an absolute URL for the given route name & parameters.
     */
    private function generateAbsoluteUrl(string $name, array $parameters)
    {
        return $this->router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    // TODO: move these to individual manager classes
    //       e.g., create a basic cs_manager method and overwrite this for cs_dates_manager & cs_user_manager
    private function getRubricItemList(cs_context_item $roomItem, array $rubricConfigArray, int $dayLimit): ?cs_list
    {
        $rubric_manager = $this->legacyEnvironment->getManager($rubricConfigArray[0]);
        $rubric_manager->reset();
        $rubric_manager->setContextLimit($roomItem->getItemID());

        // NOTE: we only include newly created users (i.e., when they have requested room membership)
        if ($rubric_manager instanceof cs_user_manager) {
            $rubric_manager->setExistenceLimit($dayLimit);
        } else {
            $rubric_manager->setAgeLimit($dayLimit);
        }

        if ($rubric_manager instanceof cs_dates_manager) {
            $rubric_manager->setDateModeLimit(2);
        }
        if ($rubric_manager instanceof cs_user_manager) {
            $rubric_manager->setUserLimit();
        }

        $rubric_manager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $rubric_manager->select();

        return $rubric_manager->get();
    }

    private function getAnnotationsList(cs_context_item $roomItem, int $dayLimit): ?cs_list
    {
        /** @var cs_annotations_manager $annotation_manager */
        $annotation_manager = $this->legacyEnvironment->getManager('annotation');
        $annotation_manager->setContextLimit($roomItem->getItemID());

        $annotation_manager->setAgeLimit($dayLimit);

        $annotation_manager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $annotation_manager->select();

        return $annotation_manager->get();
    }

    private function getUserList(cs_user_item $user, cs_context_item $roomItem): ?cs_list
    {
        $user_manager = $this->legacyEnvironment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setUserIDLimit($user->getUserID());
        $user_manager->setAuthSourceLimit($user->getAuthSource());
        $user_manager->setContextLimit($roomItem->getItemID());
        $user_manager->select();

        return $user_manager->get();
    }

    /**
     * Returns a data array with all data required to generate a newsletter message for the user
     * of the given private room. The newsletter describes the activity during the last day or week,
     * depending on the user's frequency setting.
     */
    public function getNewsletterData(cs_privateroom_item $privateRoom): array
    {
        // get user in room
        $user = $privateRoom->getOwnerUserItem();
        if (!$user) {
            return [];
        }

        $newsletterData = [];
        $roomsData = [];

        $translator = $this->legacyEnvironment->getTranslationObject();

        $mailSequence = $privateRoom->getPrivateRoomNewsletterActivity();
        $newsletterData['mailSequence'] = $mailSequence;

        $roomList = $this->getRoomListForUserWithPrivatRoom($privateRoom);
        $translator->setRubricTranslationArray($privateRoom->getRubricTranslationArray());

        // rooms
        foreach ($roomList as $roomItem) {
            $roomData['roomItem'] = $roomItem;

            $roomData['homeUrl'] = $this->generateAbsoluteUrl('app_room_home', [
                'roomId' => $roomItem->getItemID(),
            ]);

            $dayLimit = 'daily' === $mailSequence ? 1 : 7;
            $roomData['pageImpressionsCount'] = $roomItem->getPageImpressionsForNewsletter($dayLimit);
            $roomData['activeMembersCount'] = $roomItem->getActiveMembersForNewsletter($dayLimit);

            $userList = $this->getUserList($user, $roomItem);
            if (isset($userList) && $userList->isNotEmpty() && 1 == $userList->getCount()) {
                $refUser = $userList->getFirst();
            }
            if (!isset($refUser) || !($refUser->getItemID() > 0)) {
                continue;
            }

            $annotationList = $this->getAnnotationsList($roomItem, $dayLimit);
            $annotationsInNewsletter = [];

            // rubrics
            $rubrics = $this->rubricsForRoom($roomItem);
            $numRubrics = count($rubrics);
            $rubricsData = [];

            for ($i = 0; $i < $numRubrics; ++$i) {
                $rubricConfigArray = explode('_', $rubrics[$i]);
                $rubricSpecifier = $rubricConfigArray[0];
                $rubricDisplayStatus = $rubricConfigArray[1];
                if ('none' === $rubricDisplayStatus) {
                    continue;
                }

                $rubricItemList = $this->getRubricItemList($roomItem, $rubricConfigArray, $dayLimit);

                $countEntries = 0;
                $rubricData = [];

                $readerManager = $this->legacyEnvironment->getReaderManager();

                // new/modified rubric items
                $rubricItemsData = [];
                foreach ($rubricItemList as $rubricItem) {
                    $rubricItemID = $rubricItem->getItemID();

                    // is the item new or modified?
                    $noticed = $readerManager->getLatestReaderForUserByID($rubricItemID, $refUser->getItemID());
                    $itemNoticedStatus = empty($noticed)
                        ? 'new'
                        : ($noticed['read_date'] < $rubricItem->getModificationDate()
                            ? 'changed'
                            : 'seen'
                        );

                    // are there any new annotations for the new or modified items?
                    $annotationCount = 0;
                    foreach ($annotationList as $annotationItem) {
                        $annotationNoticed = $readerManager->getLatestReaderForUserByID(
                            $annotationItem->getItemID(),
                            $refUser->getItemID()
                        );

                        if (empty($annotationNoticed)) {
                            $linkedItem = $annotationItem->getLinkedItem();
                            if ($linkedItem->getItemID() == $rubricItemID) {
                                ++$annotationCount;
                                $annotationsInNewsletter[] = $annotationItem;
                            }
                        }
                    }

                    if ($itemNoticedStatus !== 'seen' || $annotationCount > 0) {
                        ++$countEntries;
                        $rubricItemData['item'] = $rubricItem;
                        $rubricItemData['itemNoticedStatus'] = $itemNoticedStatus;
                        $rubricItemData['newAnnotationsCount'] = $annotationCount;
                        $rubricItemsData[$rubricItemID] = $rubricItemData;
                    }
                }

                if ($countEntries > 0) {
                    $rubricData['itemsCount'] = $countEntries;
                    $rubricData['items'] = $rubricItemsData;
                }

                if (!empty($rubricData)) {
                    $rubricsData[$rubricSpecifier] = $rubricData;
                }
            }

            $roomData['rubrics'] = $rubricsData;
            $roomData['annotationsInNewsletter'] = $annotationsInNewsletter;

            // are there any new annotations for unchanged items?
            $annotationItem = $annotationList->getFirst();
            $annotationsStillToSend = [];
            while ($annotationItem) {
                if (!in_array($annotationItem, $annotationsInNewsletter)) {
                    $annotationNoticed = $readerManager->getLatestReaderForUserByID(
                        $annotationItem->getItemID(),
                        $refUser->getItemID()
                    );
                    if (empty($annotationNoticed)) {
                        $annotationsStillToSend[] = $annotationItem;
                    }
                }
                $annotationItem = $annotationList->getNext();
            }
            $roomData['annotationsStillToSend'] = $annotationsStillToSend;

            if (!empty($roomData['rubrics'])) {
                $roomsData[$roomItem->getItemID()] = $roomData;
            }
        }

        $newsletterData['rooms'] = $roomsData;

        return $newsletterData;
    }

    /**
     * Prepare and send the newsletters. It describes the activity during the last day or week.
     */
    public function sendOldNewsletter(cs_privateroom_item $privateRoom)
    {
        // get user in room
        $user = $privateRoom->getOwnerUserItem();
        if (!$user) {
            return;
        }

        $translator = $this->legacyEnvironment->getTranslationObject();

        $mail_sequence = $privateRoom->getPrivateRoomNewsletterActivity();
        $roomList = $this->getRoomListForUserWithPrivatRoom($privateRoom);
        $translator->setRubricTranslationArray($privateRoom->getRubricTranslationArray());

        // email
        $body = '';

        // rooms
        foreach ($roomList as $roomItem) {
            $rubrics = $this->rubricsForRoom($roomItem);
            $numRubrics = count($rubrics);

            $homeUrl = $this->generateAbsoluteUrl('app_room_home', [
                'roomId' => $roomItem->getItemID(),
            ]);

            $title = '<a href="'.$homeUrl.'">'.$roomItem->getTitle().'</a>';
            $body_title = BR.BR.$title.LF;

            if ('daily' == $mail_sequence) {
                $count_total = $roomItem->getPageImpressionsForNewsletter(1);
                $active = $roomItem->getActiveMembersForNewsletter(1);
            } else {
                $count_total = $roomItem->getPageImpressionsForNewsletter(7);
                $active = $roomItem->getActiveMembersForNewsletter(7);
            }

            if (1 == $count_total) {
                $body_title .= '('.$count_total.'&nbsp;'.$translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS_SINGULAR').'; ';
            } else {
                $body_title .= '('.$count_total.'&nbsp;'.$translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS').'; ';
            }

            $body_title .= $translator->getMessage('ACTIVITY_ACTIVE_MEMBERS').': ';
            $body_title .= $active.'):'.BRLF;
            $body2 = '';

            $dayLimit = 'daily' === $mail_sequence ? 1 : 7;
            $annotationList = $this->getAnnotationsList($roomItem, $dayLimit);
            $annotationsInNewsletter = [];

            $userList = $this->getUserList($user, $roomItem);
            if (isset($userList) && $userList->isNotEmpty() && 1 == $userList->getCount()) {
                $ref_user = $userList->getFirst();
            }

            // rubrics
            for ($i = 0; $i < $numRubrics; ++$i) {
                $rubricConfigArray = explode('_', $rubrics[$i]);
                if ('none' != $rubricConfigArray[1]) {
                    $rubricItemList = $this->getRubricItemList($roomItem, $rubricConfigArray, $dayLimit);

                    $count_entries = 0;
                    if (isset($ref_user) && $ref_user->getItemID() > 0) {
                        $temp_body = '';

                        $readerManager = $this->legacyEnvironment->getReaderManager();

                        // new/modified rubric items
                        foreach ($rubricItemList as $rubric_item) {
                            $noticed = $readerManager->getLatestReaderForUserByID($rubric_item->getItemID(),
                                $ref_user->getItemID());
                            if (empty($noticed)) {
                                $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_NEW').']</span>';
                            } elseif ($noticed['read_date'] < $rubric_item->getModificationDate()) {
                                $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_CHANGED').']</span>';
                            } else {
                                $info_text = '';
                            }

                            // new/modified annotations for new/modified items
                            $annotation_count = 0;
                            foreach ($annotationList as $annotation_item) {
                                $annotation_noticed = $readerManager->getLatestReaderForUserByID(
                                    $annotation_item->getItemID(),
                                    $ref_user->getItemID()
                                );

                                if (empty($annotation_noticed)) {
                                    $linked_item = $annotation_item->getLinkedItem();
                                    if ($linked_item->getItemID() == $rubric_item->getItemID()) {
                                        ++$annotation_count;
                                        $annotationsInNewsletter[] = $annotation_item;
                                    }
                                }
                            }

                            if (1 == $annotation_count) {
                                $info_text .= ' <span class="changed">['.$translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
                            } else {
                                if ($annotation_count > 1) {
                                    $info_text .= ' <span class="changed">['.$translator->getMessage('COMMON_NEW_ANNOTATIONS').']</span>';
                                }
                            }

                            if (!empty($info_text)) {
                                ++$count_entries;
                                $params = [];
                                $params['iid'] = $rubric_item->getItemID();
                                $title = '';
                                if ($rubric_item->isA(CS_USER_TYPE)) {
                                    $title .= $this->legacyEnvironment->getTextConverter()->text_as_html_short($rubric_item->getFullname());
                                } else {
                                    $title .= $this->legacyEnvironment->getTextConverter()->text_as_html_short($rubric_item->getTitle());
                                }
                                if ($rubric_item->isA(CS_LABEL_TYPE)) {
                                    $mod = $rubric_item->getLabelType();
                                } else {
                                    $mod = $rubric_item->getType();
                                }

                                $title .= $info_text;

                                $urlParameters = [
                                    'roomId' => $roomItem->getItemID(),
                                    'itemId' => $params['iid'],
                                ];

                                if ('material' == $mod) {
                                    $urlParameters['versionId'] = 0;
                                }

                                $detailUrl = $this->generateAbsoluteUrl('app_'.$mod.'_detail', $urlParameters);

                                $ahref_curl = '<a href="'.$detailUrl.'">'.$title.'</a>';

                                $temp_body .= BR.'&nbsp;&nbsp;- '.$ahref_curl;
                            }
                        }
                    }

                    $tempMessage = match (mb_strtoupper($rubricConfigArray[0], 'UTF-8')) {
                        'ANNOUNCEMENT' => $translator->getMessage('ANNOUNCEMENT_INDEX'),
                        'DATE' => $translator->getMessage('DATES_INDEX'),
                        'DISCUSSION' => $translator->getMessage('DISCUSSION_INDEX'),
                        'GROUP' => $translator->getMessage('GROUP_INDEX'),
                        'INSTITUTION' => $translator->getMessage('INSTITUTION_INDEX'),
                        'MATERIAL' => $translator->getMessage('MATERIAL_INDEX'),
                        'MYROOM' => $translator->getMessage('MYROOM_INDEX'),
                        'PROJECT' => $translator->getMessage('PROJECT_INDEX'),
                        'TODO' => $translator->getMessage('TODO_INDEX'),
                        'TOPIC' => $translator->getMessage('TOPIC_INDEX'),
                        'USER' => $translator->getMessage('USER_INDEX'),
                        'ENTRY' => $translator->getMessage('ENTRY_INDEX'),
                        default => $translator->getMessage('COMMON_MESSAGETAG_ERROR cs_privateroom_item(456) '),
                    };

                    if (1 == $count_entries) {
                        $listUrl = $this->generateAbsoluteUrl('app_'.$rubricConfigArray[0].'_list', [
                            'roomId' => $roomItem->getItemID(),
                        ]);

                        $ahref_curl = '<a href="'.$listUrl.'">'.$tempMessage.'</a>';
                        $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                        $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_SINGLE_ENTRY').')</span>';
                    } elseif ($count_entries > 1) {
                        $listUrl = $this->generateAbsoluteUrl('app_'.$rubricConfigArray[0].'_list', [
                            'roomId' => $roomItem->getItemID(),
                        ]);

                        $ahref_curl = '<a href="'.$listUrl.'">'.$tempMessage.'</a>';
                        $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                        $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_ENTRIES').')</span>';
                    }
                    if (!empty($body2) and !empty($temp_body)) {
                        $body2 .= $temp_body.BRLF.LF;
                    }
                }
            }

            // new/modified annotations for unchanged items
            $annotation_item = $annotationList->getFirst();
            $annotationsStillToSend = [];
            while ($annotation_item) {
                if (!in_array($annotation_item, $annotationsInNewsletter)) {
                    $annotation_noticed = $readerManager->getLatestReaderForUserByID(
                        $annotation_item->getItemID(),
                        $ref_user->getItemID()
                    );
                    if (empty($annotation_noticed)) {
                        $annotationsStillToSend[] = $annotation_item;
                    }
                }
                $annotation_item = $annotationList->getNext();
            }

            $annotation_info_text = '';
            if (1 == count($annotationsStillToSend)) {
                $annotation_info_text .= '&nbsp;&nbsp;<span class="changed">'.$translator->getMessage('COMMON_NEW_ANNOTATION_ADDITIONAL').':</span>';
            } else {
                if (count($annotationsStillToSend) > 1) {
                    $annotation_info_text .= '&nbsp;&nbsp;<span class="changed">'.$translator->getMessage('COMMON_NEW_ANNOTATIONS_ADDITIONAL').':</span>';
                }
            }

            if (!empty($annotation_info_text)) {
                $temp_body_annotation = BRLF.LF.$annotation_info_text;
                foreach ($annotationsStillToSend as $annotationStillToSend) {
                    $annotatedItem = $annotationStillToSend->getLinkedItem();
                    $annotationTitle = '';
                    if ('' != $annotationStillToSend->getTitle()) {
                        $annotationTitle = ' ('.$annotationStillToSend->getTitle().')';
                    }

                    $annotatedItemUrl = $this->generateAbsoluteUrl('app_'.$annotatedItem->getItemType().'_detail',
                        [
                            'roomId' => $roomItem->getItemID(),
                            'itemId' => $annotatedItem->getItemId(),
                        ]);

                    $ahref_curl = '<a href="'.$annotatedItemUrl.'">'.$annotatedItem->getTitle().'</a>'.$annotationTitle;
                    $temp_body_annotation .= BR.'&nbsp;&nbsp;&nbsp;&nbsp;- '.$ahref_curl;
                }
                $body2 .= $temp_body_annotation.BRLF.LF;
            }

            $body .= $body_title;
            if (!empty($body2)) {
                $body2 .= BRLF;
            } else {
                $body2 .= '&nbsp;&nbsp;'.$translator->getMessage('COMMON_NO_NEW_ENTRIES').BRLF;
            }
            $body .= $body2;
        }

        if (empty($body)) {
            $translator->getMessage('COMMON_NO_NEW_ENTRIES').LF;
        }
        $body .= LF;
        $portal = $privateRoom->getContextItem();
        $portal_title = '';
        if (isset($portal)) {
            $portal_title = $portal->getTitle();
        }
        if ('daily' == $mail_sequence) {
            $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_DAILY',
                $portal_title).LF.LF.$body;
        } else {
            $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_WEEKLY',
                $portal_title).LF.LF.$body;
        }

        $body .= BRLF.BR.'-----------------------------'.BRLF.LF.$translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_FOOTER');

        $from = $translator->getMessage('SYSTEM_MAIL_MESSAGE', $portal_title);
        if ('daily' == $mail_sequence) {
            $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_DAILY').': '.$portal_title;
        } else {
            $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_WEEKLY').': '.$portal_title;
        }

        // send email
        $account = $this->accountManager->getAccount($user, $portal->getItemId());
        if ($account) {
            $this->mailer->sendRaw(
                $subject,
                $body,
                RecipientFactory::createFromAccount($account),
                $from
            );
        }
    }
}
