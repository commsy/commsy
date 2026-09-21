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
 * What happened to an entry a {@see \App\Entity\Notification} reports.
 *
 * Belongs to {@see \App\Enum\NotificationType::Entry} alone, which is what the
 * name says: only an entry has something happen *to* it. Every other kind of
 * notification is the event itself and carries no action.
 *
 * Each event is logged as its own notification, so the action tells the view
 * whether to read the row as "created", "edited" or "annotated".
 */
enum EntryAction: string
{
    case Created = 'created';
    case Edited = 'edited';
    case Annotated = 'annotated';
}
