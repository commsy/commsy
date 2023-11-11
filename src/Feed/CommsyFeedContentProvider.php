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

namespace App\Feed;

use App\Hash\HashManager;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_manager;
use cs_privateroom_item;
use DateTime;
use Debril\RssAtomBundle\Exception\FeedException\FeedNotFoundException;
use Debril\RssAtomBundle\Provider\FeedProviderInterface;
use FeedIo\Feed;
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommsyFeedContentProvider implements FeedProviderInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly TranslatorInterface $translator,
        private readonly FeedCreatorFactory $feedCreatorFactory,
        private readonly HashManager $hashManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @throws FeedNotFoundException
     */
    public function getFeed(Request $request): FeedInterface
    {
        $contextId = $request->attributes->get('contextId');
        $this->legacyEnvironment->setCurrentContextID($contextId);
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($this->isGranted($currentContextItem, $request)) {
            $isGuestAccess = true;
            if ($request->query->has('hid')) {
                $isGuestAccess = false;
            }

            $this->feedCreatorFactory->setGuestAccess($isGuestAccess);

            $feed = new Feed();
            $feed->setLastModified($this->getLastModified());
            $feed->setTitle($this->getTitle($currentContextItem));
            $feed->setDescription($this->getDescription($currentContextItem));
            $feed->setLink($request->getSchemeAndHttpHost().$request->getBaseUrl());

            $items = $this->getItems($currentContextItem);

            foreach ($items as $item) {
                $feedItem = $this->feedCreatorFactory->createItem($item);
                if ($feedItem) {
                    $feed->add($feedItem);
                }
            }

            return $feed;
        }

        throw new FeedNotFoundException();
    }

    private function isGranted($currentContextItem, Request $request): bool
    {
        if ($currentContextItem->isOpenForGuests()) {
            return true;
        }

        if (!$currentContextItem->isPortal() && !$currentContextItem->isServer()) {
            if (!$currentContextItem->isLocked()) {
                if ($request->query->has('hid')) {
                    $hash = $request->query->get('hid');

                    if ($this->hashManager->isRSSHashValid($hash, $currentContextItem)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getLastModified(): DateTime
    {
        $itemManager = $this->legacyEnvironment->getItemManager();

        $itemManager->setIntervalLimit(1);
        $itemManager->setDeleteLimit(true);

        $result = $itemManager->_performQuery();
        $modificationDate = $result[0]['modification_date'];

        return new DateTime($modificationDate);
    }

    private function getTitle($currentContextItem): string
    {
        if ($currentContextItem->isPrivateRoom()) {
            $currentPortalItem = $this->legacyEnvironment->getCurrentPortalItem();
            $title = $currentPortalItem->getTitle();

            $ownerUserItem = $currentContextItem->getOwnerUserItem();
            $ownerFullName = $ownerUserItem->getFullName();
            if (!empty($ownerFullName)) {
                $title .= ': '.$ownerFullName;
            }
        } else {
            $title = $currentContextItem->getTitle();
        }

        return $title.' (RSS)';
    }

    private function getDescription($currentContextItem)
    {
        $description = "The RSS-feed keeps you up to date with everything that's new in workspace %room_title%.";

        return $this->translator->trans($description, [
            '%room_title%' => $this->getTitle($currentContextItem),
        ], 'rss');
    }

    private function getTypes($contextItem)
    {
        $types = ['annotation'];

        if ($contextItem->withRubric('user')) {
            $types[] = 'user';
        }

        if ($contextItem->withRubric('discussion')) {
            $types[] = 'discussion';
            $types[] = 'discarticle';
        }

        if ($contextItem->withRubric('material')) {
            $types[] = 'material';
            $types[] = 'section';
        }

        if ($contextItem->withRubric('announcement')) {
            $types[] = 'announcement';
        }

        if ($contextItem->withRubric('date')) {
            $types[] = 'date';
        }

        if ($contextItem->withRubric('todo')) {
            $types[] = 'todo';
            $types[] = 'step';
        }

        if ($contextItem->withRubric('group') || $contextItem->withRubric('institution') || $contextItem->withRubric('topic')) {
            $types[] = 'label';
        }

        return $types;
    }

    private function getItems($contextItem)
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $itemManager->resetLimits();

        $itemManager->setIntervalLimit(10);

        // Using the activated entries filter here seems not sufficient, since future modification dates
        // are only stored in their corresponding type tables.
        // This will require later filtering for now.
        $itemManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

        if ($contextItem->isPrivateRoom()) {
            /** @var cs_privateroom_item $contextItem */
            $ownerUserItem = $contextItem->getOwnerUserItem();

            $projectList = $ownerUserItem->getUserRelatedProjectList();
            $communityList = $ownerUserItem->getUserRelatedCommunityList();
            $groupRoomList = $ownerUserItem->getUserRelatedGroupList();

            $fullList = $projectList;
            $fullList->addList($communityList);
            $fullList->addList($groupRoomList);

            $roomIds = $fullList->getIDArray();

            $itemManager->setContextArrayLimit($roomIds);
        } else {
            $itemManager->setTypeArrayLimit($this->getTypes($contextItem));
        }

        return $itemManager->_performQuery();
    }
}
