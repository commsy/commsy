<?php


namespace App\Security;


use App\Entity\Account;
use App\Exception\AccountLockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof Account) {
            return;
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if (!$user instanceof Account) {
            return;
        }

        /** @var $user Account */
        if ($user->isLocked()) {
            throw new AccountLockedException('Account is locked.');
        }
    }
}