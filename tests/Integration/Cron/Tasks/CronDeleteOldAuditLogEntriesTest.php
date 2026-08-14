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

use App\Audit\AuditEvent;
use App\Audit\AuditSubjectType;
use App\Cron\Tasks\CronDeleteOldAuditLogEntries;
use App\Entity\AuditLogEntry;
use App\Entity\Portal;
use App\Repository\AuditLogEntryRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The audit log names people and what was done to them, so it must not grow
 * without end. These tests pin that the retention period is actually enforced
 * and that it does not take fresh entries with it.
 */
class CronDeleteOldAuditLogEntriesTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testEntriesPastTheRetentionPeriodAreRemoved(): void
    {
        self::bootKernel();
        $portal = PortalFactory::createOne();

        $this->recordEntry($portal, 'ancient.target', 400);
        $this->recordEntry($portal, 'recent.target', 10);

        $this->runTask(365);

        self::assertSame(['recent.target'], $this->remainingSubjects());
    }

    /**
     * The period is a parameter, so a shorter one has to bite as well.
     */
    public function testAShorterRetentionPeriodRemovesMore(): void
    {
        self::bootKernel();
        $portal = PortalFactory::createOne();

        $this->recordEntry($portal, 'older.target', 40);
        $this->recordEntry($portal, 'newer.target', 10);

        $this->runTask(30);

        self::assertSame(['newer.target'], $this->remainingSubjects());
    }

    public function testAnEntryOnTheDayOfTheDeadlineSurvives(): void
    {
        self::bootKernel();
        $portal = PortalFactory::createOne();

        $this->recordEntry($portal, 'edge.target', 364);

        $this->runTask(365);

        self::assertSame(['edge.target'], $this->remainingSubjects());
    }

    private function runTask(int $retentionDays): void
    {
        $task = new CronDeleteOldAuditLogEntries(
            static::getContainer()->get(AuditLogEntryRepository::class),
            $retentionDays
        );

        $task->run(null);
    }

    /**
     * Writes an entry and backdates it — the entry stamps itself with the
     * current time, which is the property worth keeping.
     */
    private function recordEntry(Portal $portal, string $subjectLabel, int $daysAgo): void
    {
        $entries = static::getContainer()->get(AuditLogEntryRepository::class);
        $entry = new AuditLogEntry(
            portal: $portal,
            event: AuditEvent::AccountTakeOver,
            subjectType: AuditSubjectType::Account,
            subjectId: null,
            subjectLabel: $subjectLabel,
        );
        $entries->add($entry);

        static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement(
                'UPDATE audit_log SET occurred_at = ? WHERE id = ?',
                [
                    (new DateTimeImmutable())->sub(new DateInterval('P' . $daysAgo . 'D'))->format('Y-m-d H:i:s'),
                    $entry->getId(),
                ]
            );
    }

    /**
     * @return string[]
     */
    private function remainingSubjects(): array
    {
        static::getContainer()->get(EntityManagerInterface::class)->clear();

        return array_map(
            fn (AuditLogEntry $entry): string => $entry->getSubjectLabel(),
            static::getContainer()->get(AuditLogEntryRepository::class)->findBy([], ['id' => 'ASC'])
        );
    }
}
