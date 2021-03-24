<?php


namespace App\Security\Authorization\Voter;


use App\Entity\Account;
use App\Utils\UserService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToUserVoter extends Voter
{
    private $security;
    private $userService;

    /**
     * SwitchToUserVoter constructor.
     * @param $security
     */
    public function __construct(Security $security, UserService $userService)
    {
        $this->security = $security;
        $this->userService = $userService;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['CAN_SWITCH_USER'])
            && $subject instanceof UserInterface;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Account $userAccount */ $userAccount = $token->getUser();
        /** @var \cs_user_item $userObject */ $userObject = $this->userService->getPortalUser($userAccount)->getFirst();

        if (!$userAccount instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        if(!$userObject->isDeactivatedLoginAsAnotherUser()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return true;
        }

        return false;
    }
}