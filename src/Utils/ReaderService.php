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

use App\Entity\Reader;
use App\Enum\ReaderStatus;
use App\Event\ReadStatusPreChangeEvent;
use App\ReadStatus\ReadCountDescription;
use App\Repository\ReaderRepository;
use App\Services\LegacyEnvironment;
use cs_annotation_item;
use cs_item;
use cs_user_item;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class ReaderService
{
    private FilesystemTagAwareAdapter $readStatusCache;

    public function __construct(
        private LegacyEnvironment        $legacyEnvironment,
        private ItemService              $itemService,
        private ReaderRepository         $readerRepository,
        private EventDispatcherInterface $eventDispatcher,
        private EntityManagerInterface   $entityManager,
    ) {
        $this->readStatusCache = new FilesystemTagAwareAdapter();
    }

    public function getLatestReader(int $itemId): ?Reader
    {
        $userId = $this->legacyEnvironment->getEnvironment()->getCurrentUser()->getItemID();
        return $this->readerRepository->findOneByItemIdAndUserId($itemId, $userId);
    }

    public function getChangeStatusForItems(cs_item ...$items): array
    {
        $readerList = [];
        foreach ($items as $item) {
            $readerList[$item->getItemId()] = $this->getChangeStatus($item);
        }

        return $readerList;
    }

    public function getChangeStatus(cs_item $item, cs_user_item $user = null): string
    {
        $u = $user ?? $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();

        if ($u && $u->isUser()) {
            return $this->cachedReadStatusForItem($item, $u);
        }

        return '';
    }

    /**
     * Returns the IDs of all items among the given items matching the given read status (for the given user).
     * Note that this method will also return IDs for items with new/changed annotations if "changed" has been
     * specified as read status.
     *
     * @param cs_item[]    $items      array of items from which IDs for all items matching `$readStatus` shall be returned
     * @param string        $readStatus the read status for which IDs of matching items shall be returned
     * @param cs_user_item $user       the user whose read status shall be used
     *
     * @return int[]
     */
    public function itemIdsForReadStatus(array $items, string $readStatus, cs_user_item $user): array
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
                // - instead of STATUS_SEEN, getChangeStatusForUserByID() currently returns an empty string ('')
                // - STATUS_UNREAD comprises all items matching either STATUS_NEW or STATUS_CHANGED
                // - we treat STATUS_NEW_ANNOTATION and STATUS_CHANGED_ANNOTATION like STATUS_CHANGED
                if (ReaderStatus::STATUS_NEW_ANNOTATION->value === $cachedReadStatus || ReaderStatus::STATUS_CHANGED_ANNOTATION->value === $cachedReadStatus) {
                    $cachedReadStatus = ReaderStatus::STATUS_CHANGED->value;
                }
                if ($cachedReadStatus === $readStatus
                    || '' === $cachedReadStatus && ReaderStatus::STATUS_SEEN->value === $readStatus
                    || ReaderStatus::STATUS_NEW->value === $cachedReadStatus && ReaderStatus::STATUS_UNREAD->value === $readStatus
                    || ReaderStatus::STATUS_CHANGED->value === $cachedReadStatus && ReaderStatus::STATUS_UNREAD->value === $readStatus) {
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
     * @param cs_item $item the item whose cached read status shall be returned
     * @param cs_user_item|null $user the user whose read status shall be used (defaults to the current user if not given)
     * @throws InvalidArgumentException
     */
    public function cachedReadStatusForItem(cs_item $item, cs_user_item $user = null): string
    {
        $user = $user ?? $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();

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
                return $this->getChangeStatusForUserByID($itemId, $relatedUser->getItemId());
            }

            return ''; // TODO: shouldn't we better return null here (if that's possible) since '' currently equals 'seen'
        });

        return $cachedReadStatus;
    }

    /**
     * Invalidates the cached read status for the given item.
     *
     * @param cs_item $item the item whose cached read status shall be invalidated
     * @throws InvalidArgumentException
     */
    public function invalidateCachedReadStatusForItem(cs_item $item): void
    {
        $itemId = $item->getItemId();
        $itemType = $item->getItemType();

        $this->readStatusCache->invalidateTags(['item_'.$itemType.'_'.$itemId]);
    }

    /**
     * Marks the item with the given item ID & version ID as read by the current user.
     *
     * @param int $itemId Id of the item to be marked as read
     * @param int $versionId Id of the item version to be marked as read
     * @return void
     */
    public function markRead(int $itemId, int $versionId = 0): void
    {
        $this->markItemsAsRead([$itemId], $versionId);
    }

    /**
     * @param cs_item[] $items array of items
     * @param bool $withAnnotations Should related annotations also get marked as read?
     */
    public function markItemsRead(array $items, bool $withAnnotations = true): void
    {
        foreach ($items as $item) {
            $this->markRead($item->getItemID(), $item->getVersionID());

            // annotations
            if ($withAnnotations) {
                foreach ($item->getAnnotationList() as $annotation) {
                    /** @var cs_annotation_item $annotation */
                    $this->markRead($annotation->getItemId());
                }
            }
        }
    }

    public function markItemAsRead(cs_item $item): void
    {
        $reader = $this->getLatestReader($item->getItemID());
        if (!$reader || $reader->getReadDate() <= new DateTime($item->getModificationDate())) {
            $this->markRead($item->getItemID(), $item->getVersionID());
        }
    }

    /**
     * Marks an array of items (of the given version ID) as read by the given users
     * (or the current user in case no user IDs were given).
     *
     * @param int[]      $itemIds   Array of item IDs for items to be marked as read
     * @param int        $versionId ID of the item version (applied to all given items) to be marked as read
     * @param int[]|null $userIds   Optional array of user IDs specifying the users for whom the given items shall
     *                              be marked as read; defaults to null in which case given items will be marked as read for the current user
     */
    public function markItemsAsRead(array $itemIds, int $versionId, array $userIds = null): void
    {
        if (empty($itemIds)) {
            return;
        }

        if (empty($userIds)) {
            $userIds = [$this->legacyEnvironment->getEnvironment()->getCurrentUserID()];
            if (empty($userIds)) {
                return;
            }
        }

        // Delete previous entries
        $query = $this->entityManager->createQuery('
            DELETE FROM App\Entity\Reader r
            WHERE r.itemId IN (:itemIds) AND r.userId IN (:userIds)
        ');
        $query->setParameter('itemIds', $itemIds);
        $query->setParameter('userIds', $userIds);
        $query->execute();

        // Insert
        foreach ($itemIds as $itemId) {
            foreach ($userIds as $userId) {
                $reader = new Reader();
                $reader->setItemId($itemId);
                $reader->setVersionId($versionId);
                $reader->setUserId($userId);

                $this->entityManager->persist($reader);
                $this->eventDispatcher->dispatch(new ReadStatusPreChangeEvent($userId, $itemId, ReaderStatus::STATUS_SEEN));
            }
        }

        // Flush all
        $this->entityManager->flush();
    }

    public function getStatusForItem(cs_item $item): ReaderStatus
    {
        $reader = $this->getLatestReader($item->getItemId());

        return !$reader ? ReaderStatus::STATUS_NEW : ReaderStatus::STATUS_CHANGED;
    }

    public function getReadCountDescriptionForItem(cs_item $item): ReadCountDescription
    {
        // Find all users in the items context
        $userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
        $userManager->setContextLimit($item->getContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $userList = $userManager->get();

        $readers = new ArrayCollection($this->readerRepository->findBy([
            'itemId' => $item->getItemID(),
        ]));

        // Count depending on read status
        $readCount = 0;
        $readSinceModificationCount = 0;
        foreach ($userList as $user) {
            /** @var cs_user_item $user */
            $reader = $readers->findFirst(fn ($key, Reader $reader) => $reader->getUserId() === $user->getItemID());
            if ($reader) {
                $readCount++;

                if ($reader->getReadDate() >= $item->getModificationDate()) {
                    $readSinceModificationCount++;
                }
            }
        }

        return new ReadCountDescription(
            $readCount,
            $readSinceModificationCount,
            $userList->getCount()
        );
    }

    public function deleteAllEntriesInWorkspace(int $workspaceId): void
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->setContextLimit($workspaceId);
        $itemManager->setNoIntervalLimit();
        $itemManager->select();
        $itemIds = $itemManager->get()->getIDArray();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($workspaceId);
        $userManager->select();
        $userIds = $userManager->get()->getIDArray();

        $query = $this->entityManager->createQuery('
            DELETE FROM App\Entity\Reader r
            WHERE r.itemId IN (:itemIds) OR r.userId IN (:userIds)
        ');
        $query->setParameter('itemIds', $itemIds);
        $query->setParameter('userIds', $userIds);
        $query->execute();
    }

    public function mergeAccounts(int $newId, int $oldId): void
    {
        $selectOld = $this->entityManager->createQuery('
            SELECT r FROM App\Entity\Reader r
            WHERE r.userId = :userId
        ');
        $selectOld->setParameter('userId', $oldId);
        $oldEntries = $selectOld->getResult();

        foreach ($oldEntries as $oldEntry) {
            /** @var Reader $oldEntry */
            $selectNew = $this->entityManager->createQuery('
                SELECT r FROM App\Entity\Reader r
                WHERE r.userId = :userId AND r.itemId = :itemId AND r.versionId = :versionId
            ');
            $selectNew->setParameter('userId', $newId);
            $selectNew->setParameter('itemId', $oldEntry->getItemId());
            $selectNew->setParameter('versionId', $oldEntry->getVersionId());
            $newEntry = $selectNew->getOneOrNullResult();

            if (!$newEntry) {
                // Update the old user entry
                $update = $this->entityManager->createQuery('
                    UPDATE App\Entity\Reader r
                    SET r.userId = :newUserId
                    WHERE r.userId = :oldUserId AND r.itemId = :itemId AND r.versionId = :versionId
                ');
                $update->setParameter('newUserId', $newId);
                $update->setParameter('oldUserId', $oldId);
                $update->setParameter('itemId', $oldEntry->getItemId());
                $update->setParameter('versionId', $oldEntry->getVersionId());
                $update->execute();
            } else {
                // The new user has already read this item, so just delete the old entry
                $delete = $this->entityManager->createQuery('
                    DELETE FROM App\Entity\Reader r
                    WHERE r.itemId. = :itemId AND r.versionId = :versionId AND r.userId = :userId
                ');
                $delete->setParameter('itemId', $oldEntry->getItemId());
                $delete->setParameter('versionId', $oldEntry->getVersionId());
                $delete->setParameter('userId', $oldId);
                $delete->execute();
            }
        }
    }

    private function getChangeStatusForUserByID($itemId, $userID): string
    {
        $itemList = null;
        $return = '';

        $item = $this->itemService->getTypedItem($itemId);
        if (!$item) {
            return $return;
        }

        $reader = $this->readerRepository->findOneByItemIdAndUserId($item->getItemID(), $userID);

        if (!$reader) {
            $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
            $itemIsCurrentUser = ($item instanceof cs_user_item && $item->getUserID() === $currentUser->getUserID());
            if (!$itemIsCurrentUser) {
                $return = ReaderStatus::STATUS_NEW->value;
            }
        } elseif (!$item->isNotActivated() and $reader->getReadDate() < $item->getModificationDate()) {
            $return = ReaderStatus::STATUS_CHANGED->value;
        }

        if ('' == $return) {
            // annotations
            $new = false;
            $changed = false;
            $date = '0000-00-00 00:00:00';

            foreach ($item->getAnnotationList() as $anno_item) {
                $reader = $this->readerRepository->findOneByItemIdAndUserId($anno_item->getItemID(), $userID);
                if (!$reader) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $anno_item->getModificationDate();
                    }
                } elseif ($reader->getReadDate() < $anno_item->getModificationDate()) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $anno_item->getModificationDate();
                    }
                }
            }

            if ($new) {
                $return = ReaderStatus::STATUS_NEW_ANNOTATION->value;
            } elseif ($changed) {
                $return = ReaderStatus::STATUS_CHANGED_ANNOTATION->value;
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
                $todoManager = $this->legacyEnvironment->getEnvironment()->getTodosManager();
                $todo = $todoManager->getItem($item->getItemID());
                $itemList = $todo->getStepItemList();
            }

            $new = false;
            $changed = false;
            $date = '0000-00-00 00:00:00';
            foreach ($itemList as $readerItem) {
                $reader = $this->readerRepository->findOneByItemIdAndUserId($readerItem->getItemID(), $userID);
                if (!$reader) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $readerItem->getModificationDate();
                    }
                } elseif ($reader->getReadDate() < $readerItem->getModificationDate()) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $readerItem->getModificationDate();
                    }
                }
            }

            if ($new) {
                $return = ReaderStatus::STATUS_CHANGED->value;
            } elseif ($changed) {
                $return = ReaderStatus::STATUS_CHANGED->value;
            }
        }

        return $return;
    }
}
