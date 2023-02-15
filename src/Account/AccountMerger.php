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

namespace App\Account;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;

class AccountMerger
{
    private cs_environment $legacyEnvironment;

    /**
     * AccountMerger constructor.
     */
    public function __construct(
        private UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        private EntityManagerInterface $entityManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function mergeAccounts(Account $from, Account $into)
    {
        if ($from === $into) {
            return;
        }

        // merge rooms
        $this->doMerge($from, $into);

        // merge private room
        $this->rewritePrivateRoom($from, $into);

        // merge portal
        $this->rewriteContextUserAndContent($from, $into, $this->legacyEnvironment->getCurrentPortalItem()->getId());

        // delete the merged account
        $this->entityManager->remove($from);
        $this->entityManager->flush();
    }

    private function doMerge(Account $from, Account $into)
    {
        $fromPortalUser = $this->userService->getPortalUser($from);
        $intoPortalUser = $this->userService->getPortalUser($into);

        ['duplicated' => $duplicated, 'nonDuplicated' => $nonDuplicated] =
            $this->prepareRoomLists($fromPortalUser, $intoPortalUser);

        // non duplicates
        foreach ($nonDuplicated as $uniqueRoom) {
            $this->rewriteRoomUser($from, $into, $uniqueRoom, $intoPortalUser);
        }

        // duplicates
        foreach ($duplicated as $nonUniqueRoom) {
            $this->rewriteContextUserAndContent($from, $into, $nonUniqueRoom->getItemId());
        }
    }

    /**
     * @return array[]
     */
    private function prepareRoomLists(cs_user_item $fromPortalUser, cs_user_item $intoPortalUser): array
    {
        $duplicatedRooms = [];
        $nonDuplicatedRooms = [];

        $intoUserRooms = $intoPortalUser->getRelatedProjectListAllUserStatus();
        $intoUserRooms->addList($intoPortalUser->getRelatedCommunityListAllUserStatus());

        $fromUserRooms = $fromPortalUser->getRelatedProjectListAllUserStatus();
        $fromUserRooms->addList($fromPortalUser->getRelatedCommunityListAllUserStatus());

        foreach ($fromUserRooms as $userFromRoom) {
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

    private function getUserInContext(Account $account, int $contextId): ?cs_user_item
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($contextId);
        $userManager->setUserIDLimit($account->getUsername());
        $userManager->setAuthSourceLimit($account->getAuthSource()->getId());
        $userManager->select();
        $users = $userManager->get();
        if (1 === $users->getCount()) {
            /** @var cs_user_item $user */
            $user = $users->getFirst();

            return $user;
        }

        return null;
    }

    private function rewriteRoomUser(Account $from, Account $into, cs_room_item $room, cs_user_item $nameSource = null)
    {
        $roomUser = $this->getUserInContext($from, $room->getItemID());
        $roomUser->setUserID($into->getUsername());
        $roomUser->setAuthSource($into->getAuthSource()->getId());
        if (isset($nameSource)) {
            $roomUser->setFirstname($nameSource->getFirstname());
            $roomUser->setLastname($nameSource->getLastname());
        }
        $roomUser->save();
    }

    private function rewriteContextUserAndContent(Account $from, Account $into, int $contextId)
    {
        $fromRoomUser = $this->getUserInContext($from, $contextId);
        $intoRoomUser = $this->getUserInContext($into, $contextId);

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
            CS_ITEM_TYPE,
        ];

        foreach ($managerList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $manager->mergeAccounts($intoRoomUser->getItemID(), $fromRoomUser->getItemID());
        }

        $fromRoomUser->delete();
    }

    private function rewritePrivateRoom(Account $from, Account $into)
    {
        $fromPortalUser = $this->userService->getPortalUser($from);
        $intoPortalUser = $this->userService->getPortalUser($into);

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();

        $fromPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($fromPortalUser, $from->getContextId());
        $intoPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($intoPortalUser, $into->getContextId());

        $intoPrivateRoomUser = $this->getUserInContext($into, $intoPrivateRoom->getItemID());

        $newIds = [];

        $primaryList = [CS_DATE_TYPE, CS_LABEL_TYPE, CS_MATERIAL_TYPE, CS_FILE_TYPE, CS_TAG_TYPE];
        foreach ($primaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom(
                $fromPrivateRoom->getItemID(),
                $intoPrivateRoom->getItemID(),
                $intoPrivateRoomUser->getItemID());
        }

        $secondaryList = [CS_ANNOTATION_TYPE, CS_SECTION_TYPE];
        foreach ($secondaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom(
                $fromPrivateRoom->getItemID(),
                $intoPrivateRoom->getItemID(),
                $intoPrivateRoomUser->getItemID(),
                $newIds);
        }

        $linkList = [CS_LINK_TYPE, CS_LINKITEM_TYPE, CS_LINKITEMFILE_TYPE, CS_TAG2TAG_TYPE];
        foreach ($linkList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom(
                $fromPrivateRoom->getItemID(),
                $intoPrivateRoom->getItemID(),
                $intoPrivateRoomUser->getItemID(),
                $newIds);
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
