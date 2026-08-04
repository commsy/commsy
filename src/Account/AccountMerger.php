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
use App\Item\ItemType;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Rubric\RubricType;
use App\Services\CurrentUserResolver;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\User\UserMembershipDeleter;
use App\Utils\ReaderService;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;

class AccountMerger
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        private readonly CurrentContextResolver $currentContextResolver,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReaderService $readerService,
        private readonly UserMembershipDeleter $membershipDeleter,
        private readonly PrivateRoomDeleter $privateRoomDeleter,
        private readonly CurrentUserResolver $currentUserResolver,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function mergeAccounts(Account $from, Account $into): void
    {
        if ($from === $into) {
            return;
        }

        // Run the whole merge atomically. The legacy DB layer and the ORM share
        // the same connection (database_connection), so legacy performQuery()
        // writes and Doctrine operations commit — or roll back — together.
        // wrapInTransaction() flushes before committing, so no explicit flush
        // is needed here.
        $this->entityManager->wrapInTransaction(function () use ($from, $into): void {
            // merge rooms
            $this->doMerge($from, $into);

            // merge private room
            $this->rewritePrivateRoom($from, $into);

            // merge portal
            $this->rewriteContextUserAndContent($from, $into, $this->currentContextResolver->getPortalItem()->getId());

            // delete the merged account
            $this->entityManager->remove($from);
        });
    }

    private function doMerge(Account $from, Account $into): void
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
        $userManager->setAccountIDLimit($account->getId());
        $userManager->select();
        $users = $userManager->get();
        if (1 === $users->getCount()) {
            /** @var cs_user_item $user */
            $user = $users->getFirst();

            return $user;
        }

        return null;
    }

    private function rewriteRoomUser(Account $from, Account $into, cs_room_item $room, ?cs_user_item $nameSource = null): void
    {
        $roomUser = $this->getUserInContext($from, $room->getItemID());
        $roomUser->setAccountID($into->getId());
        $roomUser->setUserID($into->getUsername());
        if (isset($nameSource)) {
            $roomUser->setFirstname($nameSource->getFirstname());
            $roomUser->setLastname($nameSource->getLastname());
        }
        if (isset($nameSource)) {
            $roomUser->setEmail($nameSource->getRoomEmail());
        } else {
            $roomUser->setEmail($into->getEmail());
        }
        $roomUser->save();
    }

    private function rewriteContextUserAndContent(Account $from, Account $into, int $contextId): void
    {
        $fromRoomUser = $this->getUserInContext($from, $contextId);
        $intoRoomUser = $this->getUserInContext($into, $contextId);

        $intoRoomUser->setStatus($fromRoomUser->getStatus() > $intoRoomUser->getStatus() ?
            $fromRoomUser->getStatus() : $intoRoomUser->getStatus());
        $intoRoomUser->save();

        $managerList = [
            RubricType::Annotation->value,
            RubricType::Announcement->value,
            RubricType::Date->value,
            ItemType::DiscussionArticle->value,
            RubricType::Discussion->value,
            ItemType::File->value,
            RubricType::Label->value,
            ItemType::Link->value,
            ItemType::LinkItem->value,
            ItemType::LinkModifierItem->value,
            RubricType::Material->value,
            ItemType::Room->value,
            ItemType::Section->value,
            ItemType::Task->value,
            RubricType::Todo->value,
            ItemType::Tag->value,
            ItemType::Tag2Tag->value,
            ItemType::Item->value,
        ];

        $this->readerService->mergeAccounts($intoRoomUser->getItemID(), $fromRoomUser->getItemID());

        foreach ($managerList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $manager->mergeAccounts($intoRoomUser->getItemID(), $fromRoomUser->getItemID());
        }

        // For portal context, the legacy delete cascaded into
        // getOwnRoom()->delete() — already handled by rewritePrivateRoom().
        $this->membershipDeleter->softDeleteMembership(
            (int) $fromRoomUser->getItemID(),
            $this->currentDeleterId()
        );
    }

    private function rewritePrivateRoom(Account $from, Account $into): void
    {
        $fromPortalUser = $this->userService->getPortalUser($from);
        $intoPortalUser = $this->userService->getPortalUser($into);

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();

        $fromPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($fromPortalUser, $from->getPortal()?->getId());
        $intoPrivateRoom = $privateRoomManager->getRelatedOwnRoomForUser($intoPortalUser, $into->getPortal()?->getId());

        $intoPrivateRoomUser = $this->getUserInContext($into, $intoPrivateRoom->getItemID());

        $newIds = [];

        $primaryList = [RubricType::Date->value, RubricType::Label->value, RubricType::Material->value, ItemType::File->value, ItemType::Tag->value];
        foreach ($primaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom(
                $fromPrivateRoom->getItemID(),
                $intoPrivateRoom->getItemID(),
                $intoPrivateRoomUser->getItemID());
        }

        $secondaryList = [RubricType::Annotation->value, ItemType::Section->value];
        foreach ($secondaryList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $newIds += $manager->copyDataFromRoomToRoom(
                $fromPrivateRoom->getItemID(),
                $intoPrivateRoom->getItemID(),
                $intoPrivateRoomUser->getItemID(),
                $newIds);
        }

        $linkList = [ItemType::Link->value, ItemType::LinkItem->value, ItemType::LinkItemFile->value, ItemType::Tag2Tag->value];
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

        $markupList = [RubricType::Date->value, RubricType::Label->value, RubricType::Material->value, RubricType::Annotation->value, ItemType::Section->value];
        foreach ($markupList as $managerName) {
            $manager = $this->legacyEnvironment->getManager($managerName);
            $manager->refreshInDescLinks($intoPrivateRoom->getItemID(), $newIds);
        }

        $this->privateRoomDeleter->softDeleteRoom(
            (int) $fromPrivateRoom->getItemID(),
            $this->currentDeleterId(),
            RoomDeletionOptions::forAccountMerge()
        );
    }

    /**
     * Acting user's item id for audit stamping; 0 when no user is bound.
     */
    private function currentDeleterId(): int
    {
        return (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
    }
}
