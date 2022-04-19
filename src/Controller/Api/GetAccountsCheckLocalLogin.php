<?php

namespace App\Controller\Api;

use App\Entity\Account;

class GetAccountsCheckLocalLogin
{
    public function __invoke(Account $data): Account
    {
        $account = new Account();
        $account->setFirstname('firstname');
        $account->setLastname('lastname');
        $account->setUsername('username');
        $account->setEmail('e@mail.de');

        return $account;
    }
}