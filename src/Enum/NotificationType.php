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

namespace App\Enum;

/**
 * What a {@see \App\Entity\Notification} is about.
 *
 * This is the subject axis, orthogonal to {@see NotificationAction} (which says
 * what happened to it). It also decides where a notification surfaces: content
 * activity fills the room/dashboard panels, everything else is personal or
 * administrative and belongs in the navbar bell.
 */
enum NotificationType: string
{
    /**
     * An entry in a room was created, edited or annotated.
     */
    case Entry = 'entry';

    /**
     * Someone asked to join a room — a task for its moderators, who can decide
     * it straight from the bell.
     */
    case RoomJoinRequest = 'room_join_request';

    /**
     * A join request was decided; addressed to whoever asked to join.
     */
    case RoomJoinDecision = 'room_join_decision';

    /**
     * An announcement is about to drop out of its room: its validity ends
     * shortly. Addressed to whoever wrote it, one row per announcement.
     */
    case AnnouncementExpiring = 'announcement_expiring';

    /**
     * Content activity belongs in the room/dashboard panels, not the bell.
     */
    public function isContentActivity(): bool
    {
        return self::Entry === $this;
    }

    /**
     * Whether deciding it is still pending, so it must not age out silently.
     */
    public function isTask(): bool
    {
        return self::RoomJoinRequest === $this;
    }

    /**
     * @return self[] the types the room/dashboard panels show
     */
    public static function contentTypes(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $type): bool => $type->isContentActivity()));
    }

    /**
     * @return self[] the types the navbar bell shows
     */
    public static function bellTypes(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $type): bool => !$type->isContentActivity()));
    }
}
