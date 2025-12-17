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
use function Zenstruck\Foundry\Persistence\assert_not_persisted;
use function Zenstruck\Foundry\Persistence\assert_persisted;

class AccountManagerTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    public function testDeleteAccount(): void
    {
        self::bootKernel();

        // Fresh account from factory
        $account = AccountFactory::createOne();
        assert_persisted($account);
        $this->getAccountManager()->delete($account);
        assert_not_persisted($account);
    }

    public function testDeleteAccountWithSettings(): void
    {
        self::bootKernel();

        // Fresh account from factory
        $account = AccountFactory::createOne();
        assert_persisted($account);

        // Settings are not persisted by the manager
        $settingsManager = $this->getAccountSettingsManager();
        $settingsManager->storeSetting(
            $account,
            \App\Account\AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION,
            ['enabled' => true]
        );

        $entityManager = $this->getEntityManager();
        $entityManager->persist($account);
        $entityManager->flush();

        $accountSettingsRepository = $entityManager->getRepository(AccountSetting::class);
        $accountSettings = $accountSettingsRepository->findOneBy(['account' => $account]);
        $this->assertNotNull($accountSettings);

        $this->getAccountManager()->delete($account);
        assert_not_persisted($account);
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
