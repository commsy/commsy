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

use App\Database\FixContext;
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
final class FixContextTest extends KernelTestCase
{
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
    }

    public function testRowsInAVanishedContextGoAndTheOthersStay(): void
    {
        $keptProfile = $this->profileIn($this->room());
        $vanished = $this->room();
        $vanishedProfile = $this->profileIn($vanished);
        $this->connection()->delete('room', ['item_id' => $vanished->getItemId()]);

        self::assertTrue($this->fixContext()->resolve($this->io()));

        self::assertSame(1, $this->rows('user', $keptProfile->getItemId()));
        self::assertSame(1, $this->rows('items', $keptProfile->getItemId()));
        self::assertSame(0, $this->rows('user', $vanishedProfile->getItemId()));
        self::assertSame(0, $this->rows('items', $vanishedProfile->getItemId()));
    }

    public function testAProfileInAVanishedContextGoesThoughStillNamedAsCreator(): void
    {
        $vanished = $this->room();
        $profile = $this->profileIn($vanished);
        $names = ['creator_id' => $profile->getItemId(), 'modifier_id' => $profile->getItemId()];
        $createdRoom = $this->room();
        $this->connection()->update('room', $names, ['item_id' => $createdRoom->getItemId()]);
        $otherProfile = RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $this->account->getPortal(), 'authSource' => $this->account->getAuthSource()]),
            'room' => $createdRoom,
        ]);
        $this->connection()->update('user', $names, ['item_id' => $otherProfile->getItemId()]);
        $this->connection()->delete('room', ['item_id' => $vanished->getItemId()]);

        self::assertTrue($this->fixContext()->resolve($this->io()));

        self::assertSame(0, $this->rows('user', $profile->getItemId()));
        foreach (['room' => $createdRoom->getItemId(), 'user' => $otherProfile->getItemId()] as $table => $itemId) {
            self::assertSame(
                ['creator_id' => null, 'modifier_id' => null],
                $this->connection()->fetchAssociative("SELECT creator_id, modifier_id FROM $table WHERE item_id = ?", [$itemId]),
                $table,
            );
        }
    }

    private function room(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function profileIn(Room $room): User
    {
        return RoomUserFactory::createOne(['account' => $this->account, 'room' => $room]);
    }

    private function rows(string $table, int $itemId): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE item_id = ?", [$itemId]);
    }

    private function io(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), new BufferedOutput());
    }

    private function fixContext(): FixContext
    {
        return self::getContainer()->get(FixContext::class);
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
