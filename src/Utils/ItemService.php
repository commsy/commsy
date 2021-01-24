<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_userroom_item;

class ItemService
{
    /**
     * @var LegacyEnvironment $legacyEnvironment
     */
    private $legacyEnvironment;

    /**
     * @var \cs_item_manager $itemManager
     */
    private $itemManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->setItemManager();
    }

    /**
     * @param integer $itemId
     * @return \cs_item
     */
    public function getItem($itemId)
    {
        $this->setItemManager();
        $item = $this->itemManager->getItem($itemId);
        return $item;
    }

    /**
     * @param int $itemId
     * @param int (optional) $versionId
     * @return \cs_item|null
     */
    public function getTypedItem($itemId, $versionId = null)
    {
        $item = $this->getItem($itemId);

        if ($item && is_object($item)) {
            $type = $item->getItemType();

            if ($type == 'label') {
                $labelManager = $this->legacyEnvironment->getLabelManager();
                $labelItem = $labelManager->getItem($item->getItemID());
                $type = $labelItem->getLabelType();
            }
            
            $manager = $this->legacyEnvironment->getManager($type);
            if (!$manager) {
                return null;
            }

            if ($versionId === null) {
                return $manager->getItem($item->getItemID());
            } else {
                if (method_exists($manager, 'getItemByVersion')) {
                    return $manager->getItemByVersion($itemId, $versionId);
                }
            }
        }

        return null;
    }

    public function getLinkedItemIdArray($itemId)
    {
        $item = $this->getTypedItem($itemId);
        $linkedItems = $item->getAllLinkItemList()->to_array();

        $linkedItemIdArray = [];

        foreach ($linkedItems as $key => $value) {
            $linkedItemIdArray[] = $value->getSecondLinkedItemID();
        }

        return $linkedItemIdArray;

    }
    
    public function getEditorsForItem ($item) {
        $user = $this->legacyEnvironment->getCurrentUserItem();
	    $link_modifier_item_manager = $this->legacyEnvironment->getLinkModifierItemManager();
	    $user_manager = $this->legacyEnvironment->getUserManager();
	    $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());
	    $modifier_array = array();
	    foreach($modifiers as $modifier_id) {
	        $modificator = $user_manager->getItem($modifier_id);
            $modifier_array[] = $modificator;
	    }
	    return $modifier_array;
    }
    
    public function getAdditionalEditorsForItem ($item) {
        $modifier_array = $this->getEditorsForItem($item);
        $additional_modifier_array = array();
        foreach ($modifier_array as $modifier) {
            if ($modifier->getItemId() != $item->getCreatorId()) {
                $additional_modifier_array[] = $modifier;
            }
        }
        return $additional_modifier_array;
    }

    public function getItemFileList($itemId) {
        $item = $this->getTypedItem($itemId);

        if (isset($item)) {
            if ( $item->isA('material') ) {
                $file_list = $item->getFileListWithFilesFromSections();
            } elseif ( $item->isA('discussion') ) {
                $file_list = $item->getFileListWithFilesFromArticles();
            } elseif ( $item->isA('todo') ) {
                $file_list = $item->getFileListWithFilesFromSteps();
            } else {
                $file_list = $item->getFileList();
            }

            if ($item->isA('section')) {
                $material_item = $item->getLinkedItem();
                $file_list2 = $material_item->getFileList();
                if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
                    $file_list->addList($file_list2);
                }
            }

            if (!empty($file_list)) {
                $file_array = $file_list->to_Array();

                $file_name_array = array();
                foreach ($file_array as $file) {
                    $file_name_array[htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8')] = $file;
                }

                return $file_name_array;
            }
        }

        return [];
    }

    /**
     * Returns all searchable items contained in rooms specified by the given room IDs.
     * @param integer[] $contextIds array of room IDs for rooms whose items shall be returned
     * @return \cs_item[]
     */
    public function getSearchableItemsForContextIds(array $contextIds)
    {
        if (empty($contextIds)) {
            return [];
        }

        $itemManager = $this->itemManager;
        $searchableTypes = [
            CS_ANNOUNCEMENT_TYPE,
            CS_DATE_TYPE,
            CS_DISCUSSION_TYPE,
            CS_LABEL_TYPE, // groups, topics & institutions
            CS_MATERIAL_TYPE,
            CS_TODO_TYPE,
            CS_USER_TYPE,
            cs_userroom_item::ROOM_TYPE_USER,
        ];

        $itemManager->resetLimits();
        $itemManager->setNoIntervalLimit();
        $itemManager->setTypeArrayLimit($searchableTypes);
        $itemManager->setContextArrayLimit($contextIds);

        $itemManager->select();

        /** @var \cs_list $itemList */
        $itemList = $itemManager->get();

        return $itemList->to_array();
    }

    /**
     * @param \cs_item[] cs_item array
     * @param bool $withAnnotations Should related annotations also marked read?
     */
    public function markRead($items, $withAnnotations = true)
    {
        $noticedManager = $this->legacyEnvironment->getNoticedManager();
        $readerManager = $this->legacyEnvironment->getReaderManager();

        foreach ($items as $item) {
            $noticedManager->markNoticed($item->getItemID(), $item->getVersionID());
            $readerManager->markRead($item->getItemID(), $item->getVersionID());

            // annotations
            if ($withAnnotations) {
                $annotations = $item->getAnnotationList();
                if (!empty($annotations)) {
                    /** @var \cs_annotation_item $annotationItem */
                    $annotationItem = $annotations->getFirst();

                    while ($annotationItem) {
                        $noticedManager->markNoticed($annotationItem->getItemID(), '0');
                        $readerManager->markRead($annotationItem->getItemId(), '0');

                        $annotationItem = $annotations->getNext();
                    }
                }
            }
        }
    }

    private function setItemManager() {
        if (!$this->legacyEnvironment->isArchiveMode()) {
            $this->itemManager = $this->legacyEnvironment->getItemManager();
        } else {
            $this->itemManager = $this->legacyEnvironment->getZzzItemManager();
        }
    }
}