<?php


namespace App\Security\Authorization\Voter;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GuestVoter extends Voter
{
    public const GUEST = 'GUEST';

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $entityManager;

    public function __construct(LegacyEnvironment $legacyEnvironment, EntityManagerInterface $entityManager)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
    }

    protected function supports($attribute, $subject)
    {
        return $attribute === self::GUEST;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {

        $contextId = $this->legacyEnvironment->getCurrentContextID();
        $portal = $this->entityManager->getRepository(Portal::class)->find($contextId);

        switch ($attribute) {
            case self::GUEST:
                $authSources = $portal->getAuthSources();
                foreach ($authSources as $authSource) {
                    if ($authSource->getType() === 'guest') {
                        return $authSource->isGuestsMayEnter();
                    }
                }
                break;
        }
        return false;
    }


}