<?php
namespace CommsyBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RubricVoter extends Voter
{
    const RUBRIC_SEE = 'RUBRIC_SEE';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, array(
            self::RUBRIC_SEE,
        ));
    }

    protected function voteOnAttribute($attribute, $rubricName, TokenInterface $token)
    {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        switch ($attribute) {
            case self::RUBRIC_SEE:
                return $this->canView($roomItem, $currentUser, $rubricName);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView($roomItem, $currentUser, $rubric)
    {
        if ($roomItem->isDeleted()) {
            return false;
        }
        if ($rubric == 'user' && $currentUser->isModerator()) {
            return true;
        }
        return in_array($rubric, $roomItem->getAvailableRubrics());
    }

}