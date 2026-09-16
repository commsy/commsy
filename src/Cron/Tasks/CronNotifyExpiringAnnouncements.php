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

use App\Entity\Announcement;
use App\Entity\Notification;
use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Notification\NotificationPayload;
use App\Repository\AnnouncementRepository;
use App\Repository\NotificationRepository;
use App\Repository\RoomRepository;
use DateTimeImmutable;

use function Symfony\Component\Clock\now;

/**
 * Warns the author of an announcement before it expires.
 *
 * The first notification that is not the consequence of somebody doing
 * something: it fires because time passed. The sweep asks which announcements
 * reach their reminder moment — a week before their validity ends — inside the
 * window since the last run, so a skipped run catches up and a repeated run
 * produces nothing new.
 *
 * One row per announcement, addressed to its author alone. It carries the
 * announcement as its source item, which is what gives the bell a direct link
 * to the entry rather than to the room.
 */
class CronNotifyExpiringAnnouncements implements CronTaskInterface
{
    /** How long before the end of its validity the author is warned. */
    private const LEAD_TIME = '+7 days';

    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $now = now();
        // Without a recorded run, look back a day — the interval the cron keeps.
        $since = $lastRun ?? $now->modify('-1 day');

        $expiring = $this->announcementRepository->findExpiringBetween(
            $since->modify(self::LEAD_TIME),
            $now->modify(self::LEAD_TIME),
        );

        foreach ($expiring as $announcement) {
            $this->notifyAuthor($announcement);
        }
    }

    public function getSummary(): string
    {
        return 'Warn announcement authors a week before expiry';
    }

    private function notifyAuthor(Announcement $announcement): void
    {
        $account = $announcement->getCreator()?->getAccount();
        if ($account === null) {
            return;
        }

        $endsAt = DateTimeImmutable::createFromInterface($announcement->getEnddate());
        // Dated at the reminder moment, not at "now": a second sweep on the same
        // announcement then recognises its own row and leaves it alone.
        $occurredAt = $endsAt->modify('-7 days');

        if ($this->notificationRepository->existsForSourceItemAt($announcement->getItemId(), $occurredAt)) {
            return;
        }

        $contextId = (int) $announcement->getContextId();

        $this->notificationRepository->save(new Notification(
            $account,
            NotificationType::AnnouncementExpiring,
            $contextId,
            $announcement->getTitle(),
            $this->roomRepository->find($contextId)?->getTitle() ?? '',
            $occurredAt,
            $announcement->getItemId(),
            'announcement',
            null,
            NotificationAction::Created,
            new NotificationPayload(dateEnd: $endsAt->format('Y-m-d H:i:s')),
        ));
    }
}
