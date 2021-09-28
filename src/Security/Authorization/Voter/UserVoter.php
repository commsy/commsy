<?php
namespace App\Security\Authorization\Voter;

use App\Utils\ItemService;
use App\Utils\UserService;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use cs_list;
use App\Services\LegacyEnvironment;

class UserVoter extends Voter
{
    const MODERATOR = 'MODERATOR';
    const PARENT_MODERATOR = 'PARENT_MODERATOR';
    const PROJECT_MODERATOR = 'PROJECT_MODERATOR';

    private $legacyEnvironment;
    private $userService;
    private $itemService;

    public function __construct(LegacyEnvironment $legacyEnvironment, UserService $userService, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
        $this->itemService = $itemService;
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
        
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $itemId = $object;
        $item = $this->itemService->getTypedItem($itemId);

        switch ($attribute) {
            case self::MODERATOR:
                return $this->isModerator($currentUser);

            case self::PARENT_MODERATOR:
                return $this->isParentModerator($currentUser, $item);

            case self::PROJECT_MODERATOR:
                return $this->isProjectModerator($currentUser, $itemId);

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

    private function isProjectModerator($currentUser, $itemId){
        return $this->isCurrentUserModerator($itemId, [$currentUser]);
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
            $link_item_manager = $this->legacyEnvironment->getLinkItemManager();
            $link_item_manager->setLinkedItemLimit($item);
            $link_item_manager->setTypeLimit("community");
            $link_item_manager->setRoomLimit($item->getContextID());
            $link_item_manager->select();
            $link_list = $link_item_manager->get();
            $result_list = new cs_list();
            $link_item = $link_list->getFirst();
            while ($link_item) {
                $result_list->add($link_item->getLinkedItem($item));
                $link_item = $link_list->getNext();
            }
            $linkedCommunities = $result_list;
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