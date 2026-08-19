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

namespace App\Twig\Components;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Notification\NotificationGroup;
use App\Notification\NotificationLinkResolver;
use App\Repository\NotificationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function Symfony\Component\Clock\now;

/**
 * The room/dashboard activity panel that replaces the legacy "newest entries"
 * feed: it lists the account's notifications newest-first, lets the user mark a
 * single entry or all of them read, and polls so fresh activity appears. Rows are
 * never removed by hand — only the retention cron drops aged-out notifications. With
 * {@see $contextId} set it scopes to one room (the room start page); without it
 * it spans all of the account's rooms (the dashboard). Every read and write
 * stays scoped to the logged-in account, so {@see $contextId} only narrows the
 * view and never widens what a user can reach.
 */
#[AsLiveComponent]
final class NotificationPanel
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Account $account = null;

    /** Set to scope the panel to one room; null spans all of the account's rooms. */
    #[LiveProp]
    public ?int $contextId = null;

    /** Panel heading, passed in by the embedding page (keeps the old feed titles). */
    #[LiveProp]
    public ?string $title = null;

    /** Upper bound on notification rows fetched before grouping into entries. */
    private const FETCH_LIMIT = 200;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationLinkResolver $linkResolver,
    ) {
    }

    #[LiveAction]
    public function markRead(#[LiveArg] int $id): void
    {
        // $id is the source item id: marking an entry read covers all its events.
        if ($this->account !== null) {
            $this->notificationRepository->markReadForAccountAndSourceItem($this->account, $id, now());
        }
    }

    #[LiveAction]
    public function markAllRead(): void
    {
        if ($this->account !== null) {
            $this->notificationRepository->markAllReadForAccount($this->account, now(), $this->contextId);
        }
    }

    /**
     * The account's notifications grouped into one entry per source item, newest
     * activity first; each group's events are ordered oldest-first.
     *
     * @return NotificationGroup[]
     */
    public function getGroups(): array
    {
        if ($this->account === null) {
            return [];
        }

        // Content activity only — personal/administrative notifications live in the bell.
        $notifications = $this->notificationRepository->findForAccount(
            $this->account,
            $this->contextId,
            self::FETCH_LIMIT,
            NotificationType::contentTypes(),
        );

        /** @var array<int|string, Notification[]> $byItem */
        $byItem = [];
        foreach ($notifications as $notification) {
            $key = $notification->getSourceItemId() ?? 'n'.$notification->getId();
            $byItem[$key][] = $notification;
        }

        $groups = [];
        foreach ($byItem as $events) {
            usort($events, static fn (Notification $a, Notification $b): int => [$a->getCreatedAt(), $a->getId()] <=> [$b->getCreatedAt(), $b->getId()]);
            $groups[] = new NotificationGroup($events);
        }

        usort($groups, static fn (NotificationGroup $a, NotificationGroup $b): int => $b->sortKey() <=> $a->sortKey());

        return $groups;
    }

    public function getUnreadCount(): int
    {
        return $this->account !== null
            ? $this->notificationRepository->countUnreadForAccount($this->account, $this->contextId, NotificationType::contentTypes())
            : 0;
    }

    /**
     * The entry's detail URL, or null when it can no longer be resolved (the
     * row then renders without a link, e.g. after the item was removed).
     */
    public function linkFor(Notification $notification): ?string
    {
        return $this->linkResolver->resolve($notification);
    }

    /**
     * A date entry's scheduled start as a real date object for locale-aware
     * formatting, or null for non-date entries / unset dates.
     */
    public function scheduledStart(Notification $notification): ?\DateTimeImmutable
    {
        return $this->parseDate($notification->getPayload()->dateStart);
    }

    /**
     * A date entry's scheduled end as a real date object, or null.
     */
    public function scheduledEnd(Notification $notification): ?\DateTimeImmutable
    {
        return $this->parseDate($notification->getPayload()->dateEnd);
    }

    private function parseDate(?string $raw): ?\DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw);

        return $parsed instanceof \DateTimeImmutable ? $parsed : null;
    }
}
