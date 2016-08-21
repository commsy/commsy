<?php
namespace CommsyBundle\Services;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\ItemService;

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

    public function getListEntries($roomId, $max = NULL, $start = NULL,  $sort = NULL)
    {
        $currentClipboardIds = array();
        if ($this->sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $this->sessionItem->getValue('clipboard_ids');
        }
        
        $entries = [];
        foreach ($currentClipboardIds as $currentClipboardId) {
            $typedItem = $this->itemService->getTypedItem($currentClipboardId);
            if ($this->type) {
                if ($typedItem->getItemType() == $this->type) {
                    $entries[] = $typedItem;
                }
            } else {
                $entries[] = $typedItem;
            }
        }

        return $entries;
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        if ($formData['type']) {
            $this->type = $formData['type'];
        }
    }
}