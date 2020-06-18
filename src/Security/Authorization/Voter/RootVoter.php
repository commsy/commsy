<?php


namespace App\Security\Authorization\Voter;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RootVoter extends Voter
{
    public const ROOT = 'ROOT';

    protected function supports($attribute, $subject)
    {
        return $attribute === self::ROOT;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return $user->getUsername() === 'root';
    }
}