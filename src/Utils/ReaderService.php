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

namespace App\Utils;

use App\Services\LegacyEnvironment;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class ReaderService
{
    /**
     * Read status constant that identifies a "new" item, i.e. an item that hasn't been seen before.
     *
     * @var string
     */
    public const READ_STATUS_NEW = 'new';

    /**
     * Read status constant that identifies a "changed" item, i.e. an item with unread changes.
     *
     * @var string
     */
    public const READ_STATUS_CHANGED = 'changed';

    /**
     * Read status constant that identifies an "unread" item, i.e. an item that either hasn't been
     * seen before (`READ_STATUS_NEW`) -OR- which has unread changes (`READ_STATUS_CHANGED`).
     *
     * @var string
     */
    public const READ_STATUS_UNREAD = 'unread';

    // TODO: most CommSy code currently uses an empty string ('') instead of READ_STATUS_SEEN/'seen'
    /**
     * Read status constant that identifies a "seen" item, i.e. an item that has been read before.
     *
     * @var string
     */
    public const READ_STATUS_SEEN = 'seen';

    /**
     * Read status constant that identifies an item that has a "new annotation" which hasn't been seen before.
     *
     * @var string
     */
    public const READ_STATUS_NEW_ANNOTATION = 'new_annotation';

    /**
     * Read status constant that identifies an item that has a "changed annotation", i.e. an annotation with unread changes.
     *
     * @var string
     */
    public const READ_STATUS_CHANGED_ANNOTATION = 'changed_annotation';

    private \Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter $readStatusCache;
    private $readerManager;

    public function __construct(private LegacyEnvironment $legacyEnvironment, private ItemService $itemService)
    {
        $this->readerManager = $this->legacyEnvironment->getEnvironment()->getReaderManager();

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
        $itemList = null;
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
        } elseif (!$item->isNotActivated() and $reader['read_date'] < $item->getModificationDate()) {
            $return = self::READ_STATUS_CHANGED;
        }

        if ('' == $return) {
            // annotations
            $annotation_list = $item->getAnnotationList();
            $anno_item = $annotation_list->getFirst();
            $new = false;
            $changed = false;
            $date = '0000-00-00 00:00:00';
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
            } elseif ($changed) {
                $return = self::READ_STATUS_CHANGED_ANNOTATION;
            }
        }

        $itemType = $item->getItemType();

        if ('' == $return and ('material' == $itemType or 'discussion' == $itemType or 'todo' == $itemType)) {
            // sub-items
            if ('material' == $itemType) {
                $materialManager = $this->legacyEnvironment->getEnvironment()->getMaterialManager();
                $material = $materialManager->getItem($item->getItemID());
                $itemList = $material->getSectionList();
            }
            if ('discussion' == $itemType) {
                $discussionManager = $this->legacyEnvironment->getEnvironment()->getDiscussionManager();
                $discussion = $discussionManager->getItem($item->getItemID());
                $itemList = $discussion->getAllArticles();
            }
            if ('todo' == $itemType) {
                $todoManager = $this->legacyEnvironment->getEnvironment()->getToDoManager();
                $todo = $todoManager->getItem($item->getItemID());
                $itemList = $todo->getStepItemList();
            }

            $readerItem = $itemList->getFirst();
            $new = false;
            $changed = false;
            $date = '0000-00-00 00:00:00';
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
            } elseif ($changed) {
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
     * Note that this method will also return IDs for items with new/changed annotations if "changed" has been
     * specified as read status.
     *
     * @param \cs_item[]    $items      array of items from which IDs for all items matching `$readStatus` shall be returned
     * @param string        $readStatus the read status for which IDs of matching items shall be returned
     * @param \cs_user_item $user       the user whose read status shall be used
     *
     * @return int[]
     */
    public function itemIdsForReadStatus(array $items, string $readStatus, \cs_user_item $user): array
    {
        if (empty($items) || !$readStatus || !$user) {
            return [];
        }

        $itemIds = [];

        foreach ($items as $item) {
            if ($item) {
                // we cache the user's read status for a given item which greatly speeds up the look-up process
                $cachedReadStatus = $this->cachedReadStatusForItem($item, $user);

                // NOTES:
                // - instead of READ_STATUS_SEEN, getChangeStatusForUserByID() currently returns an empty string ('')
                // - READ_STATUS_UNREAD comprises all items matching either READ_STATUS_NEW or READ_STATUS_CHANGED
                // - we treat READ_STATUS_NEW_ANNOTATION and READ_STATUS_CHANGED_ANNOTATION like READ_STATUS_CHANGED
                if (self::READ_STATUS_NEW_ANNOTATION === $cachedReadStatus || self::READ_STATUS_CHANGED_ANNOTATION === $cachedReadStatus) {
                    $cachedReadStatus = self::READ_STATUS_CHANGED;
                }
                if ($cachedReadStatus === $readStatus
                    || '' === $cachedReadStatus && self::READ_STATUS_SEEN === $readStatus
                    || self::READ_STATUS_NEW === $cachedReadStatus && self::READ_STATUS_UNREAD === $readStatus
                    || self::READ_STATUS_CHANGED === $cachedReadStatus && self::READ_STATUS_UNREAD === $readStatus) {
                    $itemId = $item->getItemId();
                    $itemIds[] = $itemId;
                }
            }
        }

        return $itemIds;
    }

    /**
     * Returns the cached read status for the given item and user.
     * If there's no cached read status for the given item, this method calculates its read status and caches it.
     * The cached status will be invalidated:
     * - when the item gets saved (the `CommsyEditEvent::SAVE` will trigger `invalidateCachedReadStatusForItem()`)
     * - when the item gets marked as read  (the `ReadStatusPreChangeEvent` will trigger `invalidateCachedReadStatusForItem()`)
     * - or, otherwise, after 12 hours.
     *
     * @param \cs_item      $item the item whose cached read status shall be returned
     * @param \cs_user_item $user the user whose read status shall be used (defaults to the current user if not given)
     */
    public function cachedReadStatusForItem(\cs_item $item, \cs_user_item $user = null): string
    {
        if (!$item) {
            return '';
        }

        if (!$user) {
            $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
            if ($currentUser) {
                $user = $currentUser;
            }
            if (!$user) {
                return '';
            }
        }

        $itemId = $item->getItemId();
        $userId = $user->getItemID();
        $cachedItemKey = $userId.'_'.$itemId;

        $cachedReadStatus = $this->readStatusCache->get($cachedItemKey, function (ItemInterface $cachedItem) use ($item, $itemId, $user, $userId) {
            // NOTE: this function will only get executed if there's no valid cache value for `$cachedItemKey`
            $itemType = $item->getItemType();
            $cachedItem->tag(['user_'.$userId, 'item_'.$itemType.'_'.$itemId]);
            $cachedItem->expiresAfter(60 * 60 * 12);

            $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
            if ($relatedUser) {
                $itemReadStatus = $this->getChangeStatusForUserByID($itemId, $relatedUser->getItemId());

                return $itemReadStatus;
            }

            return ''; // TODO: shouldn't we better return null here (if that's possible) since '' currently equals 'seen'
        });

        return $cachedReadStatus;
    }

    /**
     * Invalidates the cached read status for the given item.
     *
     * @param \cs_item $item the item whose cached read status shall be invalidated
     */
    public function invalidateCachedReadStatusForItem(\cs_item $item): void
    {
        if (!$item) {
            return;
        }

        $itemId = $item->getItemId();
        $itemType = $item->getItemType();

        $this->readStatusCache->invalidateTags(['item_'.$itemType.'_'.$itemId]);
    }
}
