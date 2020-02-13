<?php
namespace App\Security\Authorization\Voter;

use App\Utils\ItemService;
use App\Utils\UserService;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Services\LegacyEnvironment;

class UserVoter extends Voter
{
    const MODERATOR = 'MODERATOR';
    const PARENT_MODERATOR = 'PARENT_MODERATOR';

    private $legacyEnvironment;
    private $itemService;
    private $userService;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->userService = $userService;
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::MODERATOR,
            self::PARENT_MODERATOR,
        ));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        // get current logged in user
        // $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        // if (!$user instanceof User) {
        //     return false
        // }

        $itemId = $object;
        $item = $this->itemService->getTypedItem($itemId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        switch ($attribute) {
            case self::MODERATOR:
                return $this->isModerator($currentUser);

            case self::PARENT_MODERATOR:
                return $this->isParentModerator($currentUser, $item, $item);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function isModerator($currentUser)
    {
        if ($currentUser->isModerator()) {
            return true;
        }

        return false;
    }


    /**
     * @param $item
     * @param $currentUser
     * @return bool
     */
    private function isParentModerator($currentUser, $item):bool
    {
        $roomType = $item->getType();
        $currentRoomId = $item->getItemId();
        $currentUserIsModerator = $this->isCurrentUserModerator($currentRoomId, [$currentUser]);

        if($currentUserIsModerator and $roomType == 'community'){
            return true;
        }

        return false;
    }

    private function isCurrentUserModerator($roomId, $currentUsers){
        $moderatorIds = $this->accessModeratorIds($roomId);
        foreach ($currentUsers as $selectedId) {
            $relatedUsers = $selectedId->getRelatedUserList()->to_array();
            foreach($relatedUsers as $relatedUser){
                if(($key = array_search($relatedUser->getItemId(), $moderatorIds)) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function accessModeratorIds($roomId){
        $moderators = $this->userService->getModeratorsForContext($roomId);

        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }

        return $moderatorIds;
    }
}