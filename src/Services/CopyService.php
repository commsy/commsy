<?php
namespace App\Services;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\ItemService;
use Symfony\Component\Form\FormInterface;

class CopyService
{
    private $legacyEnvironment;

    private $roomService;

    private $itemService;

    private $sessionItem;
    
    private $type;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->roomService = $roomService;
        
        $this->itemService = $itemService;
        
        $this->sessionItem = $this->legacyEnvironment->getSessionItem();
        
        $this->type = false;
    }

    public function getCountArray($roomId)
    {
        $currentClipboardIds = array();
        if ($this->sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $this->sessionItem->getValue('clipboard_ids');
        }

        if ($this->type) {
            $itemsCountArray['count'] = sizeof($this->getListEntries($roomId));
        } else {
            $itemsCountArray['count'] = sizeof($currentClipboardIds);
        }
        
        $itemsCountArray['countAll'] = sizeof($currentClipboardIds);

        return $itemsCountArray;
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @return \cs_item[]
     */
    public function getListEntries($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $currentClipboardIds = array();
        if ($this->sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $this->sessionItem->getValue('clipboard_ids');
        }
        
        $entries = [];
        $counter = 0;
        foreach ($currentClipboardIds as $currentClipboardId) {
            if (!$start) {
                $start = 0;
            }
            if (!$max) {
                $max = count($currentClipboardIds);
            }
            if ($counter >= $start && $counter < $start + $max) {
                $typedItem = $this->itemService->getTypedItem($currentClipboardId);
                if ($this->type) {
                    if ($typedItem->getItemType() == $this->type) {
                        $entries[] = $typedItem;
                    }
                } else {
                    $entries[] = $typedItem;
                }
            }
            $counter++;
        }

        return $entries;
    }

    /**
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_item[]
     */
    public function getCopiesById($roomId, $ids)
    {
        $allCopies = $this->getListEntries($roomId);

        $filteredCopies = [];
        foreach ($allCopies as $copy) {
            if (in_array($copy->getItemID(), $ids)) {
                $filteredCopies[] = $copy;
            }
        }

        return $filteredCopies;
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        if ($formData['type']) {
            $this->type = $formData['type'];
        }
    }
    
    public function removeEntries ($roomId, $entries) {
        $currentClipboardIds = array();
        if ($this->sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $this->sessionItem->getValue('clipboard_ids');
        }
        
        $clipboardIds = [];
        foreach ($currentClipboardIds as $currentClipboardId) {
            if (!in_array($currentClipboardId, $entries)) {
                $clipboardIds[] = $currentClipboardId;
            }
        }
        
        $this->sessionItem->setValue('clipboard_ids', $clipboardIds);
        
        return $this->getCountArray($roomId);
    }
}