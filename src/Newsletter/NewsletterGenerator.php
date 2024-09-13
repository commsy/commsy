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

use App\Enum\ReaderStatus;
use App\Repository\ReaderRepository;
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
        private readonly ReaderRepository $readerRepository,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Returns the list of rooms for the user of the given private room.
     */
    private function getRoomListForUserWithPrivatRoom(cs_privateroom_item $privateRoom): array
    {
        $user = $privateRoom->getOwnerUserItem();
        $portal = $privateRoom->getContextItem();
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $userroomManager = $this->legacyEnvironment->getUserRoomManager();

        $roomList = $roomManager->getRelatedContextListForUserInt($user->getUserID(),
            $user->getAuthSource(), $portal->getItemID(), true, true);

        $userroomList = $userroomManager->getRelatedUserroomListForUser($user);

        $rooms = array_merge($roomList->to_array(), $userroomList->to_array());

        return array_filter($rooms, function ($roomItem) {
            return (!$roomItem->isPrivateRoom()
                && $roomItem->isOpen()
                && $roomItem->getItemID() > 0
            );
        });
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

            $annotations = $roomItem->getAnnotationsChangedWithinDays($dayLimit);
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

                // new/modified rubric items
                $rubricItemsData = [];
                foreach ($rubricItemList as $rubricItem) {
                    $rubricItemID = $rubricItem->getItemID();

                    // is the item new or modified?
                    $itemReader = $this->readerRepository->findOneByItemIdAndUserId($rubricItemID, $refUser->getItemID());
                    $itemNoticedStatus = !$itemReader
                        ? ReaderStatus::STATUS_NEW->value
                        : ($itemReader->getReadDate() < $rubricItem->getModificationDate()
                            ? ReaderStatus::STATUS_CHANGED->value
                            : ReaderStatus::STATUS_SEEN->value
                        );

                    // are there any new annotations for the new or modified items?
                    $annotationCount = 0;
                    foreach ($annotations as $annotationItem) {
                        $annotationReader = $this->readerRepository->findOneByItemIdAndUserId(
                            $annotationItem->getItemID(),
                            $refUser->getItemID()
                        );

                        if (!$annotationReader) {
                            $linkedItem = $annotationItem->getLinkedItem();
                            if ($linkedItem->getItemID() == $rubricItemID) {
                                ++$annotationCount;
                                $annotationsInNewsletter[] = $annotationItem;
                            }
                        }
                    }

                    if ($itemNoticedStatus !== ReaderStatus::STATUS_SEEN->value || $annotationCount > 0) {
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
            $annotationsStillToSend = [];
            foreach ($annotations as $annotationItem) {
                if (!in_array($annotationItem, $annotationsInNewsletter)) {
                    $annotationReader = $this->readerRepository->findOneByItemIdAndUserId(
                        $annotationItem->getItemID(),
                        $refUser->getItemID()
                    );
                    if (!$annotationReader) {
                        $annotationsStillToSend[] = $annotationItem;
                    }
                }
            }
            $roomData['annotationsStillToSend'] = $annotationsStillToSend;

            if (!empty($roomData['rubrics'])) {
                $roomsData[$roomItem->getItemID()] = $roomData;
            }
        }

        $newsletterData['rooms'] = $roomsData;

        return $newsletterData;
    }
}
