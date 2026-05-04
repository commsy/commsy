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

use App\Entity\Portal;
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
        private readonly RoomService $roomService
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
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        // Subjects can come in three shapes from #[IsGranted]: a numeric
        // room id (`subject: 'roomId'`), a Portal entity from MapEntity
        // (`subject: 'portal'`), or another scalar id. PORTAL_MODERATOR
        // does not need a room item; the others do.
        if ($subject instanceof Portal) {
            $roomId = $subject->getId();
        } else {
            $roomId = (int) $subject;
        }
        /** @var cs_room_item $room */
        $room = $this->roomService->getRoomItem($roomId);

        return match ($attribute) {
            self::MODERATOR => $this->isModerator($currentUser),
            self::ROOM_MODERATOR => $this->isModeratorForRoom($currentUser, $room),
            self::PARENT_ROOM_MODERATOR => $this->isParentModeratorForRoom($currentUser, $room),
            self::PORTAL_MODERATOR => $this->isPortalModerator($currentUser),
            default => throw new LogicException('This code should not be reached!'),
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
     * Checks whether the given user is the portal moderator of the user's portal.
     */
    private function isPortalModerator(cs_user_item $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->userService->userIsPortalModerator($user);
    }
}
