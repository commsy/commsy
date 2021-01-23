<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class ReaderService
{
    /**
     * Read status constant that identifies a "new" item, i.e. an item that hasn't been seen before.
     * @var string
     */
    public const READ_STATUS_NEW = 'new';

    /**
     * Read status constant that identifies a "changed" item, i.e. an item with unread changes.
     * @var string
     */
    public const READ_STATUS_CHANGED = 'changed';

    // TODO: most CommSy code currently uses an empty string ('') instead of READ_STATUS_SEEN/'seen'
    /**
     * Read status constant that identifies a "seen" item, i.e. an item that has been read before.
     * @var string
     */
    public const READ_STATUS_SEEN = 'seen';

    /**
     * Read status constant that identifies an item that has a "new annotation" which hasn't been seen before.
     * @var string
     */
    public const READ_STATUS_NEW_ANNOTATION = 'new_annotation';

    /**
     * Read status constant that identifies an item that has a "changed annotation", i.e. an annotation with unread changes.
     * @var string
     */
    public const READ_STATUS_CHANGED_ANNOTATION = 'changed_annotation';

    private $readStatusCache;

    private $legacyEnvironment;
    private $readerManager;
    private $itemService;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->readerManager = $this->legacyEnvironment->getEnvironment()->getReaderManager();
        $this->itemService = $itemService;

        $this->readStatusCache = new FilesystemTagAwareAdapter();
    }

    public function getLatestReader($itemId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReader($itemId);
    }

    public function getChangeStatus($itemId)
    {
        $current_user = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
        if ($current_user->isUser()) {
            return $this->getChangeStatusForUserByID($itemId, $current_user->getItemID());
        }
        return '';
    }

    public function getChangeStatusForUserByID($itemId, $userID)
    {
        $return = '';

        $item = $this->itemService->getTypedItem($itemId);
        if (!$item) {
            return $return;
        }

        $readerManager = $this->readerManager;
        $reader = $readerManager->getLatestReaderForUserByID($itemId, $userID);
        if (empty($reader)) {
            $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
            $itemIsCurrentUser = ($item instanceof \cs_user_item && $item->getUserID() === $currentUser->getUserID());
            if (!$itemIsCurrentUser) {
                $return = self::READ_STATUS_NEW;
            }
        } else if (!$item->isNotActivated() and $reader['read_date'] < $item->getModificationDate()) {
            $return = self::READ_STATUS_CHANGED;
        }

        if ($return == '') {

            // annotations
            $annotation_list = $item->getAnnotationList();
            $anno_item = $annotation_list->getFirst();
            $new = false;
            $changed = false;
            $date = "0000-00-00 00:00:00";
            while ($anno_item) {
                $reader = $readerManager->getLatestReaderForUserByID($anno_item->getItemID(), $userID);
                if (empty($reader)) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $anno_item->getModificationDate();
                    }
                } elseif ($reader['read_date'] < $anno_item->getModificationDate()) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $anno_item->getModificationDate();
                    }
                }
                $anno_item = $annotation_list->getNext();
            }

            if ($new) {
                $return = self::READ_STATUS_NEW_ANNOTATION;
            } else if ($changed) {
                $return = self::READ_STATUS_CHANGED_ANNOTATION;
            }
        }

        $itemType = $item->getItemType();

        if ($return == '' and ($itemType == 'material' or $itemType == 'discussion' or $itemType == 'todo')) {

            // sub-items
            if ($itemType == 'material') {
                $materialManager = $this->legacyEnvironment->getEnvironment()->getMaterialManager();
                $material = $materialManager->getItem($item->getItemID());
                $itemList = $material->getSectionList();
            }
            if ($itemType == 'discussion') {
                $discussionManager = $this->legacyEnvironment->getEnvironment()->getDiscussionManager();
                $discussion = $discussionManager->getItem($item->getItemID());
                $itemList = $discussion->getAllArticles();
            }
            if ($itemType == 'todo') {
                $todoManager = $this->legacyEnvironment->getEnvironment()->getToDoManager();
                $todo = $todoManager->getItem($item->getItemID());
                $itemList = $todo->getStepItemList();
            }

            $readerItem = $itemList->getFirst();
            $new = false;
            $changed = false;
            $date = "0000-00-00 00:00:00";
            while ($readerItem) {
                $reader = $readerManager->getLatestReaderForUserByID($readerItem->getItemID(), $userID);
                if (empty($reader)) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $readerItem->getModificationDate();
                    }
                } elseif ($reader['read_date'] < $readerItem->getModificationDate()) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $readerItem->getModificationDate();
                    }
                }
                $readerItem = $itemList->getNext();
            }

            if ($new) {
                $return = self::READ_STATUS_CHANGED;
            } else if ($changed) {
                $return = self::READ_STATUS_CHANGED;
            }
        }

        return $return;
    }

    public function getLatestReaderForUserByID($itemId, $userId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReaderForUserByID($itemId, $userId);
    }

    /**
     * Returns the IDs of all items among the given items matching the given read status (for the given user).
     * Note that this method will also return IDs for items with new/changed annotations if "new"/"changed" has been
     * specified as read status.
     * @param \cs_item[] $items array of items from which IDs for all items matching `$readStatus` shall be returned
     * @param string $readStatus the read status for which IDs of matching items shall be returned
     * @param \cs_item $user the user whose read status shall be used
     * @return integer[]
     */
    public function itemIdsForReadStatus(array $items, string $readStatus, \cs_user_item $user): array
    {
        if (empty($items) || !$readStatus || !$user) {
            return [];
        }

        $userId = $user->getItemID();
        $itemIds = [];

        foreach ($items as $item) {
            if ($item) {
                $itemId = $item->getItemId();
                $cachedItemKey = $userId . '_' . $itemId;

                // we cache the user's read status for a given item which speeds up the look-up; the cached status
                // will be invalidated after 12 hours, or when the item gets saved again (the `CommsyEditEvent::SAVE`
                // event will trigger invalidateCachedReadStatusForItemId())
                $cachedReadStatus = $this->readStatusCache->get($cachedItemKey, function (ItemInterface $cachedItem) use ($item, $itemId, $user, $userId) {
                    // NOTE: this function will only get executed if there's no valid cache value for `$cachedItemKey`
                    $itemType = $item->getItemType();
                    $cachedItem->tag(['user_' . $userId, 'item_' . $itemType . '_' . $itemId]);
                    $cachedItem->expiresAfter(60*60*12);

                    $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                    if ($relatedUser) {
                        $itemReadStatus = $this->getChangeStatusForUserByID($itemId, $relatedUser->getItemId());

                        return $itemReadStatus;
                    }
                    return ''; // TODO: shouldn't we better return null here (if that's possible) since '' currently equals 'seen'
                });

                // NOTE: instead of READ_STATUS_SEEN, getChangeStatusForUserByID() currently returns an empty string ('');
                // also, we treat READ_STATUS_NEW_ANNOTATION like READ_STATUS_NEW, and READ_STATUS_CHANGED_ANNOTATION
                // like READ_STATUS_CHANGED
                if ($cachedReadStatus === '' && $readStatus === ReaderService::READ_STATUS_SEEN
                    || strpos($cachedReadStatus, $readStatus) === 0) {
                    $itemIds[] = $itemId;
                }
            }
        }

        return $itemIds;
    }

    /**
     * Invalidates the cached read status for the given item.
     * @param \cs_item $item the ID of the item whose cached read status shall be invalidated
     */
    public function invalidateCachedReadStatusForItem($item): void
    {
        if (!$item) {
            return;
        }

        $itemId = $item->getItemId();
        $itemType = $item->getItemType();

        $this->readStatusCache->invalidateTags(['item_' . $itemType . '_' . $itemId]);
    }
}
