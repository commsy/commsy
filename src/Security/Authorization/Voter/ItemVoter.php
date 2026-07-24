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
use App\Entity\Files;
use App\Entity\Portal;
use App\Lock\FileLockManager;
use App\Proxy\PortalProxy;
use App\Repository\FilesRepository;
use App\Room\RoomType;
use App\Rubric\RubricType;
use App\Security\Permission\Checker\ItemEditChecker;
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Security\Permission\Resolver\PermissionResolver;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use App\Utils\UserService;
use App\WOPI\Discovery\DiscoveryService;
use cs_environment;
use cs_item;
use cs_privateroom_item;
use cs_room_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ItemVoter extends Voter
{
    final public const SEE = 'ITEM_SEE';
    final public const EDIT = 'ITEM_EDIT';
    final public const NEW = 'ITEM_NEW';
    final public const ANNOTATE = 'ITEM_ANNOTATE';
    final public const PARTICIPATE = 'ITEM_PARTICIPATE';
    final public const MODERATE = 'ITEM_MODERATE';
    final public const OWN = 'ITEM_OWN';
    final public const ENTER = 'ITEM_ENTER';
    final public const USERROOM = 'ITEM_USERROOM';
    final public const DELETE = 'ITEM_DELETE';
    final public const EDIT_LOCK = 'ITEM_EDIT_LOCK';
    final public const FILE_LOCK = 'ITEM_FILE_LOCK';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly ItemService $itemService,
        private readonly RoomService $roomService,
        private readonly UserService $userService,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileLockManager $fileLockManager,
        private readonly DiscoveryService $discoveryService,
        private readonly ItemEditChecker $itemEditChecker,
        private readonly PermissionResolver $permissionResolver,
        private readonly LegacyPermissionBridge $legacyBridge,
        private readonly CurrentContextResolver $currentContextResolver,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [
            self::SEE,
            self::EDIT,
            self::NEW,
            self::ANNOTATE,
            self::PARTICIPATE,
            self::MODERATE,
            self::OWN,
            self::ENTER,
            self::USERROOM,
            self::DELETE,
            self::EDIT_LOCK,
            self::FILE_LOCK
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // get current logged in user
        $user = $token->getUser();

        if ($user instanceof Account && 'root' === $user->getUsername()) {
            return true;
        }

        $item = null;
        if ($subject instanceof Portal) {
            $item = new PortalProxy($subject, $this->legacyEnvironment);
        } else {
            $itemId = $subject;
            if ($itemId) {
                $item = $this->itemService->getTypedItem($itemId);

                if (!$item) {
                    $portal = $this->entityManager->getRepository(Portal::class)->find($itemId);

                    if ($portal) {
                        $item = new PortalProxy($portal, $this->legacyEnvironment);
                    }
                }
            }
        }

        // ITEM_ENTER WORKAROUND — NOT A FIX.
        //
        // The block above resolves a numeric subject through the legacy
        // items path first (ItemService->getTypedItem) and only falls back
        // to PortalRepository if that returns null. Portal ids and
        // items.item_id share the same numeric AUTO_INCREMENT range, so
        // a row in `items` (e.g. type='user' or type='server') can shadow
        // a portal with the same id. For ITEM_ENTER, where the subject is
        // always meant to be a room or portal, this leaks the wrong
        // subject into canEnter and surfaces as "Call to undefined method
        // isPrivateRoom()" or a 302 redirect to a fallback URL.
        //
        // The PROPER fix is to stop resolving portals through the legacy
        // items table at all: portals already live in the `portal` table
        // as Doctrine entities, and PortalRepository is the canonical
        // source. To get there we need to (a) standardize on Portal
        // entities (or a typed wrapper) for portal subjects across all
        // voter callers, and (b) drop the items-based portal lookup in
        // ItemService / cs_environment::getCurrentContextItem. That is a
        // larger migration tracked separately.
        //
        // Until then, force a portal lookup for ENTER when the resolved
        // item is not actually a room — this keeps ENTER deterministic
        // without changing any callers.
        if (
            self::ENTER === $attribute
            && $item !== null
            && !$item instanceof cs_room_item
            && !$item instanceof PortalProxy
        ) {
            $portal = $this->entityManager->getRepository(Portal::class)->find($subject);
            $item = $portal ? new PortalProxy($portal, $this->legacyEnvironment) : null;
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

                case self::OWN:
                    return $this->isOwner($item, $currentUser);

                case self::ENTER:
                    return $this->canEnter($item, $currentUser, $user);

                case self::USERROOM:
                    return $this->hasUserroomItemPrivileges($item, $currentUser);

                case self::DELETE:
                    return $this->canDelete($item, $currentUser);

                case self::EDIT_LOCK:
                    return $this->canEditLock($item, $currentUser);

                case self::FILE_LOCK:
                    return $this->canFileLock($item);
            }
        } else {
            if ($attribute === self::NEW) {
                // NOTE: by using `isGuest()` (instead of `isReallyGuest()`) we'll also catch logged-in users who
                // are currently viewing a community room with guest access which they are no member of
                if ($currentUser->isGuest() || $currentUser->isOnlyReadUser() || $currentUser->isRequested()) {
                    return false;
                }

                $currentRoom = $this->currentContextResolver->getContextItem();

                return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
            }
        }

        return false;
    }

    private function canView(cs_item $item, cs_user_item $currentUser): bool
    {
        if ($item->isDeleted()) {
            return false;
        }

        return $this->permissionResolver->canSee($item, $currentUser, $this->legacyBridge->currentRoom());
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

        $itemType = $item->getItemType();

        if (RoomType::Project->value == $itemType || RoomType::Community->value == $itemType) {
            if ($item->isLockedByModerator() && !$this->userService->userIsPortalModerator($currentUser)) {
                return false;
            }
        }

        if (RubricType::Date->value == $itemType) {
            if ($item->isExternal()) {
                return false;
            }
        }

        if (RubricType::Discussion->value == $itemType) {
            $request = $this->requestStack->getCurrentRequest();
            if ('app_discussion_createanswer' == $request?->attributes->get('_route')) {
                return true;
            }
        }

        if ($currentUser->isReadOnlyUser()) {
            if ($currentUser->getItemId() == $item->getItemId()) {
                return true;
            }
        }

        return $this->permissionResolver->canEdit($item, $currentUser, $this->legacyBridge->currentRoom());
    }

    private function canAnnotate(cs_item $item, cs_user_item $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if (2 == $userStatus || 3 == $userStatus) { // user & moderator
            $currentRoom = $this->currentContextResolver->getContextItem();

            return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
        }

        return false;
    }

    private function canParticipate(cs_item $item, cs_user_item $currentUser)
    {
        $userStatus = $currentUser->getStatus();
        if (2 == $userStatus || 3 == $userStatus || 4 == $userStatus) { // user, moderator & read-only user
            $currentRoom = $this->currentContextResolver->getContextItem();

            return !(method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived());
        }

        return false;
    }

    private function canModerate(cs_item $item, cs_user_item $currentUser)
    {
        if (3 == $currentUser->getStatus()) {
            return true;
        }

        return false;
    }

    private function isOwner(cs_item $item, cs_user_item $currentUser)
    {
        if ($item->getCreatorID() === $currentUser->getItemID()) {
            return true;
        }

        return false;
    }

    private function canEnter(cs_item|PortalProxy $item, $currentUser, $user): bool
    {
        if ($item->isPrivateRoom()) {
            // A private room is a single user's personal dashboard. Only its
            // owner may enter it. Without this check anyone who knows (or
            // guesses) a private-room id could reach that user's dashboard
            // and, via /room/{id}/all, the portal-wide room list — even as a
            // guest. Identity is keyed by account_id (see cs_user_item).
            // (The root account is already short-circuited in voteOnAttribute.)
            if (!$item instanceof cs_privateroom_item || !$user instanceof Account) {
                return false;
            }

            $owner = $item->getOwnerUserItem();

            return $owner !== null
                && $owner->getAccountID() !== null
                && $owner->getAccountID() === $user->getId();
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

        // At this point the ENTER workaround above guarantees a
        // cs_room_item; narrow for static analysis and the bridge's
        // type contract.
        if (!$item instanceof cs_room_item) {
            return false;
        }

        // Voter-specific guard: a soft-deleted Room is never enterable,
        // even via the identity-triple path. The bridge doesn't fold
        // this in because none of the non-Voter callers need it (their
        // input is already filtered upstream).
        $room = $this->legacyBridge->roomFromLegacy($item);
        if ($room === null || $room->getDeletionDate() !== null) {
            return false;
        }

        return $this->legacyBridge->userCanEnter($item, $currentUser);
    }

    private function canDelete(cs_item|PortalProxy $item, $currentUser)
    {
        $roomItem = $this->roomService->getRoomItem($item->getItemID());
        if (!$roomItem) {
            return false;
        }

        if ('userroom' === $roomItem->getType()) {
            return false;
        }

        if ($roomItem->isDeleted()) {
            return false;
        }

        // the parent moderator can always delete (or lock) a room even if (s)he cannot view/enter
        // it; this is needed so that a community room moderator can delete/(un)lock any contained
        // project room even if (s)he isn't a member of that project room
        if ($this->userService->userIsParentModeratorForRoom($roomItem, $currentUser)) {
            return true;
        }

        if ($this->userService->userIsModeratorForRoom($roomItem, $currentUser)) {
            return true;
        }

        return false;
    }

    private function canEditLock(cs_item $item, cs_user_item $currentUser): bool
    {
        if ($currentUser->isRoot()) {
            return true;
        }

        return $this->itemEditChecker->canEditLock($item->getItemID());
    }

    private function canFileLock(cs_item $item): bool
    {
        if ($this->discoveryService->getWOPIDiscovery() !== null) {
            /** @var FilesRepository $fileRepository */
            $fileRepository = $this->entityManager->getRepository(Files::class);
            $files = $fileRepository->findBy(['filesId' => $item->getFileIDArray()]);
            foreach ($files as $file) {
                if ($this->fileLockManager->isLocked($file)) {
                    return false;
                }
            }
        }

        return true;
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
}
