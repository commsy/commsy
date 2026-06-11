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
 * The kind of event a {@see \App\Entity\Notification} reports.
 *
 * v1 ships a single type; the column exists so additional types (mention,
 * workspace release, …) can be added without a schema change.
 */
enum NotificationType: string
{
    /**
     * A new entry was published in a room the recipient has joined.
     */
    case NewEntry = 'new_entry';
}
