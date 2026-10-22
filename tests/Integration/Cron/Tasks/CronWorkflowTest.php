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

namespace Tests\Integration\Cron\Tasks;

use App\Cron\Tasks\CronWorkflow;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Tests\Factory\AccountFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * A material can be due for resubmission or run out of validity on a given day.
 * On that day the workflow cron mails the people the material names and sets
 * its traffic light, provided the room has that part of the workflow switched on.
 */
final class CronWorkflowTest extends KernelTestCase
{
    use Factories;
    use MailerAssertionsTrait;
    use ResetDatabase;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    public function testAMaterialDueForResubmissionIsMarkedAndItsCreatorMailed(): void
    {
        [$room, $author, $account] = $this->room(['WITHWORKFLOW' => 2, 'WITHWORKFLOWRESUBMISSION' => 2]);
        $itemId = $this->material($room, $author, 'workflow_resubmission_date', [
            'WORKFLOWRESUBMISSION' => '1',
            'WORKFLOWRESUBMISSIONWHO' => 'creator',
            'WORKFLOWRESUBMISSIONTRAFFICLIGHT' => '1_yellow',
        ]);

        $this->runCron();

        self::assertSame('1_yellow', $this->trafficLightOf($itemId));
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', (string) $account->getEmail());
    }

    public function testAMaterialRunningOutOfValidityIsMarkedAndItsCreatorMailed(): void
    {
        [$room, $author, $account] = $this->room(['WITHWORKFLOW' => 2, 'WITHWORKFLOWVALIDITY' => 2]);
        $itemId = $this->material($room, $author, 'workflow_validity_date', [
            'WORKFLOWVALIDITY' => '1',
            'WORKFLOWVALIDITYWHO' => 'creator',
            'WORKFLOWVALIDITYTRAFFICLIGHT' => '2_red',
        ]);

        $this->runCron();

        self::assertSame('2_red', $this->trafficLightOf($itemId));
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', (string) $account->getEmail());
    }

    public function testValidityIsLeftAloneInARoomWithoutIt(): void
    {
        [$room, $author] = $this->room(['WITHWORKFLOW' => 2]);
        $itemId = $this->material($room, $author, 'workflow_validity_date', [
            'WORKFLOWVALIDITY' => '1',
            'WORKFLOWVALIDITYWHO' => 'creator',
            'WORKFLOWVALIDITYTRAFFICLIGHT' => '2_red',
        ]);

        $this->runCron();

        self::assertSame('3_none', $this->trafficLightOf($itemId));
        self::assertEmailCount(0);
    }

    public function testOnlyTheCurrentVersionRunsOutOfValidity(): void
    {
        [$room, $author] = $this->room(['WITHWORKFLOW' => 2, 'WITHWORKFLOWVALIDITY' => 2]);
        $itemId = $this->material($room, $author, 'workflow_validity_date', [
            'WORKFLOWVALIDITY' => '1',
            'WORKFLOWVALIDITYWHO' => 'creator',
            'WORKFLOWVALIDITYTRAFFICLIGHT' => '2_red',
        ]);
        // a newer version without a validity date takes over
        $row = $this->connection->fetchAssociative('SELECT * FROM materials WHERE item_id = ? AND version_id = 0', [$itemId]);
        $this->connection->insert('materials', ['version_id' => 1, 'workflow_validity_date' => null] + $row);

        $this->runCron();

        self::assertSame('3_none', $this->trafficLightOf($itemId, 0));
        self::assertEmailCount(0);
    }

    private function runCron(): void
    {
        self::getContainer()->get(CronWorkflow::class)->run(null);
    }

    /**
     * @param array<string, int> $extras
     *
     * @return array{0: Room, 1: User, 2: Account}
     */
    private function room(array $extras): array
    {
        $account = AccountFactory::createOne(['email' => 'autorin@example.test']);
        $portal = $account->getPortal();
        $room = RoomFactory::createOne([
            'contextId' => $portal->getId(),
            'portal' => $portal,
            'type' => 'project',
            'extras' => $extras,
        ]);
        $author = RoomUserFactory::createOne(['account' => $account, 'room' => $room, 'email' => 'autorin@example.test']);

        return [$room, $author, $account];
    }

    /**
     * A material whose workflow date ($dateColumn) is today.
     *
     * @param array<string, string> $workflow
     */
    private function material(Room $room, User $author, string $dateColumn, array $workflow): int
    {
        $itemId = (int) MaterialFactory::createOne(['room' => $room, 'creator' => $author])->getItemId();
        $extras = unserialize((string) $this->connection->fetchOne('SELECT extras FROM materials WHERE item_id = ?', [$itemId])) ?: [];

        $this->connection->update('materials', [
            $dateColumn => date('Y-m-d').' 00:00:00',
            'workflow_status' => '3_none',
            'extras' => serialize($workflow + $extras),
        ], ['item_id' => $itemId]);

        return $itemId;
    }

    private function trafficLightOf(int $itemId, int $versionId = 0): string
    {
        return (string) $this->connection->fetchOne(
            'SELECT workflow_status FROM materials WHERE item_id = ? AND version_id = ?',
            [$itemId, $versionId],
        );
    }
}
