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

namespace App\Services;

use App\Utils\ItemService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MarkedService
{
    private ?string $type = null;

    /**
     * MarkedService constructor.
     */
    public function __construct(private ItemService $itemService, private SessionInterface $session)
    {
    }

    public function getCountArray($roomId)
    {
        $itemsCountArray = [];
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
     *
     * @return \cs_item[]
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
                $max = is_countable($currentClipboardIds) ? count($currentClipboardIds) : 0;
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
            ++$counter;
        }

        return $entries;
    }

    /**
     * @param int[] $ids
     *
     * @return \cs_item[]
     */
    public function getMarkedItemsById($ids)
    {
        $allMarkedItems = $this->getListEntries();

        $filteredMarkedItems = [];
        foreach ($allMarkedItems as $markedItem) {
            if (in_array($markedItem->getItemID(), $ids)) {
                $filteredMarkedItems[] = $markedItem;
            }
        }

        return $filteredMarkedItems;
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
