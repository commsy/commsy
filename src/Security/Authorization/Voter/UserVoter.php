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
    const PROJECT_MODERATOR = 'PROJECT_MODERATOR';

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
            self::PROJECT_MODERATOR,
        ));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $itemId = $object;
        $item = $this->itemService->getTypedItem($itemId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        switch ($attribute) {
            case self::MODERATOR:
                return $this->isModerator($currentUser);

            case self::PARENT_MODERATOR:
                return $this->isParentModerator($currentUser, $item);

            case self::PROJECT_MODERATOR:
                return $this->isProjectModerator($currentUser, $item);
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

    private function isProjectModerator($currentUser, $item){
        return $this->isCurrentUserModerator($item->getItemId(), [$currentUser]);
    }

    /**
     * @param $item
     * @param $currentUser
     * @return bool
     */
    private function isParentModerator($currentUser, $item):bool
    {
        $roomType = $item->getType();
        if($roomType == 'project'){
            $linkedCommunities = $item->getCommunityList();
            foreach($linkedCommunities as $linkedCommunity){
                $communityId = $linkedCommunity->getItemId();
                if($this->isCurrentUserModerator($communityId, [$currentUser])){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $roomId int
     * @param $currentUsers array
     * @return bool
     *
     * Delivers a boolean answer whether the current user is moderator of given roomId.
     */
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
        $userService = $this->userService;
        $userService->resetLimits();
        $moderators = $userService->getModeratorsForContext($roomId);
        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }
        return $moderatorIds;
    }
}