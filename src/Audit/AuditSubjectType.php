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

declare(strict_types=1);

namespace App\Audit;

/**
 * What kind of thing an audit entry is about.
 *
 * Only accounts are recorded so far. The type is stored anyway because
 * `subject_id` cannot be read without it, and it is what lets a later event
 * name a workspace or the portal itself without a schema change.
 */
enum AuditSubjectType: string
{
    case Account = 'account';
}
