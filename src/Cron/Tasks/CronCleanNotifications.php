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

use function Symfony\Component\Clock\now;

/**
 * Nightly cron that actively dismisses aged-out notifications: anything older
 * than the retention window is deleted, read or not, mirroring the room/dashboard
 * feed which only ever showed recent activity. Deleting an account already
 * removes its notifications via the recipient FK's ON DELETE CASCADE.
 */
class CronCleanNotifications implements CronTaskInterface
{
    /** Notifications older than this are deleted, regardless of read state. */
    private const RETENTION = '-30 days';

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $this->notificationRepository->removeOlderThan(now()->modify(self::RETENTION));
    }

    public function getSummary(): string
    {
        return 'Delete notifications older than 30 days';
    }
}
