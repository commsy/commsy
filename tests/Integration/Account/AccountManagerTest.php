<?php

declare(strict_types=1);

namespace Tests\Integration\Account;

use App\Account\AccountManager;
use App\Account\AccountSettingsManager;
use App\Entity\AccountSetting;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AccountManagerTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    public function testDeleteAccount(): void
    {
        self::bootKernel();

        // Fresh account from factory
        $account = AccountFactory::createOne();
        $account->_assertPersisted();
        $this->getAccountManager()->delete($account->_real());
        $account->_assertNotPersisted();
    }

    public function testDeleteAccountWithSettings(): void
    {
        self::bootKernel();

        // Fresh account from factory
        $account = AccountFactory::createOne();
        $account->_assertPersisted();

        // Settings are not persisted by the manager
        $settingsManager = $this->getAccountSettingsManager();
        $realAccount = $account->_real();
        $settingsManager->storeSetting(
            $realAccount,
            \App\Account\AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION,
            ['enabled' => true]
        );

        $entityManager = $this->getEntityManager();
        $entityManager->persist($realAccount);
        $entityManager->flush();

        $accountSettingsRepository = $entityManager->getRepository(AccountSetting::class);
        $accountSettings = $accountSettingsRepository->findOneBy(['account' => $realAccount]);
        $this->assertNotNull($accountSettings);

        $this->getAccountManager()->delete($realAccount);
        $account->_assertNotPersisted();
    }

    private function getAccountManager(): AccountManager
    {
        return self::getContainer()->get(AccountManager::class);
    }

    private function getAccountSettingsManager(): AccountSettingsManager
    {
        return self::getContainer()->get(AccountSettingsManager::class);
    }

    private function getEntityManager(): EntityManager
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
