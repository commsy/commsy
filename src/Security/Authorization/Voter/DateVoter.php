<?php
namespace App\Security\Authorization\Voter;

use App\Services\LegacyEnvironment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DateVoter extends Voter
{
    const EDIT = 'edit';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, array(
            self::EDIT,
        ));
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $date = $subject;
        switch($attribute) {
                case self::EDIT:
                return $this->canEdit($date);
        }

        throw new \LogicException('The code no show.');
    }


    private function canEdit($date)
    {
        return $date->isPublic() || $date->getCreatorID() === $this->legacyEnvironment->getCurrentUserID();
    }
}
