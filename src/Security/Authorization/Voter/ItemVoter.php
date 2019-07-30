<?php
namespace App\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;

class ItemVoter extends Voter
{
    const SEE = 'ITEM_SEE';
    const EDIT = 'ITEM_EDIT';
    const ANNOTATE = 'ITEM_ANNOTATE';
    const MODERATE = 'ITEM_MODERATE';
    const ENTER = 'ITEM_ENTER';

    private $legacyEnvironment;
    private $itemService;
    private $requestStack;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService, RequestStack $requestStack)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::SEE,
            self::EDIT,
            self::ANNOTATE,
            self::MODERATE,
            self::ENTER,
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

                case self::ANNOTATE:
                    return $this->canAnnotate($item, $currentUser);

                case self::MODERATE:
                    return $this->canModerate($item, $currentUser);

                case self::ENTER:
                    return $this->canEnter($item, $currentUser);
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

    /**
     * @param $item
     * @param \cs_user_item $currentUser
     * @return bool
     */
    private function canEdit($item, \cs_user_item $currentUser): bool
    {
        if ($item->getContextItem()->isArchived()) {
            // users may still edit their own account settings & room profile (which also allows them to leave the room)
            if ($item instanceof \cs_user_item && $item->getItemID() === $currentUser->getItemID()) {
                return true;
            }
            return false;
        }

        if ($item->hasLocking()) {
            if ($item->isLocked()) {
                return false;
            }
        }

        if ($item->getItemType() == CS_DATE_TYPE) {
            if ($item->isExternal()) {
                return false;
            }
        }

        if ($item->getItemType() == CS_DISCUSSION_TYPE) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request->get('_route') == 'app_discussion_createarticle') {
                return true;
            }
        }

        if ($currentUser->isReadOnlyUser()) {
            if ($currentUser->getItemId() == $item->getItemId()) {
                return true;
            }
        }

        if ($item->mayEdit($currentUser)) {
            return true;
        }

        return false;
    }

    private function canAnnotate($item, $currentUser)
    {
        if ($currentUser->getStatus() == 2 || $currentUser->getStatus() == 3) {
            $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
            return !$currentRoom->isArchived();
        }

        return false;
    }

    private function canModerate($item, $currentUser)
    {
        if ($currentUser->getStatus() == 3) {
            return true;
        }

        return false;
    }

    private function canEnter($item, $currentUser)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($item->getItemID());

        if ($item->isPrivateRoom()) {
            return true;
        } else if ($roomItem) {
            if (!$roomItem->isDeleted() && $roomItem->mayEnter($currentUser)) {
                return true;
            }
        } else if ($item->isPortal() && $item->mayEnter($currentUser)) {
            return true;
        }

        return false;
    }
}