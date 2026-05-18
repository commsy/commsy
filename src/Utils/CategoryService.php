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

use App\Services\CurrentContextResolver;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use App\Tag\TagDeleter;
use cs_tag_item;

class CategoryService
{
    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly TagDeleter $tagDeleter,
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly CurrentContextResolver $currentContextResolver,
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
     * Soft-deletes the tag and, recursively, every descendant in the
     * `tag2tag` tree. Cascade lives in {@see TagDeleter::softDelete()}.
     */
    public function removeTag($tagId, $roomId): void
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);

        $this->tagDeleter->softDelete((int) $tagId, $deleterId);
    }

    /**
     * Combines two tags into a new merged tag (title "t1/t2", union of linked
     * items, children of both re-parented under it).
     *
     * Parity: cs_tag2tag_manager::combine() + non-recursive
     * cs_tag_manager::delete($id, false).
     */
    public function combineTags(int $tagIdOne, int $tagIdTwo, int $roomId): void
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $tagManager = $environment->getTagManager();
        $tag2tagManager = $environment->getTag2TagManager();

        // If tag one is a successor of tag two, swap so the father-id lookup
        // walks up from the deeper tag's parent (legacy parity).
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

        $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);

        // Non-recursive: children survive to be re-parented below.
        // Parity: tag_manager->delete($id, false).
        $this->tagDeleter->softDeleteWithoutChildren($tagIdOne, $deleterId);
        $this->tagDeleter->softDeleteWithoutChildren($tagIdTwo, $deleterId);

        unset($itemOne, $itemTwo);

        $mergedLinkedIds = array_unique(array_merge($linkedIdsOne, $linkedIdsTwo));

        $newTag = $tagManager->getNewItem();
        $newTag->setTitle($titleOne.'/'.$titleTwo);
        $newTag->setContextID($this->currentContextResolver->getContextId() ?? 0);
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
