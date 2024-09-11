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

namespace App\Cron\Tasks;

use App\Account\AccountManager;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_annotations_manager;
use cs_context_item;
use cs_dates_manager;
use cs_environment;
use cs_list;
use cs_manager;
use cs_privateroom_item;
use cs_user_manager;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CronNewsletter implements CronTaskInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly PortalRepository $portalRepository,
        private readonly RouterInterface $router,
        private readonly Mailer $mailer,
        private readonly AccountManager $accountManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $portals = $this->portalRepository->findAll();
        foreach ($portals as $portal) {
            $this->legacyEnvironment->setCurrentContextID($portal->getId());

            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoomManager->reset();
            $privateRoomManager->setContextLimit($portal->getId());
            $privateRoomManager->setActiveLimit();
            $privateRoomManager->select();
            $privateRooms = $privateRoomManager->get();

            foreach ($privateRooms as $privateRoom) {
                /** @var cs_privateroom_item $privateRoom */
                if (!$privateRoom->isOpen() || !$privateRoom->isPrivateRoomNewsletterActive()) {
                    continue;
                }

                $frequency = $privateRoom->getPrivateRoomNewsletterActivity();
                $send = 'daily' === $frequency;

                if ('weekly' === $frequency) {
                    // send weekly newsletter on monday
                    $send = 1 == date('N');
                }

                if ($send) {
                    $this->sendNewsletter($privateRoom);
                }
            }
        }
    }

    public function getSummary(): string
    {
        return 'Send newsletter';
    }

    /**
     * Prepare and send the newsletters. It describes the activity in the last seven days.
     */
    private function sendNewsletter(cs_privateroom_item $privateRoom)
    {
        // get user in room
        $user = $privateRoom->getOwnerUserItem();
        if ($user) {
            $translator = $this->legacyEnvironment->getTranslationObject();

            $body = '';
            $mail_sequence = $privateRoom->getPrivateRoomNewsletterActivity();

            // email
            $id = $user->getItemID();

            $portal = $privateRoom->getContextItem();
            $room_manager = $this->legacyEnvironment->getRoomManager();
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

            $translator->setRubricTranslationArray($privateRoom->getRubricTranslationArray());

            foreach ($roomList as $roomItem) {
                /** @var cs_context_item $roomItem */
                $rubrics = [];

                $conf = $roomItem->getHomeConf();
                if (!empty($conf)) {
                    $rubrics = explode(',', (string) $conf);
                }

                $numRubrics = count($rubrics);

                $homeUrl = $this->router->generate('app_room_home', [
                    'roomId' => $roomItem->getItemID(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

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

                /** @var cs_annotations_manager $annotation_manager */
                $annotation_manager = $this->legacyEnvironment->getManager('annotation');
                $annotation_manager->setContextLimit($roomItem->getItemID());

                if ('daily' == $mail_sequence) {
                    $annotation_manager->setAgeLimit(1);
                } else {
                    $annotation_manager->setAgeLimit(7);
                }

                $annotation_manager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
                $annotation_manager->select();
                $annotation_list = $annotation_manager->get();
                $annotationsInNewsletter = [];

                for ($i = 0; $i < $numRubrics; ++$i) {
                    $rubric_array = explode('_', $rubrics[$i]);
                    if ('none' != $rubric_array[1]) {
                        $rubric_manager = $this->legacyEnvironment->getManager($rubric_array[0]);
                        $rubric_manager->reset();
                        $rubric_manager->setContextLimit($roomItem->getItemID());

                        // NOTE: we only include newly created users (i.e., when they have requested room membership)
                        if ('daily' == $mail_sequence) {
                            if ($rubric_manager instanceof cs_user_manager) {
                                $rubric_manager->setExistenceLimit(1);
                            } else {
                                $rubric_manager->setAgeLimit(1);
                            }
                        } else {
                            if ($rubric_manager instanceof cs_user_manager) {
                                $rubric_manager->setExistenceLimit(7);
                            } else {
                                $rubric_manager->setAgeLimit(7);
                            }
                        }

                        if ($rubric_manager instanceof cs_dates_manager) {
                            $rubric_manager->setDateModeLimit(2);
                        }
                        if ($rubric_manager instanceof cs_user_manager) {
                            $rubric_manager->setUserLimit();
                        }

                        $rubric_manager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
                        $rubric_manager->select();
                        $rubric_list = $rubric_manager->get();

                        $user_manager = $this->legacyEnvironment->getUserManager();
                        $user_manager->resetLimits();
                        $user_manager->setUserIDLimit($user->getUserID());
                        $user_manager->setAuthSourceLimit($user->getAuthSource());
                        $user_manager->setContextLimit($roomItem->getItemID());
                        $user_manager->select();
                        $user_list = $user_manager->get();

                        $count_entries = 0;
                        if (isset($user_list) && $user_list->isNotEmpty() && 1 == $user_list->getCount()) {
                            $ref_user = $user_list->getFirst();
                            if (isset($ref_user) && $ref_user->getItemID() > 0) {
                                $temp_body = '';

                                $readerManager = $this->legacyEnvironment->getReaderManager();
                                foreach ($rubric_list as $rubric_item) {
                                    $noticed = $readerManager->getLatestReaderForUserByID($rubric_item->getItemID(),
                                        $ref_user->getItemID());
                                    if (empty($noticed)) {
                                        $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_NEW').']</span>';
                                    } elseif ($noticed['read_date'] < $rubric_item->getModificationDate()) {
                                        $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_CHANGED').']</span>';
                                    } else {
                                        $info_text = '';
                                    }

                                    $annotation_count = 0;
                                    foreach ($annotation_list as $annotation_item) {
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

                                        $detailUrl = $this->router->generate('app_'.$mod.'_detail', $urlParameters,
                                            UrlGeneratorInterface::ABSOLUTE_URL);

                                        $ahref_curl = '<a href="'.$detailUrl.'">'.$title.'</a>';

                                        $temp_body .= BR.'&nbsp;&nbsp;- '.$ahref_curl;
                                    }
                                }
                            }
                        }

                        $tempMessage = match (mb_strtoupper($rubric_array[0], 'UTF-8')) {
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
                            $listUrl = $this->router->generate('app_'.$rubric_array[0].'_list', [
                                'roomId' => $roomItem->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);

                            $ahref_curl = '<a href="'.$listUrl.'">'.$tempMessage.'</a>';
                            $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                            $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_SINGLE_ENTRY').')</span>';
                        } elseif ($count_entries > 1) {
                            $listUrl = $this->router->generate('app_'.$rubric_array[0].'_list', [
                                'roomId' => $roomItem->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);

                            $ahref_curl = '<a href="'.$listUrl.'">'.$tempMessage.'</a>';
                            $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                            $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_ENTRIES').')</span>';
                        }
                        if (!empty($body2) and !empty($temp_body)) {
                            $body2 .= $temp_body.BRLF.LF;
                        }
                    }
                }

                $annotation_item = $annotation_list->getFirst();
                $annotationsStillToSend = [];
                while ($annotation_item) {
                    if (!in_array($annotation_item, $annotationsInNewsletter)) {
                        $annotationsStillToSend[] = $annotation_item;
                    }
                    $annotation_item = $annotation_list->getNext();
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

                        $annotatedItemUrl = $this->router->generate('app_'.$annotatedItem->getItemType().'_detail',
                            [
                                'roomId' => $roomItem->getItemID(),
                                'itemId' => $annotatedItem->getItemId(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);

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
}
