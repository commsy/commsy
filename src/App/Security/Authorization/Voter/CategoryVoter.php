<?php
namespace App\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CategoryVoter extends Voter
{
    const EDIT = 'CATEGORY_EDIT';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::EDIT,
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

        $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        
        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($currentRoom, $currentUser);

                // TODO:
                // // my stack check
                // if (isset($this->_data["roomId"])) {
                //     $roomId = $this->_data["roomId"];
                //     $ownRoomItem = $currentUser->getOwnRoom();
                    
                //     if ($roomId === $ownRoomItem->getItemID()) {
                //         return true;
                //     }
                // }
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit($currentRoom, $currentUser)
    {
        // categories are not editable in archived rooms
        if ($currentRoom->isArchived()) {
            return false;
        }

        // categories are editable if tags are editable by all or
        // the user is moderator
        if ($currentUser->isUser()) {
            $currentContext = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContext->isTagEditedByAll() || $currentUser->isModerator()) {
                return true;
            }
        }

        return false;
    }
}