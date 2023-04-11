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

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use App\Entity\Portal;
use App\Lock\LockManager;
use App\Proxy\PortalProxy;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use cs_item;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ItemVoter extends Voter
{
    public const SEE = 'ITEM_SEE';
    public const EDIT = 'ITEM_EDIT';
    public const ANNOTATE = 'ITEM_ANNOTATE';
    public const PARTICIPATE = 'ITEM_PARTICIPATE';
    public const MODERATE = 'ITEM_MODERATE';
    public const ENTER = 'ITEM_ENTER';
    public const USERROOM = 'ITEM_USERROOM';
    public const DELETE = 'ITEM_DELETE';
    public const EDIT_LOCK = 'ITEM_EDIT_LOCK';

    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private ItemService $itemService,
        private RoomService $roomService,
        private UserService $userService,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private LockManager $lockManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, [
            self::SEE,
            self::EDIT,
            self::ANNOTATE,
            self::PARTICIPATE,
            self::MODERATE,
            self::ENTER,
            self::USERROOM,
            self::DELETE,
            self::EDIT_LOCK,
        ]);
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        // get current logged in user
        $user = $token->getUser();

        if ($user instanceof Account && 'root' === $user->getUsername()) {
            return true;
        }

        $itemId = $object;

        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            $portal = $this->entityManager->getRepository(Portal::class)->find($itemId);

            if ($portal) {
                $item = new PortalProxy($portal, $this->legacyEnvironment);
            }
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        if ($item) {
            switch ($attribute) {
                case self::SEE:
                    return $this->canView($item, $currentUser);

                case self::EDIT:
                    return $this->canEdit($item, $currentUser);

                case self::ANNOTATE:
                    return $this->canAnnotate($item, $currentUser);

                case self::PARTICIPATE:
                    return $this->canParticipate($item, $currentUser);

                case self::MODERATE:
                    return $this->canModerate($item, $currentUser);

                case self::ENTER:
                    return $this->canEnter($item, $currentUser, $user);

                case self::USERROOM:
                    return $this->hasUserroomItemPrivileges($item, $currentUser);

                case self::DELETE:
                    return $this->canDelete($item, $currentUser);

                case self::EDIT_LOCK:
                    return $this->canEditLock($item, $currentUser);
            }
        } else {
            if ('NEW' == $itemId) {
                if (self::EDIT == $attribute) {
                    // NOTE: by using `isGuest()` (instead of `isReallyGuest()`) we'll also catch logged-in users who
                //       are currently viewing a community room with guest access which they are no member of
                    if ($currentUser->isGuest() || $currentUser->isOnlyReadUser() || $currentUser->isRequested()) {
                        return false;
                    }

                    $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

                    return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
                }
            }
        }

        throw new LogicException('This code should not be reached!');
    }

    private function canView(cs_item $item, cs_user_item $currentUser)
    {
        if ($item->isDeleted()) {
            return false;
        }

        if ($item->maySee($currentUser)) {
            return true;
        }

        return false;
    }

    private function canEdit(cs_item $item, cs_user_item $currentUser): bool
    {
        $contextItem = $item->getContextItem();
        if (null !== $contextItem && method_exists($contextItem, 'getArchived') && $contextItem->getArchived()) {
            // users may still edit their own account settings & room profile (which also allows them to leave the room)
            if ($item instanceof cs_user_item && $item->getItemID() === $currentUser->getItemID()) {
                return true;
            }

            return false;
        }

        if (!$this->canEditLock($item, $currentUser)) {
            return false;
        }

        if (CS_DATE_TYPE == $item->getItemType()) {
            if ($item->isExternal()) {
                return false;
            }
        }

        if (CS_DISCUSSION_TYPE == $item->getItemType()) {
            $request = $this->requestStack->getCurrentRequest();
            if ('app_discussion_createanswer' == $request->get('_route')) {
                return true;
            }
        }

        if ($currentUser->isReadOnlyUser()) {
            if ($currentUser->getItemId() == $item->getItemId()) {
                return true;
            }
        }

        if ($item->mayEdit($currentUser)) {
            return true;
        }

        return false;
    }

    private function canAnnotate(cs_item $item, cs_user_item $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if (2 == $userStatus || 3 == $userStatus) { // user & moderator
            $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

            return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
        }

        return false;
    }

    private function canParticipate(cs_item $item, cs_user_item $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if (2 == $userStatus || 3 == $userStatus || 4 == $userStatus) { // user, moderator & read-only user
            $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

            return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
        }

        return false;
    }

    private function canModerate(cs_item$item, cs_user_item $currentUser)
    {
        if (3 == $currentUser->getStatus()) {
            return true;
        }

        return false;
    }

    private function canEnter(cs_item|PortalProxy $item, $currentUser, $user): bool
    {
        if ($item->isPrivateRoom()) {
            return true;
        }

        if ($item->isPortal()) {
            if ($currentUser->isRoot()) {
                return true;
            }

            if ($item->isLocked()) {
                return false;
            }

            if ($item->isOpenForGuests()) {
                return true;
            }

            // allow access if user is authenticated
            return $user instanceof UserInterface;
        }

        $roomItem = $this->roomService->getRoomItem($item->getItemID());
        if (!$roomItem) {
            return false;
        }

        if (!$roomItem->isDeleted() && $roomItem->mayEnter($currentUser)) {
            return true;
        }

        return false;
    }

    private function canDelete($item, $currentUser)
    {
        $roomItem = $this->roomService->getRoomItem($item->getItemID());
        if (!$roomItem) {
            return false;
        }

        if ('userroom' === $roomItem->getType()) {
            return false;
        }

        // the parent moderator can always delete (or lock) a room even if (s)he cannot view/enter
        // it; this is needed so that a community room moderator can delete/(un)lock any contained
        // project room even if (s)he isn't a member of that project room
        if ($this->isParentModeratorForRoom($currentUser, $roomItem)) {
            return true;
        }

        if (!$roomItem->isDeleted() && $roomItem->mayEnter($currentUser)) {
            return true;
        }

        return false;
    }

    private function canEditLock(cs_item $item, cs_user_item $currentUser): bool
    {
        if ($currentUser->isRoot() || !$this->lockManager->supportsLocking($item->getItemID())) {
            return true;
        }

        return $this->lockManager->userCanLock($item->getItemID());
    }

    private function hasUserroomItemPrivileges($item, $currentUser)
    {
        $contextItem = $item->getContextItem();
        if (null !== $contextItem &&
            'userroom' === $contextItem->getType() &&
            $this->canParticipate($item, $currentUser)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the given user is a parent moderator for the given room.
     */
    private function isParentModeratorForRoom(cs_user_item $user, ?cs_room_item $room): bool
    {
        if (!$room) {
            return false;
        }

        return $this->userService->userIsParentModeratorForRoom($room, $user);
    }
}
