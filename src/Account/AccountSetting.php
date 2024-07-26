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

namespace App\Account;

enum AccountSetting: string
{
    case NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION = 'notifyPortalModOnSelfRegistration';
    case NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE = 'notifyPortalModOnWorkspaceChange';

    public function default(): array
    {
        return match ($this) {
            AccountSetting::NOTIFY_PORTAL_MOD_ON_SELF_REGISTRATION => ['enabled' => true],
            AccountSetting::NOTIFY_PORTAL_MOD_ON_WORKSPACE_CHANGE => ['enabled' => false],
        };
    }
}
