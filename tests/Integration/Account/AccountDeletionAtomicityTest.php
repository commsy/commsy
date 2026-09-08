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

namespace Tests\Integration\Account;

use App\Account\AccountDeleter;
use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Services\LegacyEnvironment;
use App\User\UserListBuilder;
use App\User\UserMembershipDeleter;
use App\Utils\UserService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that an account deletion is all or nothing.
 *
 * It writes across six phases — memberships, room content, files, the
 * private room, the straggler sweep, the account row — and the person is
 * already logged out when it runs, in a background worker. A failure
 * halfway used to leave that half applied, with the Messenger message in
 * the failed transport and nobody looking.
 *
 * The private room deletion is made to throw because it sits late in the
 * order, after the memberships and their content are stamped. Assertions
 * read through DBAL: `wrapInTransaction()` closes the entity manager when
 * its callback throws.
 */
final class AccountDeletionAtomicityTest extends KernelTestCase
{
    private Connection $connection;
    private Account $account;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testAFailureHalfwayLeavesNothingBehind(): void
    {
        $this->enableCascade();
        $announcement = AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);

        try {
            $this->deleterFailingAtThePrivateRoom()->delete($this->account);
            self::fail('the deletion was supposed to fail at the private room');
        } catch (RuntimeException $e) {
            self::assertSame('private room deletion failed', $e->getMessage());
        }

        // Everything the earlier phases wrote has to be gone again.
        self::assertNull(
            $this->column('announcement', 'deletion_date', $announcement->getItemId()),
            'the entry was soft-deleted before the failure and must be back'
        );
        self::assertNotNull(
            $this->column('announcement', 'creator_id', $announcement->getItemId()),
            'and its authorship must be back too'
        );
        self::assertNull(
            $this->column('user', 'deletion_date', $this->roomUser->getItemId(), 'item_id'),
            'the membership must not stay stamped'
        );
        self::assertNotNull(
            $this->connection->fetchOne('SELECT id FROM accounts WHERE id = :id', ['id' => $this->account->getId()]),
            'and the account must still exist'
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
    }

    /**
     * The real deleter with one collaborator swapped. Built by hand rather
     * than through the container, which refuses to replace a service it has
     * already handed out.
     */
    private function deleterFailingAtThePrivateRoom(): AccountDeleter
    {
        $container = self::getContainer();

        return new AccountDeleter(
            $container->get(UserListBuilder::class),
            $container->get(UserService::class),
            $container->get(UserRepository::class),
            $container->get(EntityManagerInterface::class),
            $container->get(MessageBusInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(UserMembershipDeleter::class),
            new ThrowingPrivateRoomDeleter(),
            $container->get(LegacyEnvironment::class),
        );
    }

    private function enableCascade(): void
    {
        $this->account->getPortal()
            ->setAllowUserDefinedDeletionStrategy(true)
            ->setCascadingUserDeletionStrategy(false);

        self::getContainer()->get(AccountSettingsManager::class)->storeSetting(
            $this->account,
            AccountSetting::USER_DELETION_CASCADING_ITEMS,
            ['enabled' => true]
        );

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($this->account);
        $em->flush();
    }

    private function column(string $table, string $column, int $id, string $key = 'item_id'): ?string
    {
        $value = $this->connection->fetchOne(
            sprintf('SELECT %s FROM %s WHERE %s = :id', $column, $table, $key),
            ['id' => $id]
        );

        return $value === false || $value === null ? null : (string) $value;
    }
}

/**
 * Fails at the phase that runs after the memberships and their content
 * have been stamped.
 */
final class ThrowingPrivateRoomDeleter extends PrivateRoomDeleter
{
    public function __construct() {}

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        throw new RuntimeException('private room deletion failed');
    }
}
