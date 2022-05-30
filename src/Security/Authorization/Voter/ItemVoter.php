<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use App\Entity\Portal;
use App\Proxy\PortalProxy;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ItemVoter extends Voter
{
    const SEE = 'ITEM_SEE';
    const EDIT = 'ITEM_EDIT';
    const ANNOTATE = 'ITEM_ANNOTATE';
    const PARTICIPATE = 'ITEM_PARTICIPATE';
    const MODERATE = 'ITEM_MODERATE';
    const ENTER = 'ITEM_ENTER';
    const USERROOM = 'ITEM_USERROOM';
    const DELETE = 'ITEM_DELETE';

    private $legacyEnvironment;
    private $itemService;
    private $roomService;
    private $userService;
    private $requestStack;
    private $entityManager;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        RoomService $roomService, UserService $userService, RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->roomService = $roomService;
        $this->userService = $userService;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::SEE,
            self::EDIT,
            self::ANNOTATE,
            self::PARTICIPATE,
            self::MODERATE,
            self::ENTER,
            self::USERROOM,
            self::DELETE,
        ));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        // get current logged in user
        $user = $token->getUser();

        if ($user instanceof Account && $user->getUsername() === 'root') {
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
            }
        } else {
            if ($itemId == 'NEW') {
                if ($attribute == self::EDIT) {
                    // NOTE: by using `isGuest()` (instead of `isReallyGuest()`) we'll also catch logged-in users who
                //       are currently viewing a community room with guest access which they are no member of
                if ($currentUser->isGuest() || $currentUser->isOnlyReadUser() || ($currentUser->isRequested())) {
                        return false;
                    }

                    $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

                    return !$currentRoom->isArchived();
                }
            }
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView($item, $currentUser)
    {
        if ($item->isDeleted()) {
            return false;
        }

        if ($item->maySee($currentUser)) {
            return true;
        }

        return false;
    }

    /**
     * @param $item
     * @param \cs_user_item $currentUser
     * @return bool
     */
    private function canEdit($item, \cs_user_item $currentUser): bool
    {
        $contextItem = $item->getContextItem();
        if ($contextItem !== null && $contextItem->isArchived()) {
            // users may still edit their own account settings & room profile (which also allows them to leave the room)
            if ($item instanceof \cs_user_item && $item->getItemID() === $currentUser->getItemID()) {
                return true;
            }
            return false;
        }

        if ($item->hasLocking()) {
            if ($item->isLocked()) {
                return false;
            }
        }

        if ($item->getItemType() == CS_DATE_TYPE) {
            if ($item->isExternal()) {
                return false;
            }
        }

        if ($item->getItemType() == CS_DISCUSSION_TYPE) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request->get('_route') == 'app_discussion_createarticle') {
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

    private function canAnnotate($item, $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if ($userStatus == 2 || $userStatus == 3) { // user & moderator
            $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
            return !$currentRoom->isArchived();
        }

        return false;
    }

    private function canParticipate($item, $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if ($userStatus == 2 || $userStatus == 3 || $userStatus == 4) { // user, moderator & read-only user
            $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
            return !$currentRoom->isArchived();
        }

        return false;
    }

    private function canModerate($item, $currentUser)
    {
        if ($currentUser->getStatus() == 3) {
            return true;
        }

        return false;
    }

    private function canEnter($item, $currentUser, $user)
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

        if ($roomItem->getType() === 'userroom') {
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

    private function hasUserroomItemPrivileges($item, $currentUser)
    {
        $contextItem = $item->getContextItem();
        if ($contextItem !== null &&
            $contextItem->getType() === 'userroom' &&
            $this->canParticipate($item, $currentUser)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Checks whether the given user is a parent moderator for the given room.
     *
     * @param cs_user_item $user
     * @param cs_room_item|null $room
     * @return bool
     */
    private function isParentModeratorForRoom(cs_user_item $user, ?cs_room_item $room): bool
    {
        if (!$room) {
            return false;
        }

        return $this->userService->userIsParentModeratorForRoom($room, $user);
    }
}
