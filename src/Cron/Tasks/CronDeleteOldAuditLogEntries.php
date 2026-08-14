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

namespace App\Cron\Tasks;

use App\Repository\AuditLogEntryRepository;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Keeps the audit log to its retention period.
 *
 * The log holds who did what to whom, so it must not accumulate without end.
 */
readonly class CronDeleteOldAuditLogEntries implements CronTaskInterface
{
    public function __construct(
        private AuditLogEntryRepository $auditLogEntries,
        #[Autowire('%commsy.audit.retention_days%')]
        private int $retentionDays
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $threshold = (new DateTimeImmutable())->sub(new DateInterval('P' . $this->retentionDays . 'D'));

        $this->auditLogEntries->deleteOlderThan($threshold);
    }

    public function getSummary(): string
    {
        return 'Delete audit log entries past the retention period';
    }
}
