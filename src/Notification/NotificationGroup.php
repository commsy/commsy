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

namespace App\Notification;

use App\Entity\Notification;
use App\Enum\EntryAction;

/**
 * All notification events for one source item, shown as a single panel row: the
 * header describes the entry and its creation, an event strip lists every event
 * (created · edited · annotated …). Events are held oldest-first.
 */
final class NotificationGroup
{
    /**
     * @param Notification[] $events non-empty, chronological (oldest first)
     */
    public function __construct(private readonly array $events)
    {
    }

    /**
     * @return Notification[] oldest first
     */
    public function events(): array
    {
        return $this->events;
    }

    /** The most recent event — carries the entry's current snapshot (title, room …). */
    public function latest(): Notification
    {
        return $this->events[array_key_last($this->events)];
    }

    /**
     * The event that describes the entry's origin: the creation event if the
     * group has one, otherwise the oldest event we hold (e.g. when the entry was
     * created before the feature or its creation had no recipients).
     */
    public function origin(): Notification
    {
        foreach ($this->events as $event) {
            if ($event->getAction() === EntryAction::Created) {
                return $event;
            }
        }

        return $this->events[array_key_first($this->events)];
    }

    public function sourceItemId(): ?int
    {
        return $this->latest()->getSourceItemId();
    }

    public function sourceItemType(): ?string
    {
        return $this->latest()->getSourceItemType();
    }

    public function contextId(): int
    {
        return $this->latest()->getContextId();
    }

    public function title(): string
    {
        return $this->latest()->getTitle();
    }

    public function roomTitle(): string
    {
        return $this->latest()->getRoomTitle();
    }

    /** Newest event time — the row's sort key (recently active entries float up). */
    public function sortKey(): \DateTimeImmutable
    {
        return $this->latest()->getCreatedAt();
    }

    public function isUnread(): bool
    {
        foreach ($this->events as $event) {
            if ($event->isUnread()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The "!" indicator status, mirroring the legacy reader colours:
     * 'new' (red) when an unread creation is present, else 'changed' (amber)
     * when any other unread event is present, else null (nothing to flag).
     */
    public function indicatorStatus(): ?string
    {
        $changed = false;
        foreach ($this->events as $event) {
            if (!$event->isUnread()) {
                continue;
            }
            if ($event->getAction() === EntryAction::Created) {
                return 'new';
            }
            $changed = true;
        }

        return $changed ? 'changed' : null;
    }
}
