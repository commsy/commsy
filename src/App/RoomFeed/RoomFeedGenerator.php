<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 08.03.18
 * Time: 15:23
 */

namespace App\RoomFeed;


use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use Symfony\Component\Form\Form;

class RoomFeedGenerator
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var RoomService
     */
    private $roomService;

    /**
     * @var ItemService
     */
    private $itemService;

    /**
     * @var array limits
     */
    private $limits = [];

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->itemService = $itemService;

    }

    /**
     * @param int $size Number of items to get
     * @param int $lastId The item id of the last received article item
     *
     * @return array List of items
     */
    public function getDashboardFeedList($size, $lastId)
    {
        $currentUser = $this->legacyEnvironment->getCurrentUser();
        $contextIds = $this->getAllUserRelatedContexts($currentUser);

        return $this->getFeedList($contextIds, $size, $lastId);
    }

    /**
     * @param int $roomId The room id
     * @param int $size Number of items to get
     * @param int $lastId The item id of the last received article item
     *
     * @return array List of items
     */
    public function getRoomFeedList($roomId, $size, $lastId)
    {
        return $this->getFeedList([$roomId], $size, $lastId);
    }

    /**
     * @param int[] $contextIds The context ids
     * @param int $size Number of items to get
     * @param int $lastId The item id of the last received article item
     *
     * @return array List of items
     */
    private function getFeedList($contextIds, $size, $lastId)
    {
        /**
         * Because each room has a different rubric configuration group context ids by rubric. That way we only
         * need one query for each rubric later on.
         */
        $contextIdsByRubric = [];
        foreach ($contextIds as $contextId) {
            $roomRubrics = $this->getRoomRubrics($contextId);
            foreach ($roomRubrics as $roomRubric) {
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
                $lastModificationDate = \DateTime::createFromFormat('Y-m-d H:i:s', $lastFeedItem->getModificationDate());

                $previousFeedEntries = [];
                foreach ($contextIdsByRubric as $rubric => $contextIds) {
                    $rubricManager = $this->legacyEnvironment->getManager($rubric);
                    if (method_exists($rubricManager, 'getNewestItems')) {
                        $previousFeedEntries = array_merge($previousFeedEntries, $rubricManager->getNewestItems($contextIds, $this->limits, 0, $lastModificationDate)->to_array());
                    }
                }

                /**
                 * $previousFeedEntries will now hold at least all entries we already displayed (up to the lastModificationDate)
                 * and is sorted the same way across all rubrics we do later on when getting the next items.
                 */

                usort($previousFeedEntries, [$this, 'sortByModificationDate']);

                /**
                 * Iterate over all previous feed entries and break as soon as we found the last item id. Excluded ids
                 * will be stored grouped by rubric for better handling.
                 */
                foreach ($previousFeedEntries as $previousFeedEntry) {
                    $type = $previousFeedEntry->getType();

                    // consider sub-label type
                    if ($type == 'label') {
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
         * Query for the next $size items and take excluded ids into account
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

        usort($feedList, [$this, 'sortByModificationDate']);

        $feedList = array_slice($feedList, 0, $size);

        return $feedList;
    }

    /**
     * Sets filter conditions to apply when fetching items
     *
     * @param Form $filterForm
     */
    public function setFilterConditions(Form $filterForm)
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
     * @param int $roomId The id of the room
     * @return string[] List of rubrics needed for querying
     */
    private function getRoomRubrics($roomId)
    {
        $rubrics = [];

        foreach ($this->roomService->getRubricInformation($roomId, true) as $rubric) {
            list($rubricName, $modifier) = explode('_', $rubric);
            if (strcmp($modifier, 'hide') != 0) {
                $rubrics[] = $rubricName;
            }
        }

        return $rubrics;
    }

    /**
     * Comparison Callback for sorting two items by modification date.
     *
     * @param \cs_item $a first item
     * @param \cs_item $b second item
     * @return int compare result
     */
    private function sortByModificationDate(\cs_item $a, \cs_item $b)
    {
        $modDateA = $a->getModificationDate();
        $modDateB = $b->getModificationDate();

        if ($modDateA == $modDateB) {
            return 0;
        }

        return $modDateA < $modDateB ? 1 : -1;
    }

    /**
     * Returns all context ids we are interested in when building the feed list for the dashboard.
     *
     * @param \cs_user_item $currentUser The current user
     *
     * @return int[] Context ids
     */
    private function getAllUserRelatedContexts(\cs_user_item $currentUser)
    {
        $roomIds = [];

        $projectRooms = $currentUser->getUserRelatedProjectList();
        if (isset($projectRooms) && $projectRooms->isNotEmpty()) {
            $projectRoom = $projectRooms->getFirst();

            while ($projectRoom) {
                $roomIds[] = $projectRoom->getItemId();

                $projectRoom = $projectRooms->getNext();
            }
        }

//        $grouproom_list = $currentUser->getUserRelatedGroupList();
//        if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
//            $grouproom_list->reverse();
//            $grouproom_item = $grouproom_list->getFirst();
//            while ($grouproom_item) {
//                $project_room_id = $grouproom_item->getLinkedProjectItemID();
//                if ( in_array($project_room_id,$roomIds) ) {
//                    $room_id_array_temp = array();
//                    foreach ($roomIds as $value) {
//                        $room_id_array_temp[] = $value;
//                        if ( $value == $project_room_id) {
//                            $room_id_array_temp[] = $grouproom_item->getItemID();
//                        }
//                    }
//                    $roomIds = $room_id_array_temp;
//                }
//                $grouproom_item = $grouproom_list->getNext();
//            }
//        }

        $communityRooms = $currentUser->getUserRelatedCommunityList();
        if (isset($communityRooms) && $communityRooms->isNotEmpty()) {
            $communityRoom = $communityRooms->getFirst();

            while ($communityRoom) {
                $roomIds[] = $communityRoom->getItemId();

                $communityRoom = $communityRooms->getNext();
            }
        }

        /**
         * TODO: This post-processing filters user items, that are not activated yet. This should be refactored to avoid
         * querying for a user list for each room.
         */
        $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
        $authSource = $authSourceManager->getItem($currentUser->getAuthSource());

        $userManager = $this->legacyEnvironment->getUserManager();
        $roomIdsActivated = [];

        foreach ($roomIds as $roomId) {
            $userList = $userManager->getUserArrayByUserAndRoomIDLimit($currentUser->getUserId(), [$roomId], $authSource->getItemId());
            if (!empty($userList)) {
                $roomIdsActivated[] = $roomId;
            }
        }

        return $roomIdsActivated;
    }
}