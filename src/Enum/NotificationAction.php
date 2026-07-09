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
 * What happened to the source item the {@see \App\Entity\Notification} reports.
 *
 * Mirrors the room/dashboard activity feed, which surfaces both freshly created
 * and edited entries, plus annotations. Each event is logged as its own
 * notification, so the action tells the view whether to read it as "created",
 * "edited" or "annotated".
 */
enum NotificationAction: string
{
    case Created = 'created';
    case Edited = 'edited';
    case Annotated = 'annotated';
}
