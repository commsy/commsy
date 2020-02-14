<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PortalModeratorVoter extends Voter
{
    public const PORTAL_MODERATOR = 'PORTAL_MODERATOR';

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject)
    {
        return $subject instanceof Portal && $attribute === self::PORTAL_MODERATOR;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access    
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($user->getUsername() === 'root') {
            return true;
        }

        switch ($attribute) {
            case self::PORTAL_MODERATOR:
                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
                if ($currentUserItem) {
                    /** @var $subject Portal */
                    if (
                        (int) $currentUserItem->getStatus() === 3 &&
                        $subject->getDeletionDate() === null &&
                        (int) $currentUserItem->getContextID() === $subject->getId()
                    ) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}
