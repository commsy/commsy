<?php
namespace CommsyBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\ItemService;

class ItemVoter extends Voter
{
    const SEE = 'ITEM_SEE';
    const EDIT = 'ITEM_EDIT';

    private $legacyEnvironment;
    private $itemService;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::SEE,
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
        
        $itemId = $object;

        $item = $this->itemService->getTypedItem($itemId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        if ($item) {
            switch ($attribute) {
                case self::SEE:
                    return $this->canView($item, $currentUser);

                case self::EDIT:
                    return $this->canEdit($item, $currentUser);
            }
        } else if ($itemId == 'NEW') {
            if ($attribute == self::EDIT) {
                if ($currentUser->isOnlyReadUser()) {
                    return false;
                }

                $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

                return !$currentRoom->isArchived();
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

    private function canEdit($item, $currentUser)
    {
        if ($item->getContextItem()->isArchived()) {
            return false;
        }

        if ($item->hasLocking()) {
            if ($item->isLocked()) {
                return false;
            }
        }

        if ($item->getType = CS_DATE_TYPE) {
            if ($item->isExternal()) {
                return false;
            }
        }

        if ($item->mayEdit($currentUser)) {
            return true;
        }

        return false;
    }
}