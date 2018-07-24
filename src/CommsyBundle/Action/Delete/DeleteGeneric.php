<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:17
 */

namespace CommsyBundle\Action\Delete;


use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DeleteGeneric implements DeleteInterface
{
    /**
     * @var \cs_environment
     */
    protected $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        $item->delete();

        $this->removeItemFromClipboard($item->getItemId());
    }

    /**
     * @param \cs_item $item
     * @return string
     */
    public function getRedirectRoute(\cs_item $item)
    {
        return null;
    }

    private function removeItemFromClipboard(int $itemId)
    {
        $sessionItem = $this->legacyEnvironment->getSessionItem();
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
            if (in_array($itemId, $currentClipboardIds)) {
                unset($currentClipboardIds[array_search($itemId, $currentClipboardIds)]);
                $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
            }
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionManager->save($sessionItem);
        }
    }
}