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

namespace App\Cron\Tasks;

use App\Repository\NotificationRepository;
use DateTimeImmutable;

/**
 * Nightly cron pruning notifications the recipient has already read and that
 * are older than the retention window. Defence-in-depth against unbounded
 * growth: unread notifications are always kept, and deleting an account
 * already removes its notifications via the recipient FK's ON DELETE CASCADE.
 */
class CronCleanNotifications implements CronTaskInterface
{
    /** Read notifications older than this are pruned. */
    private const RETENTION = '-90 days';

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $this->notificationRepository->removeReadOlderThan(new DateTimeImmutable(self::RETENTION));
    }

    public function getSummary(): string
    {
        return 'Delete read notifications older than 90 days';
    }
}
