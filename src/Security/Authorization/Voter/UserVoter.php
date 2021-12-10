<?php
namespace App\Security\Authorization\Voter;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const MODERATOR = 'MODERATOR';
    const ROOM_MODERATOR = 'ROOM_MODERATOR';
    const PARENT_ROOM_MODERATOR = 'PARENT_ROOM_MODERATOR';

    private $legacyEnvironment;
    private $userService;
    private $roomService;

    public function __construct(LegacyEnvironment $legacyEnvironment, UserService $userService, RoomService $roomService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
        $this->roomService = $roomService;
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::MODERATOR,
            self::ROOM_MODERATOR,
            self::PARENT_ROOM_MODERATOR,
        ));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $roomId = $object;
        /** @var cs_room_item $room */
        $room = $this->roomService->getRoomItem($roomId);

        switch ($attribute) {
            case self::MODERATOR:
                return $this->isModerator($currentUser);

            case self::ROOM_MODERATOR:
                return $this->isModeratorForRoom($currentUser, $room);

            case self::PARENT_ROOM_MODERATOR:
                return $this->isParentModeratorForRoom($currentUser, $room);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * Checks whether the given user is a moderator in the user's context.
     *
     * @param cs_user_item $user
     * @return bool
     */
    private function isModerator(cs_user_item $user): bool
    {
        return $user->isModerator();
    }

    /**
     * Checks whether the given user is a moderator in the given room.
     *
     * @param cs_user_item $user
     * @param cs_room_item|null $room
     * @return bool
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
