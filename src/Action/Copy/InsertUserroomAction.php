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

    public function execute(\cs_room_item $roomItem, array $users): Response
    {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if ($this->legacyEnvironment->isArchiveMode()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in archived workspaces is not allowed'));
        }

        if ($this->legacyEnvironment->inPortal()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in portal is not allowed'));
        }

        if ($currentUser->isOnlyReadUser()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items as read only user is not allowed'));
        }

        $readerManager = $this->legacyEnvironment->getReaderManager();
        $noticedManager = $this->legacyEnvironment->getNoticedManager();
        $userRoomIds = [];
        $versionIdsByCopyIds = [];

        // get the copied items from the clipboard to be "imported" into the given users' user rooms
        $imports = $this->copyService->getListEntries(0);

        // for each given (project room) user, copy each import item into his/her user room
        foreach ($users as $user) {
            /** @var \cs_user_item $user */
            $userRoom = $user->getLinkedUserroomItem();
            if (!$userRoom) {
                continue;
            }

            $userRoomId = $userRoom->getItemID();
            $userRoomIds[] = $userRoomId;

            foreach ($imports as $import) {
                /** @var \cs_item $import */

                // copy item
                $oldContextId = $this->legacyEnvironment->getCurrentContextID();
                $this->legacyEnvironment->setCurrentContextID($userRoomId);
                $copy = $import->copy();
                $this->legacyEnvironment->setCurrentContextID($oldContextId);

                // for the current user, mark the copied item as read & noticed
                if (empty($copy->getErrorArray())) {
                    $copyId = $copy->getItemID();
                    $versionId = $copy->getVersionID();
                    $versionIdsByCopyIds[$copyId] = $versionId;

                    $readerManager->markRead($copyId, $versionId);
                    $noticedManager->markNoticed($copyId, $versionId);
                }
            }
        }

        if (!empty($versionIdsByCopyIds) && !empty($userRoomIds)) {
            $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
            $authSource = $authSourceManager->getItem($currentUser->getAuthSource());
            $userManager = $this->legacyEnvironment->getUserManager();

            // for the current user, get his/her related users from the user rooms identified by the IDs in $userRoomIds
            /** @var \cs_user_item[] $relatedUsers */
            $relatedUsers = $userManager->getAllUsersByUserAndRoomIDLimit($currentUser->getUserId(), $userRoomIds, $authSource->getItemId());

            // for all found related users, mark the copied items as read & noticed
            if (!empty($relatedUsers)) {
                $relatedUserIds = array_map(function (\cs_user_item $user) {
                    return $user->getItemID();
                }, $relatedUsers);

                foreach ($versionIdsByCopyIds as $copyId => $versionId) {
                    // TODO: allowing markItemsAsRead() & markItemsAsNoticed() to accept a matching array of version IDs would avoid this foreach loop
                    $readerManager->markItemsAsRead([$copyId], $versionId, $relatedUserIds);
                    $noticedManager->markItemsAsNoticed([$copyId], $versionId, $relatedUserIds);
                }
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('inserted %itemcount% entries into %usercount% personal workspaces', count($imports), [
                    '%itemcount%' => count($imports),
                    '%usercount%' => count($users),
                ]),
        ]);
    }
}