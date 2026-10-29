<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Database\CleanUpProfilesWithoutAccount;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
final class CleanUpProfilesWithoutAccountTest extends KernelTestCase
{
    private Connection $connection;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->account = AccountStory::get('account');
    }

    public function testOnlyLiveProfilesWithoutAccountInExistingContextsGo(): void
    {
        $withAccount = $this->createProfile($this->createRoom());
        // one room each: an account has one live profile per room
        $withoutAccount = $this->withoutAccount($this->createProfile($this->createRoom()));
        $alreadyDeleted = $this->withoutAccount($this->createProfile($this->createRoom()));
        $this->connection->update('user', ['deletion_date' => '2026-01-01 10:00:00', 'deleter_id' => 0], ['item_id' => $alreadyDeleted]);
        $vanishedRoom = $this->createRoom();
        $inVanishedRoom = $this->withoutAccount($this->createProfile($vanishedRoom));
        $this->connection->delete('room', ['item_id' => $vanishedRoom->getItemId()]);

        $this->check()->resolve($this->io());

        self::assertNull($this->deletionDate($withAccount->getItemId()));
        self::assertNotNull($this->deletionDate($withoutAccount));
        self::assertSame('2026-01-01 10:00:00', $this->deletionDate($alreadyDeleted));
        self::assertNull($this->deletionDate($inVanishedRoom));
    }

    public function testTheUsernameCanBeRegisteredAgainAfterTheCheck(): void
    {
        // left behind by a deleted account of that username
        $orphan = $this->withoutAccount($this->createProfile($this->createRoom()));
        $this->connection->update('user', ['user_id' => 'left.behind'], ['item_id' => $orphan]);

        $this->check()->resolve($this->io());

        $again = AccountFactory::createOne([
            'username' => 'left.behind',
            'portal' => $this->account->getPortal(),
            'authSource' => $this->account->getAuthSource(),
        ]);

        self::assertNotNull($again->getId());
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function createProfile(Room $room): User
    {
        return RoomUserFactory::createOne(['account' => $this->account, 'room' => $room]);
    }

    private function withoutAccount(User $profile): int
    {
        $this->connection->update('user', ['account_id' => null], ['item_id' => $profile->getItemId()]);

        return $profile->getItemId();
    }

    private function deletionDate(int $itemId): ?string
    {
        $date = $this->connection->fetchOne('SELECT deletion_date FROM user WHERE item_id = ?', [$itemId]);

        return false === $date ? null : $date;
    }

    private function io(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), new BufferedOutput());
    }

    private function check(): CleanUpProfilesWithoutAccount
    {
        return self::getContainer()->get(CleanUpProfilesWithoutAccount::class);
    }
}
