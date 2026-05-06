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
use App\Repository\UserRepository;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    final public const MODERATOR = 'MODERATOR';
    final public const ROOM_MODERATOR = 'ROOM_MODERATOR';
    final public const PARENT_ROOM_MODERATOR = 'PARENT_ROOM_MODERATOR';
    final public const PORTAL_MODERATOR = 'PORTAL_MODERATOR';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly UserService $userService,
        private readonly RoomService $roomService,
        private readonly UserRepository $userRepository,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [
            self::MODERATOR,
            self::ROOM_MODERATOR,
            self::PARENT_ROOM_MODERATOR,
            self::PORTAL_MODERATOR,
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // Account=root short-circuit (matches ItemVoter top-level). Root
        // bypasses every UserVoter attribute — this is the same shortcut
        // the now-deleted PortalModeratorVoter used for PORTAL_MODERATOR,
        // generalized to all UserVoter attributes for consistency.
        $tokenUser = $token->getUser();
        if ($tokenUser instanceof Account && 'root' === $tokenUser->getUserIdentifier()) {
            return true;
        }

        if (self::PORTAL_MODERATOR === $attribute) {
            return $tokenUser instanceof Account
                && $this->isPortalModerator($tokenUser, $subject);
        }

        // The remaining attributes still ride on the legacy currentUserItem
        // because their underlying logic (UserService::userIsParentModeratorForRoom,
        // cs_user_item::getRelatedUserItemInContext, …) hasn't been ported yet.
        // They will follow when we tackle ROOM_MODERATOR / PARENT_ROOM_MODERATOR
        // in a later phase.
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if (self::MODERATOR === $attribute) {
            return $this->isModerator($currentUser);
        }

        /** @var cs_room_item|null $room */
        $room = $this->roomService->getRoomItem((int) $subject);

        return match ($attribute) {
            self::ROOM_MODERATOR => $this->isModeratorForRoom($currentUser, $room),
            self::PARENT_ROOM_MODERATOR => $this->isParentModeratorForRoom($currentUser, $room),
            default => throw new LogicException('Unhandled UserVoter attribute: ' . $attribute),
        };
    }

    /**
     * Checks whether the given user is a moderator in the user's context.
     */
    private function isModerator(cs_user_item $user): bool
    {
        return $user->isModerator();
    }

    /**
     * Checks whether the given user is a moderator in the given room.
     */
    private function isModeratorForRoom(cs_user_item $user, ?cs_room_item $room): bool
    {
        if (!$room) {
            return false;
        }

        $roomUser = $user->getRelatedUserItemInContext($room->getItemID());
        if (!$roomUser) {
            return false;
        }

        return $roomUser->isModerator();
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

    /**
     * Whether the account is moderator of its own portal AND that portal
     * is alive (not soft-deleted). Replaces the now-deleted
     * PortalModeratorVoter — the deletion-date check used to live there
     * but was effectively dead code: with Symfony's affirmative strategy
     * this voter granted regardless of the subject's deletion state.
     *
     * Subject handling:
     *   - {@see Portal} entity → cross-portal check against the account's
     *     own portal; the entity itself is the deletion-date source of truth.
     *   - int (portal id) → cross-portal check; deletion check on the
     *     account's own portal (the only one a non-root user can act in).
     *   - null → no cross-portal check; deletion check on the account's
     *     own portal.
     */
    private function isPortalModerator(Account $account, mixed $subject = null): bool
    {
        $accountPortal = $account->getPortal();
        if ($accountPortal === null) {
            return false;
        }
        $accountPortalId = $accountPortal->getId();

        if ($subject instanceof Portal && $subject->getId() !== $accountPortalId) {
            return false;
        }
        if (is_int($subject) && $subject !== $accountPortalId) {
            return false;
        }

        $portal = $subject instanceof Portal ? $subject : $accountPortal;
        if ($portal->getDeletionDate() !== null) {
            return false;
        }

        $portalUser = $this->userRepository->findInContext($account, $accountPortalId);
        return $portalUser !== null && $portalUser->isModerator();
    }
}
