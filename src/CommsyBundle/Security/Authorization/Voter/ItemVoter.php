<?php
namespace CommsyBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\ItemService;

class ItemVoter implements VoterInterface
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

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::SEE,
            self::EDIT,
        ));
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function vote(TokenInterface $token, $itemId, array $attributes)
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
        
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $item = $this->itemService->getTypedItem($itemId);

        if ($item) {
            switch ($attribute) {
                case self::SEE:
                    if ($item->maySee($currentUser)) {
                        return VoterInterface::ACCESS_GRANTED;
                    }

                    break;

                case self::EDIT:
                    if ($item->mayEdit($currentUser)) {
                        return VoterInterface::ACCESS_GRANTED;
                    }

                    break;
            }
        } else if ($itemId == 'NEW') {
            if ($attribute == self::EDIT) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}