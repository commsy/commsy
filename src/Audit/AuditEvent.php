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
 * The administrative acts the audit log records.
 *
 * The case value is stored in the database and read back by the portal
 * configuration, so it has to stay stable once released. Add a case together
 * with the recording call site and a label in the `portal` translations.
 */
enum AuditEvent: string
{
    case AccountTakeOver = 'account.take_over';
    case AccountStatusChanged = 'account.status_changed';
    case AccountPasswordReset = 'account.password_reset';
    case AccountTakeOverGrantChanged = 'account.take_over_grant_changed';

    /**
     * Translation key of the human-readable name, domain `portal`.
     */
    public function labelKey(): string
    {
        return match ($this) {
            self::AccountTakeOver => 'portal.audit.event.account_take_over',
            self::AccountStatusChanged => 'portal.audit.event.account_status_changed',
            self::AccountPasswordReset => 'portal.audit.event.account_password_reset',
            self::AccountTakeOverGrantChanged => 'portal.audit.event.account_take_over_grant_changed',
        };
    }
}
