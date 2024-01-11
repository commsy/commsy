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

namespace App\RoomFeed;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use cs_community_item;
use cs_environment;
use cs_item;
use cs_project_item;
use cs_user_item;
use cs_userroom_item;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;

class RoomFeedGenerator
{
    private readonly cs_environment $legacyEnvironment;

    /**
     * @var array limits
     */
    private array $limits = [];

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomService $roomService,
        private readonly ItemService $itemService,
        private readonly Security $security
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param int $size Number of items to get
     * @param int|null $lastId The item id of the last received article item
     */
    public function getDashboardFeedList(int $size, ?int $lastId): array
    {
        $currentUser = $this->legacyEnvironment->getCurrentUser();
        $contextIds = $this->getAllUserRelatedContexts($currentUser);

        return $this->getFeedList($contextIds, $size, $lastId);
    }

    /**
     * @param int $roomId The room id
     * @param int $size   Number of items to get
     * @param int $lastId The item id of the last received article item
     */
    public function getRoomFeedList($roomId, $size, $lastId): array
    {
        return $this->getFeedList([$roomId], $size, $lastId);
    }

    /**
     * @param int[] $contextIds The context ids
     * @param int   $size       Number of items to get
     * @param int   $lastId     The item id of the last received article item
     */
    private function getFeedList($contextIds, $size, $lastId): array
    {
        /**
         * Because each room has a different rubric configuration group context ids by rubric. That way we only
         * need one query for each rubric later on.
         */
        $contextIdsByRubric = [];
        foreach ($contextIds as $contextId) {
            $roomRubrics = $this->roomService->getVisibleRoomRubrics($contextId);
            foreach ($roomRubrics as $roomRubric) {
                // exclude users as it clutters the feed with unimportant entries
                if ('user' === $roomRubric) {
                    continue;
                }

                $contextIdsByRubric[$roomRubric][] = $contextId;
            }
        }

        /**
         * $lastId will hold the latest item id we already fetched from the database and displayed at the end of the
         * current feed list. If it is present, we get the corresponding item from the item table and use it's
         * modification date to get all item ids for each rubric to exclude them for the next entries.
         */
        $excludedIds = [];
        if ($lastId) {
            $lastFeedItem = $this->itemService->getTypedItem($lastId);
            if ($lastFeedItem) {
                $lastModificationDate = ($lastFeedItem instanceof cs_user_item) ?
                    DateTime::createFromFormat('Y-m-d H:i:s', $lastFeedItem->getCreationDate()) :
                    DateTime::createFromFormat('Y-m-d H:i:s', $lastFeedItem->getModificationDate());

                $previousFeedEntries = [];
                foreach ($contextIdsByRubric as $rubric => $contextIds) {
                    $rubricManager = $this->legacyEnvironment->getManager($rubric);
                    if (method_exists($rubricManager, 'getNewestItems')) {
                        $previousFeedEntries = array_merge($previousFeedEntries, $rubricManager->getNewestItems($contextIds, $this->limits, 0, $lastModificationDate)->to_array());
                    }
                }

                /*
                 * $previousFeedEntries will now hold at least all entries we already displayed (up to the lastModificationDate)
                 * and is sorted the same way across all rubrics we do later on when getting the next items.
                 */

                usort($previousFeedEntries, $this->sortByModificationDate(...));

                /*
                 * Iterate over all previous feed entries and break as soon as we found the last item id. Excluded ids
                 * will be stored grouped by rubric for better handling.
                 */
                foreach ($previousFeedEntries as $previousFeedEntry) {
                    $type = $previousFeedEntry->getType();

                    // consider sub-label type
                    if ('label' == $type) {
                        $type = $previousFeedEntry->getLabelType();
                    }

                    $excludedIds[$type][] = $previousFeedEntry->getItemId();

                    if ($previousFeedEntry->getItemId() == $lastId) {
                        break;
                    }
                }
            }
        }

        /**
         * Query for the next $size items and take excluded ids into account.
         */
        $feedList = [];
        foreach ($contextIdsByRubric as $rubric => $contextIds) {
            $rubricManager = $this->legacyEnvironment->getManager($rubric);
            if (method_exists($rubricManager, 'getNewestItems')) {
                if (isset($excludedIds[$rubric])) {
                    $feedList = array_merge($feedList, $rubricManager->getNewestItems($contextIds, $this->limits, $size, null, $excludedIds[$rubric])->to_array());
                } else {
                    $feedList = array_merge($feedList, $rubricManager->getNewestItems($contextIds, $this->limits, $size)->to_array());
                }
            }
        }

        usort($feedList, $this->sortByModificationDate(...));

        return array_slice($feedList, 0, $size);
    }

    /**
     * Sets filter conditions to apply when fetching items.
     */
    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->limits['buzzword'] = $itemId;
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->limits['categories'] = $categories;
                }
            }
        }
    }

    /**
     * Comparison Callback for sorting two items by modification date.
     *
     * @param cs_item $a first item
     * @param cs_item $b second item
     *
     * @return int compare result
     */
    private function sortByModificationDate(cs_item $a, cs_item $b): int
    {
        $isUserA = CS_USER_TYPE === $a->getItemType();
        $isUserB = CS_USER_TYPE === $a->getItemType();

        $modDateA = ($isUserA) ? $a->getCreationDate() : $a->getModificationDate();
        $modDateB = ($isUserB) ? $b->getCreationDate() : $b->getModificationDate();

        return $modDateB <=> $modDateA;
    }

    /**
     * Returns all context ids we are interested in when building the feed list for the dashboard.
     *
     * @param cs_user_item $currentUser The current user
     *
     * @return int[] Context ids
     */
    private function getAllUserRelatedContexts(cs_user_item $currentUser): array
    {
        $roomIds = [];

        $projectRooms = $currentUser->getUserRelatedProjectList(false);
        foreach ($projectRooms as $projectRoom) {
            /* @var cs_project_item $projectRoom */
            $roomIds[] = $projectRoom->getItemID();
        }

        $userRooms = $currentUser->getRelatedUserroomsList(false);
        foreach ($userRooms as $userRoom) {
            /* @var cs_userroom_item $userRoom */
            $roomIds[] = $userRoom->getItemID();
        }

        $communityRooms = $currentUser->getUserRelatedCommunityList(false);
        foreach ($communityRooms as $communityRoom) {
            /* @var cs_community_item $communityRoom */
            $roomIds[] = $communityRoom->getItemID();
        }

        /**
         * TODO: This post-processing filters user items, that are not activated yet. This should be refactored to avoid
         * querying for a user list for each room.
         */
        /** @var Account $account */
        $account = $this->security->getUser();
        $authSource = $account->getAuthSource();

        $userManager = $this->legacyEnvironment->getUserManager();
        $roomIdsActivated = [];

        foreach ($roomIds as $roomId) {
            $userList = $userManager->getUserArrayByUserAndRoomIDLimit($currentUser->getUserId(), [$roomId], $authSource->getId());
            if (!empty($userList)) {
                $roomIdsActivated[] = $roomId;
            }
        }

        return $roomIdsActivated;
    }
}
