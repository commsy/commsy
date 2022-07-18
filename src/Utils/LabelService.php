<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_buzzword_item;
use cs_buzzword_manager;
use cs_environment;
use cs_item;
use cs_labels_manager;
use cs_tag_item;

class LabelService
{
    /** @var cs_environment $legacyEnvironment */
    private cs_environment $legacyEnvironment;

    /** @var cs_labels_manager $labelManager */
    private cs_labels_manager $labelManager;

    /** @var ItemService $itemService */
    private ItemService $itemService;

    /** @var CategoryService $categoryService */
    private CategoryService $categoryService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        CategoryService $categoryService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->labelManager = $this->legacyEnvironment->getLabelManager();
        $this->itemService = $itemService;
        $this->categoryService = $categoryService;
    }

    public function getLabel($itemId)
    {
        $label = $this->labelManager->getItem($itemId);
        return $label;
    }

    /**
     * Creates and returns a new hashtag with the given name and context.
     *
     * @param string $hashtagName
     * @param int $contextId
     * @return \cs_label_item
     */
    public function getNewHashtag(string $hashtagName, int $contextId): \cs_label_item
    {
        $hashtag = $this->labelManager->getNewItem();

        $hashtag->setLabelType('buzzword');
        $hashtag->setContextID($contextId);
        $hashtag->setCreatorItem($this->legacyEnvironment->getCurrentUserItem());
        $hashtag->setName($hashtagName);

        $hashtag->save();

        return $hashtag;
    }

    /**
     * Adds the categories with the given IDs to the items with the given IDs.
     *
     * @param int[] $categoryIds list of IDs for categories that shall be added to the items referenced by $itemIds
     * @param int[] $itemIds list of IDs for items that shall be tagged with the categories referenced by $categoryIds
     * @param int $contextId the ID of the room containing the specified categories and items
     */
    public function addCategoriesById(array $categoryIds, array $itemIds, int $contextId)
    {
        if (empty($categoryIds) || empty($itemIds)) {
            return;
        }

        foreach ($itemIds as $itemId) {
            $item = $this->itemService->getTypedItem($itemId);
            $itemCategoryIds = $this->getLinkedCategoryIds($item);
            $itemCategoryIds = array_unique(array_merge($itemCategoryIds, $categoryIds));

            $item->setTagListByID($itemCategoryIds);
            $item->save();
        }
    }

    /**
     * Adds the hashtags with the given IDs to the items with the given IDs.
     *
     * @param int[] $hashtagIds list of IDs for hashtags that shall be added to the items referenced by $itemIds
     * @param int[] $itemIds list of IDs for items that shall be tagged with the hashtags referenced by $hashtagIds
     * @param int $contextId the ID of the room containing the specified hashtags and items
     */
    public function addHashtagsById(array $hashtagIds, array $itemIds, int $contextId)
    {
        if (empty($hashtagIds) || empty($itemIds)) {
            return;
        }

        foreach ($itemIds as $itemId) {
            $itemHashtagIds = $this->getLinkedHashtagIds($itemId, $contextId);
            $itemHashtagIds = array_unique(array_merge($itemHashtagIds, $hashtagIds));

            $item = $this->itemService->getTypedItem($itemId);
            $item->setBuzzwordListByID($itemHashtagIds);
            $item->save();
        }
    }

    /**
     * Returns an array of all category (aka tag) IDs for the room with the given ID. Each array item is keyed
     * by the category's title & ID (like '<TITLE>_<ID>'), and (if `$flatten` is false) a category's children
     * are contained in an array item keyed by '<TITLE>_sub_<ID>'.
     *
     * @param int $roomId the ID of the room whose categories shall be returned
     * @param bool $flatten whether the returned array should be flattened (true) or not (false); defaults to false
     * in which case a category's children are contained in a sub-array
     * @return array
     */
    public function getCategories(int $roomId, bool $flatten = false): array
    {
        $categories = $this->categoryService->getTags($roomId);
        return $this->transformTagArray($categories, $flatten);
    }

    /**
     * Returns an array of category (aka tag) IDs which are linked to the given item.
     *
     * @param cs_item $item
     * @return int[]
     */
    public function getLinkedCategoryIds(cs_item $item): array
    {
        $linkedCategories = [];
        $categoriesList = $item->getTagList();

        /** @var cs_tag_item $categoryItem */
        $categoryItem = $categoriesList->getFirst();
        while ($categoryItem) {
            $linkedCategories[] = $categoryItem->getItemId();
            $categoryItem = $categoriesList->getNext();
        }

        return $linkedCategories;
    }

    /**
     * Returns an array of all hashtag (aka buzzword) titles for the room with the given ID keyed by the hashtag's ID.
     *
     * @param int $roomId
     * @return array
     */
    public function getHashtags(int $roomId): array
    {
        $hashtags = [];

        /** @var cs_buzzword_manager $buzzwordManager */
        $buzzwordManager = $this->legacyEnvironment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->setTypeLimit('buzzword');
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();

        $buzzwordItem = $buzzwordList->getFirst();
        while ($buzzwordItem) {
            $hashtags[$buzzwordItem->getItemId()] = $buzzwordItem->getTitle();
            $buzzwordItem = $buzzwordList->getNext();
        }

        return array_flip($hashtags);
    }

    /**
     * Returns an array of hashtag (aka buzzword) IDs which are linked to the item with the given ID.
     *
     * @param int $itemId
     * @param int $roomId
     * @return int[]
     */
    public function getLinkedHashtagIds(int $itemId, int $roomId): array
    {
        $linkedHashtags = [];

        /** @var cs_buzzword_manager $buzzwordManager */
        $buzzwordManager = $this->legacyEnvironment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->setTypeLimit('buzzword');
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();

        /** @var cs_buzzword_item $buzzwordItem */
        $buzzwordItem = $buzzwordList->getFirst();
        while ($buzzwordItem) {
            $selected_ids = $buzzwordItem->getAllLinkedItemIDArrayLabelVersion();
            if (in_array($itemId, $selected_ids)) {
                $linkedHashtags[] = (int)$buzzwordItem->getItemId();
            }
            $buzzwordItem = $buzzwordList->getNext();
        }

        return $linkedHashtags;
    }

    /**
     * @param array $baseCategories
     * @param array $itemCategories
     * @return array
     */
    public function getTagDetailArray($baseCategories, $itemCategories): array
    {
        $result = [];
        $tempResult = [];
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = [
                            'title' => $baseCategory['title'],
                            'item_id' => $baseCategory['item_id'],
                            'children' => $tempResult
                        ];
                    } else {
                        $result[] = [
                            'title' => $baseCategory['title'],
                            'item_id' => $baseCategory['item_id']
                        ];
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = [
                        'title' => $baseCategory['title'],
                        'item_id' => $baseCategory['item_id'],
                        'children' => $tempResult
                    ];
                }
            }
            $tempResult = [];
            $addCategory = false;
        }

        return $result;
    }

    /**
     * Transforms the given category (aka tag) array into an array of category IDs. Each array item is keyed
     * by the category's title & ID (like '<TITLE>_<ID>'), and (if `$flatten` is false) a category's children
     * are contained in an array item keyed by '<TITLE>_sub_<ID>'.
     *
     * @param array $tagArray array of categories in a format as created by `CategoryService->buildTagArray()`
     * @param bool $flatten whether the returned array should be flattened (true) or not (false); defaults to false
     * in which case a category's children are contained in a sub-array
     * @return array
     */
    private function transformTagArray(array $tagArray, bool $flatten = false): array
    {
        $array = [];

        foreach ($tagArray as $tag) {
            // NOTE: in order to form unique array keys, we append the category (aka tag) ID to the category title;
            // note that, in any form that makes use of this tag array, the category ID must be stripped again
            // from the title (e.g. via a `choice_label` field option)
            $array[$tag['title'] . '_' . $tag['item_id']] = $tag['item_id'];

            if (!empty($tag['children'])) {
                $children = $this->transformTagArray($tag['children'], $flatten);

                if ($flatten) {
                    $array = array_merge($array, $children);
                } else {
                    $array[$tag['title'] . '_sub' . '_' . $tag['item_id']] = $children;
                }
            }
        }

        return $array;
    }
}