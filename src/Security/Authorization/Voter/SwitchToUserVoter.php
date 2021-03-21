<?php


namespace App\Security\Authorization\Voter;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToUserVoter extends Voter
{
    private $security;

    /**
     * SwitchToUserVoter constructor.
     * @param $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {

        return in_array($attribute, ['ROLE_ALLOWED_TO_SWITCH'])
            && $subject instanceof UserInterface;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            //TODO: Check whether subject is allowed to switch users
            return true;
        }

        return false;
    }
}