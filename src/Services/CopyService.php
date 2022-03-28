<?php

namespace App\Services;

use App\Utils\ItemService;
use cs_item;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CopyService
{
    private ItemService $itemService;

    private SessionInterface $session;

    private ?string $type = null;

    /**
     * CopyService constructor.
     * @param ItemService $itemService
     * @param SessionInterface $session
     */
    public function __construct(
        ItemService $itemService,
        SessionInterface $session
    ) {
        $this->itemService = $itemService;
        $this->session = $session;
    }

    public function getCountArray($roomId)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

        if ($this->type) {
            $itemsCountArray['count'] = sizeof($this->getListEntries());
        } else {
            $itemsCountArray['count'] = sizeof($currentClipboardIds);
        }

        $itemsCountArray['countAll'] = sizeof($currentClipboardIds);

        return $itemsCountArray;
    }

    /**
     * @param null $max
     * @param null $start
     * @param null $sort
     * @return cs_item[]
     */
    public function getListEntries($max = null, $start = null, $sort = null)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

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
     * @param integer[] $ids
     * @return cs_item[]
     */
    public function getCopiesById($ids)
    {
        $allCopies = $this->getListEntries();

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

    public function removeEntries($roomId, $entries)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

        $clipboardIds = [];
        foreach ($currentClipboardIds as $currentClipboardId) {
            if (!in_array($currentClipboardId, $entries)) {
                $clipboardIds[] = $currentClipboardId;
            }
        }

        $this->session->set('clipboard_ids', $clipboardIds);

        return $this->getCountArray($roomId);
    }

    public function removeItemFromClipboard(int $itemId)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);
        if (in_array($itemId, $currentClipboardIds)) {
            unset($currentClipboardIds[array_search($itemId, $currentClipboardIds)]);
            $this->session->set('clipboard_ids', $currentClipboardIds);
        }
    }
}