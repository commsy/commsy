<?php

namespace App\Tests;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Facade\AccountCreatorFacade;
use Codeception\Actor;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;

    public function havePortal(string $title): Portal
    {
        $authSource = new AuthSourceLocal();
        $this->haveInRepository($authSource, [
            'title' => 'Lokal',
            'enabled' => true,
            'default' => true,
            'createRoom' => true,
        ]);

        $portal = new Portal();
        $portal->addAuthSource($authSource);
        $this->haveInRepository($portal, [
            'title' => $title,
            'status' => 1,
        ]);

        return $portal;
    }

    public function haveAccount(Portal $portal, string $username): Account
    {
        /** @var AuthSourceLocal $localAuthSource */
        $localAuthSource = $portal->getAuthSources()->filter(function (AuthSource $authSource) {
            return $authSource->getType() === 'local';
        })->first();

        /** @var Account $account */
        $account = $this->make(Account::class, [
            'authSource' => $localAuthSource,
            'contextId' => $portal->getId(),
            'username' => $username,
        ]);

        /** @var AccountCreatorFacade $accountFacade */
        $accountFacade = $this->grabService(AccountCreatorFacade::class);
        $accountFacade->persistNewAccount($account);

        $this->grabEntityFromRepository(Account::class, [
            'username' => $username,
        ]);

        return $account;
    }
}
