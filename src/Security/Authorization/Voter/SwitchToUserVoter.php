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
     * @param Security $security
     * @param UserService $userService
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
        /** @var Account $account */
        $account = $token->getUser();

        if (!$account instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        /** @var \cs_user_item $portalUser */
        $portalUser = $this->userService->getPortalUser($account);

        // check if the user is allowed to impersonate by flag
        if ($portalUser->getCanImpersonateAnotherUser()) {
            // check if the impersonate grant is expired
            $now = new \DateTimeImmutable();
            $expiryDate = $portalUser->getImpersonateExpiryDate();
            if ($expiryDate === null || $expiryDate >= $now) {
                return true;
            }
        }

        return false;
    }
}