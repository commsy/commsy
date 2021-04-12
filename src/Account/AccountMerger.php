<?php


namespace App\Account;


use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_context_item;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;

class AccountMerger
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * AccountMerger constructor.
     * @param UserService $userService
     * @param LegacyEnvironment $legacyEnvironment
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager
    ) {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
    }

    /**
     * @param Account $from
     * @param Account $into
     */
    public function mergeAccounts(Account $from, Account $into)
    {
        if ($from === $into) {
            return;
        }

        // merge non-archived rooms
        $this->legacyEnvironment->activateArchiveMode();
        $this->doMerge($from, $into);

        // merge non-archived rooms
        $this->legacyEnvironment->deactivateArchiveMode();
        $this->doMerge($from, $into);

        // merge private room
        $this->rewritePrivateRoom($from, $into);

        // merge portal
        $this->rewriteContextUserAndContent($from, $into, $this->legacyEnvironment->getCurrentPortalItem());

        // delete the merged account
        $this->entityManager->remove($from);
        $this->entityManager->flush();
    }

    /**
     * @param Account $from
     * @param Account $into
     */
    private function doMerge(Account $from, Account $into)
    {
        $fromPortalUser = $this->userService->getPortalUser($from);
        $intoPortalUser = $this->userService->getPortalUser($into);

        list('duplicated' => $duplicated, 'nonDuplicated' => $nonDuplicated) =
            $this->prepareRoomLists($fromPortalUser, $intoPortalUser);

        // non duplicates
        foreach ($nonDuplicated as $uniqueRoom) {
            $this->rewriteRoomUser($from, $into, $uniqueRoom, $intoPortalUser);
        }

        // duplicates
        foreach ($duplicated as $nonUniqueRoom) {
            $this->rewriteContextUserAndContent($from, $into, $nonUniqueRoom);
        }
    }

    /**
     * @param cs_user_item $fromPortalUser
     * @param cs_user_item $intoPortalUser
     * @return array[]
     */
    private function prepareRoomLists(cs_user_item $fromPortalUser, cs_user_item $intoPortalUser): array
    {
        $duplicatedRooms = [];
        $nonDuplicatedRooms = [];

        $intoUserRooms = $intoPortalUser->getRelatedCommunityListAllUserStatus();
        $intoUserRooms->addList($intoPortalUser->getRelatedCommunityListAllUserStatus());

        $formUserRooms = $fromPortalUser->getRelatedProjectListAllUserStatus();
        $formUserRooms->addList($fromPortalUser->getRelatedCommunityListAllUserStatus());

        foreach ($formUserRooms as $userFromRoom) {
            if ($intoUserRooms->inList($userFromRoom)) {
                $duplicatedRooms[] = $userFromRoom;
            } else {
                $nonDuplicatedRooms[] = $userFromRoom;
            }
        }

        return [
            'duplicated' => $duplicatedRooms,
            'nonDuplicated' => $nonDuplicatedRooms,
        ];
    }

    /**
     * @param Account $account
     * @param cs_context_item $context
     * @return cs_user_item|null
     */
    public function getUserInContext(Account $account, cs_context_item $context): ?cs_user_item
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($context->getItemID());
        $userManager->setUserIDLimit($account->getUsername());
        $userManager->setAuthSourceLimit($account->getAuthSource()->getId());
        $userManager->select();
        $users = $userManager->get();
        if ($users->getCount() === 1) {
            /** @var cs_user_item $user */
            $user = $users->getFirst();
            return $user;
        }

        return null;
    }

    /**
     * @param Account $from
     * @param Account $into
     * @param cs_room_item $room
     * @param cs_user_item|null $nameSource
     */
    private function rewriteRoomUser(Account $from, Account $into, cs_room_item $room, cs_user_item $nameSource = null)
    {
        $roomUser = $this->getUserInContext($from, $room);
        $roomUser->setUserID($into->getUsername());
        $roomUser->setAuthSource($into->getAuthSource()->getId());
        if (isset($nameSource)) {
            $roomUser->setFirstname($nameSource->getFirstname());
            $roomUser->setLastname($nameSource->getLastname());
        }
        $roomUser->save();
    }

    /**
     * @param Account $from
     * @param Account $into
     * @param cs_context_item $context
     */
    private function rewriteContextUserAndContent(Account $from, Account $into, cs_context_item $context)
    {
        $fromRoomUser = $this->getUserInContext($from, $context);
        $intoRoomUser = $this->getUserInContext($into, $context);

        $intoRoomUser->setStatus($fromRoomUser->getStatus() > $intoRoomUser->getStatus() ?
            $fromRoomUser->getStatus() : $intoRoomUser->getStatus());
        $intoRoomUser->save();

        $managerList = [
            CS_ANNOTATION_TYPE,
            CS_ANNOUNCEMENT_TYPE,
            CS_DATE_TYPE,
            CS_DISCARTICLE_TYPE,
            CS_DISCUSSION_TYPE,
            CS_FILE_TYPE,
            CS_LABEL_TYPE,
            CS_LINK_TYPE,
            CS_LINKITEM_TYPE,
            CS_LINKMODITEM_TYPE,
            CS_MATERIAL_TYPE,
            CS_READER_TYPE,
            CS_ROOM_TYPE,
            CS_SECTION_TYPE,
            CS_TASK_TYPE,
            CS_PORTAL_TYPE,
            CS_TODO_TYPE,
            CS_TAG_TYPE,
            CS_TAG2TAG_TYPE,
            CS_ITEM_TYPE
        ];

        foreach ($managerList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $manager->mergeAccounts($intoRoomUser->getItemID(), $fromRoomUser->getItemID());
        }

        $fromRoomUser->delete();
    }

    /**
     * @param Account $from
     * @param Account $into
     */
    private function rewritePrivateRoom(Account $from, Account $into)
    {
        $fromPortalUser = $this->userService->getPortalUser($from);
        $intoPortalUser = $this->userService->getPortalUser($into);

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();

        $fromPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($fromPortalUser, $from->getContextId());
        $intoPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($intoPortalUser, $into->getContextId());

        $intoPrivateRoomUser = $this->getUserInContext($into, $intoPrivateRoom);

        $newIds = [];

        $primaryList = [CS_DATE_TYPE, CS_LABEL_TYPE, CS_MATERIAL_TYPE, CS_FILE_TYPE, CS_TAG_TYPE];
        foreach ($primaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom($fromPrivateRoom, $intoPrivateRoom,
                $intoPrivateRoomUser->getItemID());
        }

        $secondaryList = [CS_ANNOTATION_TYPE, CS_SECTION_TYPE];
        foreach ($secondaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom($fromPrivateRoom, $intoPrivateRoom,
                $intoPrivateRoomUser->getItemID(), $newIds);
        }

        $linkList = [CS_LINK_TYPE, CS_LINKITEM_TYPE, CS_LINKITEMFILE_TYPE, CS_TAG2TAG_TYPE];
        foreach ($linkList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom($fromPrivateRoom, $intoPrivateRoom,
                $intoPrivateRoomUser->getItemID(), $newIds);
        }

        $linkModifierItemManager = $this->legacyEnvironment->getLinkModifierItemManager();
        foreach ($newIds as $newId) {
            $linkModifierItemManager->markEdited($newId, $intoPrivateRoomUser->getItemID());
        }

        $markupList = [CS_DATE_TYPE, CS_LABEL_TYPE, CS_MATERIAL_TYPE, CS_ANNOTATION_TYPE, CS_SECTION_TYPE];
        foreach ($markupList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $manager->refreshInDescLinks($intoPrivateRoom->getItemID(), $newIds);
        }

        $fromPrivateRoom->delete();
    }
}