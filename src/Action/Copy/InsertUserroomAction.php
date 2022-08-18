<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 14:35
 */

namespace App\Action\Copy;


use App\Entity\Account;
use App\Http\JsonDataResponse;
use App\Http\JsonErrorResponse;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class InsertUserroomAction
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var MarkedService
     */
    private MarkedService $markService;

    /**
     * @var Security
     */
    private $security;

    public function __construct(
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        MarkedService $markService,
        Security $security
    ) {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->markService = $markService;
        $this->security = $security;
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
        $imports = $this->markService->getListEntries(0);

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
            /** @var Account $account */
            $account = $this->security->getUser();
            $authSource = $account->getAuthSource();
            $userManager = $this->legacyEnvironment->getUserManager();

            // for the current user, get his/her related users from the user rooms identified by the IDs in $userRoomIds
            /** @var \cs_user_item[] $relatedUsers */
            $relatedUsers = $userManager->getAllUsersByUserAndRoomIDLimit($currentUser->getUserId(), $userRoomIds, $authSource->getId());

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
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->trans('inserted %count% entries into %usercount% personal workspaces', [
                    '%count%' => count($imports),
                    '%usercount%' => count($users),
                ]),
        ]);
    }
}