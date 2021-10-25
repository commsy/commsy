<?php
namespace App\Tests\Step\Functional;

use App\Entity\Portal;
use App\Tests\FunctionalTester;

class User extends FunctionalTester
{
    public function amLoggedInAsUser(Portal $portal, string $username, string $password)
    {
        $I = $this;

        $I->amOnRoute('app_login', [
            'context' => $portal->getId(),
        ]);

        $I->fillField('#inputEmail', $username);
        $I->fillField('#inputPassword', $password);
        $I->click('button[name=login_local]');

//        /** @var Account $userAccount */
//        $userAccount = $I->grabEntityFromRepository(Account::class, [
//            'username' => '...',
//        ]);
//        $I->amLoggedInAs($userAccount);
    }
}