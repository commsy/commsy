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

use App\Account\AccountDeleter;
use App\Entity\Account;
use App\Entity\User;
use App\Migrations\Version20260513120000;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Per-step coverage of the cleanup migration ({@see Version20260513120000}).
 *
 * Foundry resets the test DB by running all migrations (`reset.mode: migrate`
 * in `config/packages/zenstruck_foundry.yaml`), so the migration's `up()`
 * already executes once during bootstrap. That run happens against an empty
 * database — apart from the seeded `root` user, which the migration filters
 * out — and therefore only proves the SQL parses and tables exist, not that
 * the cleanup logic does the right thing when orphans are present.
 *
 * The tests below set up specific orphan/divergence scenarios via direct SQL
 * and then invoke `up()` a second time. The migration is idempotent (every
 * UPDATE is guarded against re-running), so the second invocation is safe.
 */
final class CleanupUserConsistencyTest extends KernelTestCase
{
    /**
     * Step 1 — an orphan row (account_id NULL) whose matching account still
     * exists gets its `account_id` restored, no soft-delete.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testRelinksOrphanToMatchingAccount(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        // Reproduce the orphan condition.
        $this->getConnection()->executeStatement(
            'UPDATE user SET account_id = NULL WHERE item_id = :id',
            ['id' => $roomUser->getItemId()]
        );

        $this->runMigration();

        $row = $this->getConnection()->fetchAssociative(
            'SELECT account_id, deletion_date, deleter_id FROM user WHERE item_id = :id',
            ['id' => $roomUser->getItemId()]
        );

        self::assertIsArray($row);
        self::assertSame(
            $account->getId(),
            $row['account_id'] === null ? null : (int) $row['account_id'],
            'Step 1 should have re-linked the orphan to its matching account.'
        );
        self::assertNull($row['deletion_date'], 'Re-linked orphan must not be soft-deleted.');
        self::assertNull($row['deleter_id'], 'Re-linked orphan must not carry a deleter id.');
    }

    /**
     * Step 2 — an orphan whose matching account no longer exists gets
     * soft-deleted, with the audit marker `deleter_id = 0`.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeletesOrphanWithoutMatchingAccount(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        $roomUserItemId = $roomUser->getItemId();

        // Tear down the account — the Phase 1 sweep soft-deletes the room
        // user too, so we manually undo the soft-delete to recreate the
        // historical orphan shape (account gone, row still active).
        self::getContainer()->get(AccountDeleter::class)->delete($account);

        $this->getConnection()->executeStatement(
            'UPDATE user SET deletion_date = NULL, deleter_id = NULL, account_id = NULL
              WHERE item_id = :id',
            ['id' => $roomUserItemId]
        );

        $this->runMigration();

        $userRow = $this->getConnection()->fetchAssociative(
            'SELECT account_id, deletion_date, deleter_id FROM user WHERE item_id = :id',
            ['id' => $roomUserItemId]
        );

        self::assertIsArray($userRow);
        self::assertNull($userRow['account_id'], 'No account exists — account_id must stay NULL.');
        self::assertNotNull($userRow['deletion_date'], 'Step 2 should have soft-deleted the orphan user row.');
        self::assertSame(
            0,
            (int) $userRow['deleter_id'],
            'Cleanup soft-delete must carry deleter_id = 0 as audit marker.'
        );

        // Twin row in items must be soft-deleted too — they're a one-to-one
        // pair (every user row has a matching items row of type='user') and
        // downstream cleanup (e.g. CronHardDelete) reads the items row.
        $itemsRow = $this->getConnection()->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM items WHERE item_id = :id',
            ['id' => $roomUserItemId]
        );
        self::assertIsArray($itemsRow);
        self::assertNotNull($itemsRow['deletion_date'], 'Twin items row must also be soft-deleted.');
        self::assertSame(0, (int) $itemsRow['deleter_id']);
    }

    private function runMigration(): void
    {
        $migration = new Version20260513120000($this->getConnection(), new NullLogger());
        $migration->up(new Schema());
    }

    private function getConnection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
