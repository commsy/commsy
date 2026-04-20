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
use App\Tag\TagDeleter;
use cs_tag_item;

class CategoryService
{
    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly TagDeleter $tagDeleter,
    ) {
    }

    public function getTag($tagId)
    {
        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();

        return $tagManager->getItem($tagId);
    }

    public function updateTag($tagId, $newTitle): void
    {
        $tagItem = $this->getTag($tagId);
        $tagItem->setTitle($newTitle);
        $tagItem->save();
    }

    public function getTags($roomId): array
    {
        // Reset cache
        $tag2tagManager = $this->legacyEnvironment->getEnvironment()->getTag2TagManager();
        $tag2tagManager->resetCachedChildrenIdArray();

        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();
        $tagManager->resetCache();

        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();
        $rootItem = $tagManager->getRootTagItemFor($roomId);

        return $rootItem ? $this->buildTagArray($rootItem) : [];
    }

    /**
     * Creates and returns a new category (aka tag) with the given title, context and parent.
     *
     * @param null $parentTagId
     */
    public function addTag($title, $roomId, $parentTagId = null): cs_tag_item
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $currentUserItem = $environment->getCurrentUserItem();
        $tagManager = $environment->getTagManager();

        if (!$parentTagId) {
            $rootTagItem = $tagManager->getRootTagItemFor($roomId);
            if (!$rootTagItem) {
                $tagManager->createRootTagItemFor($roomId);
                $tagManager->forceSQL();
                $rootTagItem = $tagManager->getRootTagItemFor($roomId);
            }
            $parentTagId = $rootTagItem->getItemID();
        }

        $parentTagItem = $tagManager->getItem($parentTagId);

        $tagItem = $tagManager->getNewItem();
        $tagItem->setTitle($title);
        $tagItem->setContextID($roomId);
        $tagItem->setCreatorItem($currentUserItem);
        $tagItem->setCreationDate(date('Y-m-d H:i:s'));
        $tagItem->setPosition($parentTagId, $parentTagItem->getChildrenList()->getCount() + 1);

        $tagItem->save();

        return $tagItem;
    }

    /**
     * Soft-deletes the tag (and, recursively, every descendant tag in the
     * `tag2tag` tree).
     *
     * Thin wrapper: the actual cascade (tag + link_items + tag2tag + items
     * twin, recursive) lives in {@see TagDeleter::softDelete()}. This
     * method only resolves the deleter id from the legacy environment and
     * hands off.
     */
    public function removeTag($tagId, $roomId): void
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $deleterId = (int) ($environment->getCurrentUserItem()?->getItemID() ?: 0);

        $this->tagDeleter->softDelete((int) $tagId, $deleterId);
    }

    /**
     * Combines two tags into a new merged tag, preserving parity with the
     * retired legacy `cs_tag2tag_manager::combine()`.
     *
     * Behaviour:
     *   1. Decide the delete order via `tag2tag_manager::isASuccessorOfB` (the
     *      successor is soft-deleted first so its pivot row is gone before we
     *      read the ancestor's father).
     *   2. Read both tag titles and their linked item ids.
     *   3. Collect children of both tags (for re-parenting under the new tag).
     *   4. Non-recursive soft-delete of both old tag rows via
     *      {@see TagDeleter::softDeleteWithoutChildren()} — parity with the
     *      legacy `tag_manager->delete($id, false)` path. Children stay alive
     *      so they can be re-parented under the merged tag.
     *   5. Create the new merged tag (title = "t1/t2", context, creator,
     *      creation_date, linked items = union of both) under the father of
     *      the first tag (after the swap).
     *   6. Re-parent all children of both old tags under the new merged tag.
     *
     * Replaces `cs_tag2tag_manager::combine($id1, $id2, $fatherId)` plus the
     * non-recursive `cs_tag_manager::delete($id, false)` path it relied on.
     */
    public function combineTags(int $tagIdOne, int $tagIdTwo, int $roomId): void
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $tagManager = $environment->getTagManager();
        $tag2tagManager = $environment->getTag2TagManager();

        // Mirror the legacy controller: if tag one is a successor of tag two,
        // swap them so the father-id lookup below walks up from the deeper
        // tag's parent.
        if ($tag2tagManager->isASuccessorOfB($tagIdOne, $tagIdTwo)) {
            [$tagIdOne, $tagIdTwo] = [$tagIdTwo, $tagIdOne];
        }

        $fatherId = (int) $tag2tagManager->getFatherItemID($tagIdOne);

        $itemOne = $tagManager->getItem($tagIdOne);
        $itemTwo = $tagManager->getItem($tagIdTwo);

        $titleOne = $itemOne->getTitle();
        $titleTwo = $itemTwo->getTitle();

        $linkedIdsOne = $itemOne->getAllLinkedItemIDArray();
        $linkedIdsTwo = $itemTwo->getAllLinkedItemIDArray();

        $childrenIdsOne = $tag2tagManager->getChildrenItemIDArray($tagIdOne);
        $childrenIdsTwo = $tag2tagManager->getChildrenItemIDArray($tagIdTwo);

        $deleterId = (int) ($environment->getCurrentUserItem()?->getItemID() ?: 0);

        // Non-recursive soft-delete of both old tags (legacy parity with
        // tag_manager->delete($id, false)). Children rows survive so we can
        // re-parent them under the new merged tag below.
        $this->tagDeleter->softDeleteWithoutChildren($tagIdOne, $deleterId);
        $this->tagDeleter->softDeleteWithoutChildren($tagIdTwo, $deleterId);

        unset($itemOne, $itemTwo);

        // Create the new merged tag.
        $mergedLinkedIds = array_unique(array_merge($linkedIdsOne, $linkedIdsTwo));

        $newTag = $tagManager->getNewItem();
        $newTag->setTitle($titleOne.'/'.$titleTwo);
        $newTag->setContextID($environment->getCurrentContextID());
        $newTag->setCreatorItem($environment->getCurrentUserItem());
        $newTag->setCreationDate(getCurrentDateTimeInMySQL());
        $newTag->setLinkedItemsByIDArray($mergedLinkedIds);
        $newTag->setPosition($fatherId, $tag2tagManager->countChildren($fatherId));
        $newTag->save();

        // Re-parent children of both old tags under the new merged tag.
        $newId = (int) $newTag->getItemID();
        $count = 1;
        foreach (array_merge($childrenIdsOne, $childrenIdsTwo) as $childId) {
            $child = $tagManager->getItem($childId);
            $child->setPosition($newId, $count);
            $child->save();
            unset($child);
            ++$count;
        }
    }

    public function updateStructure($structure, $roomId): void
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $tagManager = $environment->getTagManager();
        $rootTagItem = $tagManager->getRootTagItemFor($roomId);

        $this->updateTree($structure, $rootTagItem, $tagManager);
    }

    private function updateTree($structure, $rootItem, $tagManager): void
    {
        foreach ($structure as $position => $tagInformation) {
            // persist new position
            $tagItem = $tagManager->getItem($tagInformation['itemId']);
            $tagItem->setPosition($rootItem->getItemId(), $position + 1);
            $tagItem->save();

            if (!empty($tagInformation['children'])) {
                $this->updateTree($tagInformation['children'], $tagItem, $tagManager);
            }
        }
    }

    private function buildTagArray(cs_tag_item $item, int $level = 0): array
    {
        $return = [];
        ++$level;

        foreach ($item->getChildrenList() as $item) {
            $return[] = [
                'title' => $item->getTitle(),
                'item_id' => $item->getItemID(),
                'level' => $level,
                'children' => $this->buildTagArray($item, $level),
            ];
        }

        return $return;
    }
}
