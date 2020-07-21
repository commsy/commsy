<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 14:35
 */

namespace App\Action\Copy;


use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Http\JsonDataResponse;
use App\Http\JsonErrorResponse;
use App\Utils\RoomService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class InsertUserroomAction
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(TranslatorInterface $translator, LegacyEnvironment $legacyEnvironment, ItemService $itemService)
    {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        if ($this->legacyEnvironment->isArchiveMode()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in archived workspaces is not allowed'));
        }

        if ($this->legacyEnvironment->inPortal()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in portal is not allowed'));
        }

        if ($this->legacyEnvironment->getCurrentUserItem()->isOnlyReadUser()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items as read only user is not allowed'));
        }

        foreach ($items as $item) {
            // archive
            $toggleArchive = false;
            if ($item->isArchived() and !$this->legacyEnvironment->isArchiveMode()) {
                $toggleArchive = true;
                $this->legacyEnvironment->toggleArchiveMode();
            }

            // archive
            $importItem = $this->itemService->getTypedItem($item->getItemId());

            // archive
            if ($toggleArchive) {
                $this->legacyEnvironment->toggleArchiveMode();
            }

            // archive
            $importItem->setContextItem($roomItem);
            $importItem->setContextID($roomItem->getItemID());
            $copy = $importItem->copy();

            if (empty($copy->getErrorArray())) {
                $readerManager = $this->legacyEnvironment->getReaderManager();
                $readerManager->markRead($copy->getItemID(), $copy->getVersionID());
                $noticedManager = $this->legacyEnvironment->getNoticedManager();
                $noticedManager->markNoticed($copy->getItemID(), $copy->getVersionID());
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('inserted %count% entries in this room', count($items), [
                '%count%' => count($items),
            ]),
        ]);
    }
}