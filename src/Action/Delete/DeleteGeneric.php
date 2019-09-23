<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:17
 */

namespace App\Action\Delete;


use App\Services\LegacyEnvironment;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DeleteGeneric implements DeleteInterface
{
    /**
     * @var \cs_environment
     */
    protected $legacyEnvironment;

    private $session;

    public function __construct(LegacyEnvironment $legacyEnvironment, SessionInterface $session)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->session = $session;
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
        $currentClipboardIds = $this->session->get('clipboard_ids', []);
        if (in_array($itemId, $currentClipboardIds)) {
            unset($currentClipboardIds[array_search($itemId, $currentClipboardIds)]);
            $this->session->set('clipboard_ids', $currentClipboardIds);
        }
    }
}