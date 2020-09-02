<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 14:35
 */

namespace App\Action\Copy;


use App\Services\CopyService;
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

    /**
     * @var CopyService
     */
    private $copyService;

    public function __construct(
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        CopyService $copyService
    ) {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->copyService = $copyService;
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

        $imports = $this->copyService->getListEntries(0);

        foreach ($items as $user) {
            /** @var \cs_user_item $user */
            $userRoom = $user->getLinkedUserroomItem();

            foreach ($imports as $import) {
                /** @var \cs_item $import */

                $oldContextId = $this->legacyEnvironment->getCurrentContextID();
                $this->legacyEnvironment->setCurrentContextID($userRoom->getItemID());
                $copy = $import->copy();
                $this->legacyEnvironment->setCurrentContextID($oldContextId);

                if (empty($copy->getErrorArray())) {
                    $readerManager = $this->legacyEnvironment->getReaderManager();
                    $readerManager->markRead($copy->getItemID(), $copy->getVersionID());
                    $noticedManager = $this->legacyEnvironment->getNoticedManager();
                    $noticedManager->markNoticed($copy->getItemID(), $copy->getVersionID());
                }
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('inserted %count% entries', count($items), [
                '%count%' => count($items),
            ]),
        ]);
    }
}