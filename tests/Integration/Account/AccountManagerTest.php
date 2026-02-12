<?php

declare(strict_types=1);

namespace Tests\Integration\Account;

use App\Account\AccountManager;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use App\Entity\AccountSetting;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use function Zenstruck\Foundry\Persistence\assert_not_persisted;
use function Zenstruck\Foundry\Persistence\assert_persisted;
use function Zenstruck\Foundry\Persistence\repository;

class AccountManagerTest extends KernelTestCase
{
    public function testDeleteAccount(): void
    {
        self::bootKernel();

        // Fresh account from factory
        $account = AccountFactory::createOne();
        assert_persisted($account);
        $this->getAccountManager()->delete($account);
        assert_not_persisted($account);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testPropagateAccountDataToProfiles(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        assert_persisted($account);

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        assert_persisted($room);

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        assert_persisted($roomUser);

        /** @var UserRepository $userRepository */
        $userRepository = repository(User::class);

        // There should be 3 users with the same firstname, lastname and email in the database
        $numUsers = $userRepository->count([
            'firstname' => $account->getFirstname(),
            'lastname' => $account->getLastname(),
            'email' => $account->getEmail(),
        ]);
        self::assertEquals(3, $numUsers);

        $oldAccount = clone $account;

        // Set new data
        $account->setFirstname('firstname');
        $account->setLastname('lastname');
        $account->setEmail('e@mail.de');

        $this->getAccountManager()->propagateAccountDataToProfiles($account);

        // Check data propagation
        $numUsers = $userRepository->count([
            'firstname' => $oldAccount->getFirstname(),
            'lastname' => $oldAccount->getLastname(),
            'email' => $oldAccount->getEmail(),
        ]);
        self::assertEquals(0, $numUsers);

        $numUsers = $userRepository->count([
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'e@mail.de',
        ]);
        self::assertEquals(3, $numUsers);
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
