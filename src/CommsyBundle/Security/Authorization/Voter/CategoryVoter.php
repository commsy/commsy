<?php
namespace CommsyBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CategoryVoter implements VoterInterface
{
    const EDIT = 'CATEGORY_EDIT';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::EDIT,
        ));
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // check if the voter is used correct, only allow one attribute
        // this isn't a requirement, it's just one easy way for you to
        // design your voter
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException('Only one attribute is allowed');
        }

        // set the attribute to check against
        $attribute = $attributes[0];

        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // get current logged in user
        // $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        // if (!$user instanceof UserInterface) {
        //     return VoterInterface::ACCESS_DENIED;
        // }
        
        switch ($attribute) {
            case self::EDIT:
                $currentUser = $this->legacyEnvironment->getCurrentUserItem();

                // categories are editable if tags are editable by all or
                // the user is moderator
                if ($currentUser->isUser()) {
                    $currentContext = $this->legacyEnvironment->getCurrentContextItem();

                    if ($currentContext->isTagEditedByAll() || $currentUser->isModerator()) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }

                // TODO:
                // // my stack check
                // if (isset($this->_data["roomId"])) {
                //     $roomId = $this->_data["roomId"];
                //     $ownRoomItem = $currentUser->getOwnRoom();
                    
                //     if ($roomId === $ownRoomItem->getItemID()) {
                //         return true;
                //     }
                // }
                break;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}