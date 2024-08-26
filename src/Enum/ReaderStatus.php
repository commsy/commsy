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

enum ReaderStatus: string
{
    /**
     * Identifies a "new" item, i.e. an item that hasn't been seen before.
     */
    case STATUS_NEW = 'new';

    /**
     * Identifies a "changed" item, i.e. an item with unread changes.
     */
    case STATUS_CHANGED = 'changed';

    /**
     * Identifies an "unread" item, i.e. an item that either hasn't been
     * seen before STATUS_NEW or which has unread changes STATUS_CHANGED.
     */
    case STATUS_UNREAD = 'unread';

    /**
     * Identifies a "seen" item, i.e. an item that has been read before.
     * TODO: most CommSy code currently uses an empty string ('') instead of STATUS_SEEN
     */
    case STATUS_SEEN = 'seen';

    /**
     * Identifies an item that has a "new annotation" which hasn't been seen before.
     */
    case STATUS_NEW_ANNOTATION = 'new_annotation';

    /**
     * Identifies an item that has a "changed annotation", i.e. an annotation with unread changes.
     */
    case STATUS_CHANGED_ANNOTATION = 'changed_annotation';
}
