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

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const MODERATOR = 'MODERATOR';
    public const ROOM_MODERATOR = 'ROOM_MODERATOR';
    public const PARENT_ROOM_MODERATOR = 'PARENT_ROOM_MODERATOR';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, private UserService $userService, private RoomService $roomService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, [self::MODERATOR, self::ROOM_MODERATOR, self::PARENT_ROOM_MODERATOR]);
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $roomId = $object;
        /** @var cs_room_item $room */
        $room = $this->roomService->getRoomItem($roomId);

        return match ($attribute) {
            self::MODERATOR => $this->isModerator($currentUser),
            self::ROOM_MODERATOR => $this->isModeratorForRoom($currentUser, $room),
            self::PARENT_ROOM_MODERATOR => $this->isParentModeratorForRoom($currentUser, $room),
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
}
