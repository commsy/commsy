<?php

namespace App\Feed;

use App\Services\LegacyEnvironment;
use cs_environment;
use Debril\RssAtomBundle\Exception\FeedException\FeedNotFoundException;
use Debril\RssAtomBundle\Provider\FeedProviderInterface;
use FeedIo\Feed;
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommsyFeedContentProvider implements FeedProviderInterface
{
    private cs_environment $legacyEnvironment;
    private TranslatorInterface $translator;
    private FeedCreatorFactory $feedCreatorFactory;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        TranslatorInterface $translator,
        FeedCreatorFactory $feedCreatorFactory
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->feedCreatorFactory = $feedCreatorFactory;
    }

    /**
     * @param Request $request
     *
     * @return FeedInterface
     * @throws FeedNotFoundException
     *
     */
    public function getFeed(Request $request): FeedInterface
    {
        $contextId = $request->attributes->get('contextId');
        $this->legacyEnvironment->setCurrentContextID($contextId);
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        if ($this->isGranted($currentContextItem, $request)) {
            $userItem = null;

            $isGuestAccess = true;
            if ($request->query->has('hid')) {
                $hash = $request->query->get('hid');
                $userItem = $currentContextItem->getUserByRSSHash($hash);
                $isGuestAccess = false;
            }

            $this->feedCreatorFactory->setGuestAccess($isGuestAccess);

            $feed = new Feed();
            $feed->setLastModified($this->getLastModified());
            $feed->setTitle($this->getTitle($currentContextItem));
            $feed->setDescription($this->getDescription($currentContextItem));
            $feed->setLink($request->getSchemeAndHttpHost() . $request->getBaseUrl());

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

                    $hashManager = $this->legacyEnvironment->getHashManager();
                    if ($hashManager->isRSSHashValid($hash, $currentContextItem)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getLastModified(): \DateTime
    {
        $itemManager = $this->legacyEnvironment->getItemManager();

        $itemManager->setIntervalLimit(1);
        $itemManager->setDeleteLimit(true);

        $result = $itemManager->_performQuery();
        $modificationDate = $result[0]['modification_date'];

        return new \DateTime($modificationDate);
    }

    private function getTitle($currentContextItem): string
    {
        if ($currentContextItem->isPrivateRoom()) {
            $currentPortalItem = $this->legacyEnvironment->getCurrentPortalItem();
            $title = $currentPortalItem->getTitle();

            $ownerUserItem = $currentContextItem->getOwnerUserItem();
            $ownerFullName = $ownerUserItem->getFullName();
            if (!empty($ownerFullName)) {
                $title .= ': ' . $ownerFullName;
            }
        } else {
            $title = $currentContextItem->getTitle();
        }

        return $title . ' (RSS)';
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
        $itemManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);

        if ($contextItem->isPrivateRoom()) {
            $ownerUserItem = $contextItem->getOwnerUserItem();

            $roomIds = [];

            $projectList = $ownerUserItem->getUserRelatedProjectList();
            $communityList = $ownerUserItem->getUserRelatedCommunityList();
            $groupRoomList = $ownerUserItem->getUserRelatedGroupList();

            $fullList = $projectList;
            $fullList = $fullList->addList($communityList);
            $fullList = $fullList->addList($groupRoomList);

            $roomItem = $fullList->getFirst();
            while ($roomItem) {
                $roomIds[] = $roomItem->getItemID();

                $roomItem = $fullList->getNext();
            }

            $itemManager->setContextArrayLimit($roomIds);
        } else {
            $itemManager->setTypeArrayLimit($this->getTypes($contextItem));
        }

        return $itemManager->_performQuery();
    }
}